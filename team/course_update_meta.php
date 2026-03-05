<?php

declare(strict_types=1);

session_start();



$projectRoot = dirname(__DIR__); // docroot yerine proje kökü tercih

require_once $projectRoot . '/config.php';



header('Content-Type: application/json; charset=utf-8');



function json_response($ok, $data = [], $code = 200) {

    http_response_code($code);

    echo json_encode($ok ? ['success'=>true] + $data : ['success'=>false] + $data);

    exit;

}



try {

    if (!isset($_SESSION['team_logged_in'], $_SESSION['team_db_id'])) {

        json_response(false, ['message'=>'Unauthorized'], 401);

    }



    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        json_response(false, ['message'=>'Method Not Allowed'], 405);

    }



    $pdo = get_db_connection();

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



    $course_id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $title           = trim($_POST['title'] ?? '');

    $about_text      = trim($_POST['about_text'] ?? '');

    $intro_video_url = trim($_POST['intro_video_url'] ?? ''); // <- boş string gelebilir

    $goal_text       = trim($_POST['goal_text'] ?? '');

    $learnings       = trim($_POST['learnings_text'] ?? '');

    $category_id     = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    $level           = trim($_POST['level'] ?? '');

    $comp            = trim($_POST['comp'] ?? 'FRC');

    $language        = trim($_POST['language'] ?? 'tr');



    if ($course_id <= 0 || $title === '' || $about_text === '' || $goal_text === '' || $learnings === '' || $category_id <= 0 || $level === '' || !in_array($comp, ['FRC','FTC','FLL']) || !in_array($language, ['tr','en'])) {

        json_response(false, ['message'=>'Eksik veya hatalı form verisi'], 422);

    }



    // sahiplik

    $q = $pdo->prepare('SELECT id FROM courses WHERE id=:id AND team_db_id=:team');

    $q->execute([':id'=>$course_id, ':team'=>$_SESSION['team_db_id']]);

    if (!$q->fetch()) {

        json_response(false, ['message'=>'Course not found or not owned'], 403);

    }



    // intro_video_url: '' ise NULL yazacağız

    $introParam = ($intro_video_url === '') ? null : $intro_video_url;



    $sql = "UPDATE courses

               SET title = :title,

                   status = :status,

                   about_text = :about,

                   goal_text = :goal,

                   learnings_text = :learn,

                   category_id = :cat,

                   level = :lvl,

                   comp = :comp,

                   language = :language,

                   intro_video_url = :intro,        -- her zaman set

                   updated_at = NOW()

             WHERE id = :id AND team_db_id = :team";

    $u = $pdo->prepare($sql);



    // normal bind

    $u->bindValue(':title',  $title);

    $u->bindValue(':status', 'pending');

    $u->bindValue(':about',  $about_text);

    $u->bindValue(':goal',   $goal_text);

    $u->bindValue(':learn',  $learnings);

    $u->bindValue(':cat',    $category_id, PDO::PARAM_INT);

    $u->bindValue(':lvl',    $level);

    $u->bindValue(':comp',   $comp);

    $u->bindValue(':language', $language);

    $u->bindValue(':id',     $course_id, PDO::PARAM_INT);

    $u->bindValue(':team',   $_SESSION['team_db_id'], PDO::PARAM_INT);



    // intro için NULL/STR ayarı

    if ($introParam === null) {

        $u->bindValue(':intro', null, PDO::PARAM_NULL);

    } else {

        $u->bindValue(':intro', $introParam, PDO::PARAM_STR);

    }



    $u->execute();



    json_response(true, ['message'=>'Kurs bilgileri güncellendi.']);



} catch (Throwable $e) {

    json_response(false, ['message'=>'Server error','error'=>$e->getMessage()], 500);

}

