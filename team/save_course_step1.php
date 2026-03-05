<?php
session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
// 1. ADIM: Oturum kontrolü
// Eğer kullanıcı giriş yapmamışsa veya takım numarası oturumda yoksa, yetkisiz sayfasına yönlendir.
if (!isset($_SESSION['team_logged_in']) || !isset($_SESSION['team_number'])) { 
    header("Location: $projectRoot/team/unauthorized.php");
    exit(); 
}

// 2. ADIM: Sadece form POST metodu ile gönderildiğinde devam et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once($projectRoot . '/config.php');

    $pdo = get_db_connection();
    
    try {
        // 3. ADIM (GÜVENLİK DÜZELTMESİ): Oturumdaki 'team_db_id'ye GÜVENME!
        // Bunun yerine, oturumdaki 'team_number' ile veritabanından doğru ve güncel ID'yi kendin çek.
        $stmt_get_id = $pdo->prepare("SELECT id FROM teams WHERE team_number = :team_number LIMIT 1");
        $stmt_get_id->execute([':team_number' => $_SESSION['team_number']]);
        $team = $stmt_get_id->fetch(PDO::FETCH_ASSOC);

        // Eğer bu numarayla bir takım bulunamazsa (imkansız gibi ama bir güvenlik önlemi), işlemi durdur.
        if (!$team) {
            die("Oturum hatası: Geçerli takım ID'si bulunamadı.");
        }
        $correct_team_id = $team['id']; // İşte bu, %100 doğru ve güncel ID.

        // 4. ADIM: Rastgele bir kurs kimliği oluştur
        $course_uid = 'kurs_' . bin2hex(random_bytes(8)); 

        // 5. ADIM: Veritabanına yeni kursu ekle
        $stmt_insert = $pdo->prepare(
            "INSERT INTO courses (
                course_uid, team_db_id, category_id, title, about_text, 
                goal_text, learnings_text, level, comp, language, status
             ) VALUES (
                :uid, :team_id, :cat_id, :title, :about, 
                :goal, :learnings, :level, :comp, :language, 'pending'
            )"
        );
        
        $language = $_POST['language'] ?? 'tr';
        if (!in_array($language, ['tr', 'en'])) $language = 'tr';
        
        $isSuccess = $stmt_insert->execute([
            ':uid' => $course_uid,
            ':team_id' => $correct_team_id,
            ':cat_id' => $_POST['category_id'],
            ':title' => $_POST['title'],
            ':about' => $_POST['about_text'],
            ':goal' => $_POST['goal_text'],
            ':learnings' => $_POST['learnings_text'],
            ':level' => $_POST['level'],
            ':comp' => $_POST['comp'] ?? 'FRC',
            ':language' => $language
        ]);

        // 6. ADIM: Başarılıysa, 2. adıma (görsel yükleme) yönlendir
        if ($isSuccess) {
            $new_course_id = $pdo->lastInsertId(); 
            header("Location: manage_course_content.php?id=" . $new_course_id);
            exit();
        } else {
            die("Kurs kaydedilirken bir veritabanı hatası oluştu.");
        }

    } catch (PDOException $e) {
        // Olası veritabanı hatalarını yakala ve detaylı bilgi ver
        die("Veritabanı Hatası: " . $e->getMessage());
    }

} else {
    // Sayfa POST ile çağrılmazsa panele yönlendir
    header('Location: panel.php');
    exit();
}
?>