<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();
$anonId = $_COOKIE['rv_anon'] ?? null;
$action = $_REQUEST['action'] ?? null;
$course_id = isset($_REQUEST['course_id']) ? (int)$_REQUEST['course_id'] : 0;

if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');

    // 1) Soruları çek
    $stmt = $pdo->prepare("
        SELECT id, body, DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM course_questions
        WHERE course_id=? AND is_approved=1
        ORDER BY id DESC
        LIMIT 100
    ");
    $stmt->execute([$course_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Soru yoksa direkt dön
    if (!$items) { echo json_encode(['items'=>[]]); exit; }

    // 2) Tek seferde tüm yanıtları çek ve sorulara ekle
    $ids = array_column($items, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $r = $pdo->prepare("
        SELECT question_id,
               responder_name,
               body,
               DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') AS created_at
        FROM course_question_replies
        WHERE question_id IN ($in)
        ORDER BY id ASC
    ");
    $r->execute($ids);

    $repliesByQ = [];
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $qid = (int)$row['question_id'];
        $repliesByQ[$qid][] = [
            'responder_name' => $row['responder_name'] ?: 'Eğitmen',
            'body'           => $row['body'],
            'created_at'     => $row['created_at'],
        ];
    }

    // 3) Her soruya replies alanını ekle
    foreach ($items as &$it) {
        $qid = (int)$it['id'];
        $it['replies'] = $repliesByQ[$qid] ?? [];
    }
    unset($it);

    echo json_encode(['items'=>$items]);
    exit;
}

if ($action === 'add') {
    $body = trim($_POST['body'] ?? '');
    if ($body === '') { echo json_encode(['ok'=>false,'error'=>'empty']); exit; }

    if (!$anonId) {
        // anonim id yarat
        $anonId = bin2hex(random_bytes(16));
        setcookie('rv_anon', $anonId, time()+60*60*24*365, "/; samesite=Lax", "", false, true);
    }

    $ins = $pdo->prepare("INSERT INTO course_questions (course_id, anon_id, body, is_approved) VALUES (?,?,?,0)");
    $ins->execute([$course_id, $anonId, $body]);

    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'replies') {
    $qid = $_POST['question_id'];
    if (empty($qid)) { echo json_encode(['ok'=>false,'error'=>'empty']); exit; }


    $ins = $pdo->prepare("SELECT * FROM course_questions_replies WHERE question_id=?");
    $ins->execute([$qid]);

    $replys = $ins->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['items'=>$replys]); exit;

}

echo json_encode(['ok'=>false,'error'=>'bad request']);
