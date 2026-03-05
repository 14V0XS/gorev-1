<?php
session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
if (!isset($_SESSION['admin_logged_in'])) { exit('Yetkisiz erişim'); }

// --- RASTGELE ID VE ŞİFRE OLUŞTURMA FONKSİYONLARI ---
function generateRandomId($length = 6) {
    $chars = '0123456789';
    $rand = '';
    for ($i = 0; $i < $length; $i++) { $rand .= $chars[rand(0, strlen($chars) - 1)]; }
    return $rand;
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|';
    // En az bir büyük harf ve bir özel karakter olmasını garantile
    $password = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1) .
                substr(str_shuffle('!@#$%^&*()_+-=[]{}|'), 0, 1) .
                substr(str_shuffle($chars), 0, $length - 2);
    return str_shuffle($password);
}

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_team' && isset($_POST['team_number'])) {
        $team_number = $_POST['team_number'];
        $team_name = "Takım #" . $team_number;
        $generated_id = generateRandomId();
        $generated_password = generatePassword();
        $password_hash = password_hash($generated_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT id FROM teams WHERE team_number = ?");
        $stmt->execute([$team_number]);
        if ($stmt->rowCount() > 0) {
            header("Location: teams.php?fail=exists&number=$team_number");
            exit();
        }



        $stmt = $pdo->prepare("INSERT INTO teams (team_number, team_name, team_id_generated, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$team_number, $team_name, $generated_id, $password_hash]);

        // Bilgileri admin panelinde göstermek için URL'e ekleyerek geri yönlendir
        $new_team_info = json_encode(['number' => $team_number, 'id' => $generated_id, 'password' => $generated_password]);
        header("Location: teams.php?new_team_info=" . urlencode($new_team_info));
        exit();
    }

    if ($action === 'delete_team' && isset($_POST['team_db_id'])) {
        $team_db_id = $_POST['team_db_id'];
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$team_db_id]);
        header("Location: teams.php?status=deleted");
        exit();
    }
}
header("Location: teams.php");
exit();
?>