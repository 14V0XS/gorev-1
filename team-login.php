<?php

// team-login.php — 3 deneme sonra 15 dk kilit + form disable + geri sayım

declare(strict_types=1);



if (session_status() !== PHP_SESSION_ACTIVE) {

    session_start();

}

require_once __DIR__ . '/config.php';



const MAX_TRIES = 5;

const LOCK_MIN  = 15;



// CSRF token

if (empty($_SESSION['csrf'])) {

    $_SESSION['csrf'] = bin2hex(random_bytes(32));

}

$csrf = $_SESSION['csrf'];



// Oturum açık ise panel'e gönder

if (!empty($_SESSION['team_logged_in'])) {

    header('Location: team/panel.php', true, 302);

    exit;

}



$error_message = null;

$form_disabled = false;   // <-- yeni

$lock_seconds  = 0;       // <-- yeni (geri sayım için)



function client_ip(): string {

    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {

        if (!empty($_SERVER[$k])) {

            return trim(explode(',', $_SERVER[$k])[0]);

        }

    }

    return '0.0.0.0';

}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {

            throw new RuntimeException('Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');

        }



        $pdo = get_db_connection();

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("SET time_zone = '+00:00'"); // <-- YENİ



        $team_number       = trim((string)($_POST['team_number'] ?? ''));

        $team_id_generated = trim((string)($_POST['team_id'] ?? ''));

        $password          = (string)($_POST['password'] ?? '');



        if ($team_number === '' || $team_id_generated === '' || $password === '') {

            throw new RuntimeException('Lütfen tüm alanları doldurun.');

        }



        $identity = strtolower($team_number . ':' . $team_id_generated);

        $ip       = client_ip();



        $sel = $pdo->prepare("SELECT attempts, locked_until FROM login_attempts WHERE identity = ? AND ip = ? LIMIT 1");

        $sel->execute([$identity, $ip]);

        $thr = $sel->fetch(PDO::FETCH_ASSOC);



        $lock_seconds = 0;

        if ($thr) {

            $secStmt = $pdo->prepare("

        SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), locked_until))

        FROM login_attempts

        WHERE identity = ? AND ip = ?

        LIMIT 1

    ");

            $secStmt->execute([$identity, $ip]);

            $lock_seconds = (int)$secStmt->fetchColumn();



            if ($lock_seconds > 0) {

                $form_disabled = true;

                $error_message = null;

            }

        }





        if (!$form_disabled) {

            $stmt = $pdo->prepare("SELECT id, team_number, team_id_generated, password_hash

                                   FROM teams WHERE team_number = ? AND team_id_generated = ? LIMIT 1");

            $stmt->execute([$team_number, $team_id_generated]);

            $team = $stmt->fetch(PDO::FETCH_ASSOC);



            $ok = $team && password_verify($password, $team['password_hash']);



            if ($ok) {

                $del = $pdo->prepare("DELETE FROM login_attempts WHERE identity = ? AND ip = ? LIMIT 1");

                $del->execute([$identity, $ip]);



                session_regenerate_id(true);

                $_SESSION['team_logged_in'] = true;

                $_SESSION['team_db_id']     = (int)$team['id'];

                $_SESSION['team_number']    = $team['team_number'];



                header('Location: team/panel.php', true, 302);

                exit;

            }



            if ($thr) {

                $attempts = (int)$thr['attempts'] + 1;

                if ($attempts >= MAX_TRIES) {

                    $upd = $pdo->prepare("

    UPDATE login_attempts

       SET attempts = ?, locked_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE)

     WHERE identity = ? AND ip = ?

     LIMIT 1

");

                    $upd->execute([$attempts, LOCK_MIN, $identity, $ip]);



                    $secStmt = $pdo->prepare("

    SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), locked_until))

    FROM login_attempts

    WHERE identity = ? AND ip = ?

    LIMIT 1

");

                    $secStmt->execute([$identity, $ip]);

                    $lock_seconds  = (int)$secStmt->fetchColumn();

                    $form_disabled = true;

                    $error_message = null;



                } else {

                    $upd = $pdo->prepare("

                        UPDATE login_attempts

                           SET attempts = ?, locked_until = NULL

                         WHERE identity = ? AND ip = ?

                         LIMIT 1

                    ");

                    $upd->execute([$attempts, $identity, $ip]);

                    $left = MAX_TRIES - $attempts;

                    $error_message = "Hatalı giriş. Kalan deneme: {$left}";

                }

            } else {

                $ins = $pdo->prepare("

                    INSERT INTO login_attempts (identity, ip, attempts, locked_until)

                    VALUES (?, ?, 1, NULL)

                ");

                $ins->execute([$identity, $ip]);

                $left = MAX_TRIES - 1;

                $error_message = "Hatalı giriş. Kalan deneme: {$left}";

            }

        }



    } catch (Throwable $e) {

        $error_message = $e->getMessage();

    }

}

?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= __('team_login.page_title') ?></title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">



    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>



    <style>

        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: #f7f7f7; }

        @font-face { font-family: "Sakana"; src: url("assets/fonts/Sakana.ttf") format("truetype"); }

        .rookieverse { font-family: "Sakana", sans-serif !important; font-weight: bold; font-size: 2.5rem; color: #E5AE32; text-decoration: none; }

        .password-wrapper { position: relative; }

        .password-toggle-icon { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); cursor: pointer; color: #6b7280; }

        .disabledish { opacity: .5; cursor: not-allowed; }

    </style>

    <!-- Google tag (gtag.js) -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>

    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());



        gtag('config', 'G-EDSVL8LRCY');

    </script>

</head>

<body class="flex items-center justify-center min-h-screen">



<div class="w-full max-w-md p-4">

    <div class="flex justify-center mb-8">

        <a href="index.php"><span class="rookieverse">FRC ROOKIEVERSE</span></a>

    </div>



    <div class="bg-white border-2 border-gray-200 rounded-xl shadow-lg p-8">

        <h1 class="text-2xl font-bold text-center text-gray-800 mb-2"><?= __('team_login.title') ?></h1>

        <p class="text-center text-gray-500 mb-6"><?= __('team_login.description') ?></p>



        <?php if ($error_message && !$form_disabled): ?>

            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" role="alert">

                <strong class="font-bold">Bilgi</strong>

                <span class="block sm:inline"> <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?> </span>

            </div>

        <?php endif; ?>



        <?php if ($form_disabled): ?>

            <div id="lockNotice" class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg mb-6" role="alert">

                <strong class="font-bold"><?= __('team_login.security_lock') ?></strong>

                <span class="block sm:inline"> Tekrar deneyebilmek için <span id="lock-timer" class="font-mono">--:--</span> kaldı.</span>

            </div>

        <?php endif; ?>



        <?php $disabled_attr = $form_disabled ? 'disabled aria-disabled="true"' : ''; ?>



        <form id="loginForm" action="team-login.php" method="POST" class="space-y-6" autocomplete="off" novalidate>

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <div>

                <label for="team-number" class="font-medium text-gray-700"><?= __('team_login.team_number') ?></label>

                <input id="team-number" name="team_number" type="number" required class="mt-2 w-full px-4 py-2.5 border rounded-lg <?= $form_disabled ? 'disabledish' : '' ?>" <?= $disabled_attr ?>>

            </div>

            <div>

                <label for="team-id" class="font-medium text-gray-700"><?= __('team_login.team_id') ?></label>

                <input id="team-id" name="team_id" type="text" required maxlength="6" class="mt-2 w-full px-4 py-2.5 border rounded-lg <?= $form_disabled ? 'disabledish' : '' ?>" <?= $disabled_attr ?>>

            </div>

            <div>

                <label for="password" class="font-medium text-gray-700"><?= __('team_login.password') ?></label>

                <div class="password-wrapper">

                    <input id="password" name="password" type="password" required class="mt-2 w-full px-4 py-2.5 border rounded-lg <?= $form_disabled ? 'disabledish' : '' ?>" <?= $disabled_attr ?>>

                    <span id="password-toggle" class="password-toggle-icon"><i data-lucide="eye"></i></span>

                </div>

            </div>

            <div>

                <button id="submitBtn" type="submit" class="w-full bg-[#E5AE32] hover:bg-[#c4952b] text-white font-bold py-3 text-lg rounded-lg <?= $form_disabled ? 'disabledish' : '' ?>" <?= $disabled_attr ?>><?= __('team_login.login') ?></button>

            </div>

        </form>



        <!-- geri sayım için başlangıç değeri -->

        <div id="lock_ctx" data-remaining="<?= (int)$lock_seconds ?>"></div>

    </div>

</div>



<script>

    lucide.createIcons();



    // Şifre göster/gizle

    const passwordInput = document.getElementById('password');

    const passwordToggle = document.getElementById('password-toggle');

    if (passwordToggle) {

        passwordToggle.addEventListener('click', () => {

            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';

            passwordInput.setAttribute('type', type);

            passwordToggle.querySelector('i').setAttribute('data-lucide', type === 'password' ? 'eye' : 'eye-off');

            lucide.createIcons();

        });

    }



    // Kilit geri sayım ve form disable/enable

    (function(){

        const ctx = document.getElementById('lock_ctx');

        const form = document.getElementById('loginForm');

        if (!ctx || !form) return;



        let remaining = parseInt(ctx.dataset.remaining || '0', 10);

        const inputs = form.querySelectorAll('input, button');

        const timerEl = document.getElementById('lock-timer');



        function setDisabled(d) {

            inputs.forEach(el => {

                el.disabled = d;

                el.classList.toggle('disabledish', d);

            });

        }



        function tick() {

            if (remaining <= 0) {

                setDisabled(false);

                const notice = document.getElementById('lockNotice');

                if (notice) notice.remove();

                return;

            }

            if (timerEl) {

                const mm = String(Math.floor(remaining / 60)).padStart(2,'0');

                const ss = String(remaining % 60).padStart(2,'0');

                timerEl.textContent = mm + ':' + ss;

            }

            remaining--;

            setTimeout(tick, 1000);

        }



        if (remaining > 0) {

            setDisabled(true);

            tick();

        }

    })();

</script>



</body>

</html>

