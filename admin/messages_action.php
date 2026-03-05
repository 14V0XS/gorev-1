<?php
declare(strict_types=1);
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function j($ok,$data=[],$code=200){ http_response_code($code); echo json_encode($ok?['success'=>true]+$data:['success'=>false]+$data); exit; }

try{
    // Admin kontrolünü kendi mantığına göre yap
    if (!isset($_SESSION['admin_logged_in'])) j(false,['message'=>'Unauthorized'],401);

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $action  = $payload['action'] ?? '';
    $ids     = $payload['ids'] ?? [];
    if (!in_array($action, ['read','unread','delete'], true) || !is_array($ids) || empty($ids)) {
        j(false,['message'=>'Geçersiz istek'],422);
    }

    // ints
    $ids = array_map('intval', $ids);
    $ids = array_values(array_filter($ids, fn($v)=>$v>0));
    if (empty($ids)) j(false,['message'=>'ID yok'],422);

    $pdo = get_db_connection();
    $in  = implode(',', array_fill(0, count($ids), '?'));

    if ($action==='read'){
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id IN ($in)");
        $stmt->execute($ids);
    } elseif ($action==='unread'){
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read=0 WHERE id IN ($in)");
        $stmt->execute($ids);
    } else { // delete
        $stmt = $pdo->prepare("UPDATE contact_messages SET archived=1 WHERE id IN ($in)");
        $stmt->execute($ids);
    }

    j(true, ['message'=>'OK']);
}catch(Throwable $e){
    j(false, ['message'=>'Server error','error'=>$e->getMessage()],500);
}
