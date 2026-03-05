<?php
require_once '../config.php';
require_once 'team_header.php';

$pdo = get_db_connection();
$hash = password_hash('derin_sifre', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");

$stmt->execute(['admin35', $hash]);