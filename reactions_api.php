<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();
$anonId = $_COOKIE['rv_anon'] ?? null;
$action = $_REQUEST['action'] ?? null;
$course_id = isset($_REQUEST['course_id']) ? (int)$_REQUEST['course_id'] : 0;

if ($action === 'get') {
    // toplamlar
    $stmt = $pdo->prepare("SELECT reaction, COUNT(*) c FROM course_reactions WHERE course_id=? GROUP BY reaction");
    $stmt->execute([$course_id]);
    $likes = 0; $dislikes = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['reaction']==='like') $likes = (int)$r['c'];
        if ($r['reaction']==='dislike') $dislikes = (int)$r['c'];
    }

    $your = null;
    if ($anonId) {
        $stmt = $pdo->prepare("SELECT reaction FROM course_reactions WHERE course_id=? AND anon_id=? LIMIT 1");
        $stmt->execute([$course_id, $anonId]);
        $your = $stmt->fetchColumn() ?: null;
    }

    echo json_encode(['likes'=>$likes,'dislikes'=>$dislikes,'your_reaction'=>$your]); exit;
}

if ($action === 'toggle') {
    if (!$anonId) {
        // basitçe anonim id yoksa oluştur
        $anonId = bin2hex(random_bytes(16));
        setcookie('rv_anon', $anonId, time()+60*60*24*365, "/; samesite=Lax", "", false, true);
    }

    $reaction = $_POST['reaction'] ?? '';
    if (!in_array($reaction, ['like','dislike'], true)) {
        echo json_encode(['ok'=>false,'error'=>'reaction invalid']); exit;
    }

    // mevcut var mı?
    $stmt = $pdo->prepare("SELECT reaction FROM course_reactions WHERE course_id=? AND anon_id=? LIMIT 1");
    $stmt->execute([$course_id, $anonId]);
    $current = $stmt->fetchColumn();

    if ($current === $reaction) {
        // aynıysa kaldır (toggle off)
        $del = $pdo->prepare("DELETE FROM course_reactions WHERE course_id=? AND anon_id=?");
        $del->execute([$course_id, $anonId]);
    } else if ($current === false) {
        // hiç yoksa ekle
        $ins = $pdo->prepare("INSERT INTO course_reactions (course_id, anon_id, reaction) VALUES (?,?,?)");
        $ins->execute([$course_id, $anonId, $reaction]);
    } else {
        // farklıysa güncelle
        $up = $pdo->prepare("UPDATE course_reactions SET reaction=? WHERE course_id=? AND anon_id=?");
        $up->execute([$reaction, $course_id, $anonId]);
    }

    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'error'=>'bad request']);
