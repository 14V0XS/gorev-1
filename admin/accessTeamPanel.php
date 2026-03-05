<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config.php';

// 1) Admin yetkisi yoksa hemen çık
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../index.php', true, 302);
    exit;
}

// 2) Sadece GET kabul et
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Location: ../courses.php', true, 302);
    exit;
}

// 3) Çıkış (admin'in takım görünümünden çıkması)
$exitParam = filter_input(INPUT_GET, 'exit', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($exitParam) {
    unset(
        $_SESSION['team_logged_in'],
        $_SESSION['team_db_id'],
        $_SESSION['team_number'],
        $_SESSION['admin_panel_view']
    );
    header('Location: panel.php', true, 302);
    exit;
}

// 4) Takım paneline “admin olarak gör” girişi
$teamId = filter_input(INPUT_GET, 'team_id', FILTER_VALIDATE_INT);
if (!$teamId) {
    // team_id yoksa veya geçersizse admin paneline dön
    header('Location: panel.php', true, 302);
    exit;
}

$details = getTeam($teamId);
if (!$details) {
    header('Location: panel.php', true, 302);
    exit;
}

// Takım kimliğini admin görünümü olarak oturuma yaz
$_SESSION['team_logged_in']  = true;
$_SESSION['team_db_id']      = $details['id'];
$_SESSION['team_number']     = $details['team_number'];
$_SESSION['admin_panel_view']= 1;

header('Location: ../team/panel.php', true, 302);
exit;
