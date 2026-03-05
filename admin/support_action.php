<?php
/** support_action.php — Tanılama + sağlam hata raporu sürümü */
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    // ---- Ortam ve Yetki ----
    $projectRoot = dirname(__DIR__);               // admin klasörünün bir üstü
    if (!is_dir($projectRoot)) { throw new RuntimeException("projectRoot bulunamadı: $projectRoot"); }
    require_once $projectRoot . '/config.php';

    if (empty($_SESSION['admin_logged_in'])) {
        throw new RuntimeException('Yetkisiz erişim (admin oturumu yok).');
    }

    // ---- PDO ----
    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) { throw new RuntimeException('PDO nesnesi alınamadı.'); }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ---- Input ----
    $raw = file_get_contents('php://input');
    $in  = json_decode($raw ?: '[]', true);
    if (!is_array($in)) { throw new RuntimeException('Geçersiz JSON gövdesi.'); }
    $action = $in['action'] ?? '';

    // ---- Hızlı sağlık kontrolü ----
    if ($action === 'ping') {
        // tablo var mı? alanlar var mı?
        $check = $pdo->query("SHOW COLUMNS FROM team_support")->fetchAll();
        $cols = array_column($check, 'Field');
        $needed = ['id','team_id','content_id','message','type','is_resolved','created_at','archived'];
        $missing = array_values(array_diff($needed, $cols));
        echo json_encode([
            'success' => true,
            'db_ok'   => true,
            'missing_columns' => $missing,
            'session_admin' => true,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    switch ($action) {


        case 'reply': {
            $id  = (int)($in['id'] ?? 0);
            $msg = trim((string)($in['message'] ?? ''));
            if ($id<=0) throw new RuntimeException('Geçersiz id');
            if ($msg==='') throw new RuntimeException('Mesaj boş olamaz');

            $q = $pdo->prepare("SELECT id, team_id FROM team_support WHERE id=? AND type='support' AND archived=0 LIMIT 1");
            $q->execute([$id]);
            $orig = $q->fetch();
            if (!$orig) throw new RuntimeException("Support kaydı bulunamadı. id=$id");

            $ins = $pdo->prepare("
        INSERT INTO team_support (team_id, content_id, message, type, is_resolved)
        VALUES (?, ?, ?, 'support_response', 0)
      ");
            $ins->execute([(int)$orig['team_id'], $id, mb_substr($msg,0,250,'UTF-8')]);

            echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
            break;
        }

        case 'resolve':
        case 'unresolve': {
            $ids = array_map('intval', (array)($in['ids'] ?? []));
            if (!$ids) throw new RuntimeException('Seçim yok');
            $val = ($action==='resolve') ? 1 : 0;
            $inQ = implode(',', array_fill(0, count($ids), '?'));
            $st = $pdo->prepare("UPDATE team_support SET is_resolved=? WHERE id IN ($inQ) AND type='support'");
            $st->execute(array_merge([$val], $ids));
            echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
            break;
        }
        case 'thread': {
            $id = (int)($in['id'] ?? 0);
            if ($id<=0) throw new RuntimeException('Geçersiz id');

            // KÖK support
            $q = $pdo->prepare("
    SELECT s.id, s.team_id, s.message, s.is_resolved, s.created_at,
           t.team_name, t.team_number
      FROM team_support s
      LEFT JOIN teams t ON t.id = s.team_id
     WHERE s.id = ? AND s.type='support' AND s.archived = 0 AND s.content_id IS NULL
     LIMIT 1
  ");
            $q->execute([$id]);
            $item = $q->fetch(PDO::FETCH_ASSOC);
            if (!$item) throw new RuntimeException("Support kaydı bulunamadı veya arşivli. id=$id");

            // Admin cevapları (support_response) – köke bağlı
            $r = $pdo->prepare("
    SELECT id, message, created_at
      FROM team_support
     WHERE type='support_response' AND content_id=? AND archived=0
     ORDER BY created_at ASC, id ASC
  ");
            $r->execute([$id]);
            $replies = $r->fetchAll(PDO::FETCH_ASSOC);

            // Takım follow-up'ları (type='support', content_id = admin cevabının id'si)
            $followups_by = [];
            if (!empty($replies)) {
                $replyIds = array_map(fn($x)=>(int)$x['id'], $replies);
                $in = implode(',', array_fill(0, count($replyIds), '?'));
                $fu = $pdo->prepare("
      SELECT id, content_id AS reply_to, message, created_at
        FROM team_support
       WHERE type='support' AND archived=0 AND content_id IN ($in)
       ORDER BY created_at ASC, id ASC
    ");
                $fu->execute($replyIds);
                while ($row = $fu->fetch(PDO::FETCH_ASSOC)) {
                    $reply_to = (int)$row['reply_to'];
                    $followups_by[$reply_to][] = [
                        'id'         => (int)$row['id'],
                        'message'    => (string)$row['message'],
                        'created_at' => (string)$row['created_at'],
                    ];
                }
            }

            $teamDisp = trim(($item['team_number'] ? '#'.$item['team_number'].' ' : '').($item['team_name'] ?? ''));

            echo json_encode([
                'success'=>true,
                'item'=>[
                    'id'          => (int)$item['id'],
                    'team_id'     => (int)$item['team_id'],
                    'team_display'=> $teamDisp,
                    'message'     => (string)$item['message'],
                    'is_resolved' => (int)$item['is_resolved'] === 1,
                    'created_at'  => (string)$item['created_at'],
                ],
                'replies'=>$replies,
                'followups_by'=>$followups_by,   // <<< EK
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        /*
                case 'archive': {
                    $ids = array_map('intval', (array)($in['ids'] ?? []));
                    if (!$ids) throw new RuntimeException('Seçim yok');
                    $inQ = implode(',', array_fill(0, count($ids), '?'));
                    $st = $pdo->prepare("UPDATE team_support SET archived=1 WHERE id IN ($inQ) AND type='support'");
                    $st->execute($ids);
                    echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
                    break;
                }
        */      case 'delete_reply': {
        $replyId = (int)($in['reply_id'] ?? 0);
        if ($replyId <= 0) throw new RuntimeException('Geçersiz reply_id');

        // (İsteğe bağlı güvenlik) gerçekten support_response mı?
        $chk = $pdo->prepare("SELECT id FROM team_support WHERE id=? AND type='support_response' LIMIT 1");
        $chk->execute([$replyId]);
        if (!$chk->fetch()) throw new RuntimeException('Yanıt bulunamadı');

        // Sil
        $st = $pdo->prepare("DELETE FROM team_support WHERE id=? AND type='support_response' LIMIT 1");
        $st->execute([$replyId]);

        echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
        break;
    }

        case 'delete': {
            $ids = array_map('intval', (array)($in['ids'] ?? []));
            if (!$ids) throw new RuntimeException('Seçim yok');
            $inQ = implode(',', array_fill(0, count($ids), '?'));
            $st = $pdo->prepare("UPDATE team_support SET archived=1 WHERE id IN ($inQ) AND type='support'");
            $st->execute($ids);
            echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
            break;
        }

        default:
            throw new RuntimeException("Bilinmeyen action: ".(string)$action);
    }

} catch (Throwable $e) {
    // Hata mesajını görünür verelim (JSON içinde)
    http_response_code(200); // fetch .json() patlamasın
    echo json_encode([
        'success'=>false,
        'message'=>$e->getMessage(),
        'where'  =>$e->getFile().':'.$e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
