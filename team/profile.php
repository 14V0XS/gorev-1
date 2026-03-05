<?php
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';


if (!isset($_SESSION['team_logged_in'])) {
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    header('Location: ' . $base . '/team-login.php');
    exit();
}

$pdo    = get_db_connection();
$teamId = (int) ($_SESSION['team_db_id'] ?? 0);
if ($teamId <= 0) { die('Geçersiz takım oturumu.'); }

/** Yardımcılar **/
function get_table_columns(PDO $pdo, string $table): array {
    $cols = [];
    try {
        $q = $pdo->query("DESCRIBE `$table`");
        foreach ($q as $row) { $cols[] = $row['Field']; }
    } catch (Throwable $e) { /* sessiz geç */ }
    return $cols;
}
function pick_col(array $columns, array $candidates): ?string {
    foreach ($candidates as $c) if (in_array($c, $columns, true)) return $c;
    return null;
}
function normalize_url(?string $u): string {
    $u = trim((string)$u);
    if ($u === '') return '';
    // Şema yoksa https ekle
    if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
    return $u;
}

/** Mevcut takım verisini çek (getTeam varsa onu kullan) **/
if (function_exists('getTeam')) {
    $team = getTeam($teamId);
} else {
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ? LIMIT 1");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$flash_success = null;
$flash_error   = null;

/** POST: Güncelle **/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in_csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $in_csrf)) {
        $flash_error = 'Oturum doğrulama (CSRF) başarısız.';
    } else {
        // Form alanlarını al + normalize et
        $display_name  = trim($_POST['display_name']  ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $website       = normalize_url($_POST['website']       ?? '');
        $bio           = trim($_POST['bio'] ?? '');
        $instagram_url = normalize_url($_POST['instagram_url'] ?? '');
        $youtube_url   = normalize_url($_POST['youtube_url']   ?? '');
        $linkedin_url  = normalize_url($_POST['linkedin_url']  ?? '');

        // Basit validasyon
        if ($display_name === '') {
            $flash_error = 'Görünen ad boş olamaz.';
        } elseif ($contact_email !== '' && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $flash_error = 'E-posta adresi geçersiz.';
        } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $flash_error = 'Web sitesi adresi geçersiz.';
        } elseif ($instagram_url !== '' && !filter_var($instagram_url, FILTER_VALIDATE_URL)) {
            $flash_error = 'Instagram linki geçersiz.';
        } elseif ($youtube_url !== '' && !filter_var($youtube_url, FILTER_VALIDATE_URL)) {
            $flash_error = 'YouTube linki geçersiz.';
        } elseif ($linkedin_url !== '' && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
            $flash_error = 'LinkedIn linki geçersiz.';
        }

        // Logo upload (isteğe bağlı)
        $logoRel = $team['logo'] ?? ($team['profile_pic_path'] ?? '');
        if ($flash_error === null && !empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $tmp  = $_FILES['logo']['tmp_name'];
                $size = (int) ($_FILES['logo']['size'] ?? 0);
                if ($size > 5 * 1024 * 1024) {
                    $flash_error = 'Logo 5MB sınırını aşıyor.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime  = $finfo->file($tmp);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($allowed[$mime])) {
                        $flash_error = 'Logo yalnızca JPG, PNG veya WEBP olabilir.';
                    } else {
                        $ext   = $allowed[$mime];
                        $dirFs = rtrim(str_replace('\\','/', $projectRoot), '/') . '/uploads/team_' . $teamId . '/';
                        $dirRel= 'uploads/team_' . $teamId . '/';
                        if (!is_dir($dirFs)) @mkdir($dirFs, 0775, true);
                        if (!is_dir($dirFs) || !is_writable($dirFs)) {
                            $flash_error = 'Yükleme klasörü oluşturulamadı veya yazılamıyor.';
                        } else {
                            $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            $destFs   = $dirFs . $filename;
                            if (@move_uploaded_file($tmp, $destFs)) {
                                $logoRel = $dirRel . $filename;
                            } else {
                                $flash_error = 'Logo yüklenemedi. Klasör izinlerini kontrol edin.';
                            }
                        }
                    }
                }
            } else {
                $flash_error = 'Logo yükleme hatası (kod: ' . (int)$_FILES['logo']['error'] . ').';
            }
        }

        // DB güncelle (kolonları dinamik eşle)
        if ($flash_error === null) {
            echo 'basarili';

            $cols = get_table_columns($pdo, 'teams');

            $map = [
                // form alanı => db kolon adayları (öncelik sırasıyla)
                'display_name'  => ['display_name', 'team_name', 'name'],
                'contact_email' => ['contact_email', 'email'],
                'website'       => ['website', 'site'],
                'bio'           => ['bio', 'about', 'description'],
                'instagram_url' => ['instagram_url', 'instagram'],
                'youtube_url'   => ['youtube_url', 'youtube'],
                'linkedin_url'  => ['linkedin_url', 'linkedin'],
                'logo'          => ['logo', 'profile_pic_path', 'avatar'],
            ];

            $updates = [];
            $params  = [':id' => $teamId];

            // display_name
            if ($col = pick_col($cols, $map['display_name']))  { $updates[] = "`$col` = :d";  $params[':d']  = $display_name; }
            // contact_email
            if ($col = pick_col($cols, $map['contact_email'])) { $updates[] = "`$col` = :e";  $params[':e']  = $contact_email; }
            // website
            if ($col = pick_col($cols, $map['website']))       { $updates[] = "`$col` = :w";  $params[':w']  = $website; }
            // bio
            if ($col = pick_col($cols, $map['bio']))           { $updates[] = "`$col` = :b";  $params[':b']  = $bio; }
            // instagram
            if ($col = pick_col($cols, $map['instagram_url'])) { $updates[] = "`$col` = :iu"; $params[':iu'] = $instagram_url; }
            // youtube
            if ($col = pick_col($cols, $map['youtube_url']))   { $updates[] = "`$col` = :yu"; $params[':yu'] = $youtube_url; }
            // linkedin
            if ($col = pick_col($cols, $map['linkedin_url']))  { $updates[] = "`$col` = :li"; $params[':li'] = $linkedin_url; }
            // logo
            if ($logoRel !== '' && ($col = pick_col($cols, $map['logo']))) {
                $updates[] = "`$col` = :l"; $params[':l'] = $logoRel;
            }
            if (!empty($updates)) {
                $sql = "UPDATE `teams` SET " . implode(', ', $updates) . " WHERE id = :id";
                try {
                    $upd = $pdo->prepare($sql);
                    $upd->execute($params);
                    $flash_success = 'Profil başarıyla güncellendi.';

                    // Güncel veriyi tekrar çek
                    if (function_exists('getTeam')) {
                        $team = getTeam($teamId);
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ? LIMIT 1");
                        $stmt->execute([$teamId]);
                        $team = $stmt->fetch(PDO::FETCH_ASSOC) ?: $team;
                    }
                } catch (PDOException $e) {
                    $flash_error = 'Güncelleme başarısız: ' . $e->getMessage();
                }
            } else {
                $flash_error = 'Güncellenecek uygun kolon bulunamadı. (teams şemanı kontrol et)';
            }
        }
    }
}

/** Sayfa altındaki görseller ve placeholderlar için yardımcı değişkenler **/
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
// Logo/pp alanı hem `logo` hem `profile_pic_path` olabilir:
$logoPath = $team['logo'] ?? ($team['profile_pic_path'] ?? '');
$logoUrl  = $logoPath ? ($base . '/' . ltrim($logoPath, '/')) : ($base . '/assets/images/default.jpg'); // .jgp düzeltildi
$teamNumber = htmlspecialchars((string) ($_SESSION['team_number'] ?? ''));

require_once 'team_header.php';
?>

    <style>
    /* --- Mevcut Stiller --- */
    .form-card {
        display: flex;
        gap: 24px;
    }

    .form-main {
        flex: 1 1 0;
    }

    .form-aside {
        width: 320px;
        max-width: 100%;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-grid .col-span-2 {
        grid-column: span 2;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .label {
        font-weight: 700;
        font-size: 14px;
    }

    .input,
    .textarea,
    .file {
        width: 100%;
        background: #fff;
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px 12px;
    }

    .input:focus,
    .textarea:focus,
    .file:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 4px rgba(229, 174, 50, .15);
    }

    .help {
        font-size: 12px;
        color: var(--muted);
    }

    .actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .alert {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--border);
        margin-bottom: 16px;
    }

    .alert.success {
        background: #f0fdf4;
    }

    .alert.error {
        background: #fef2f2;
    }

    .btn {
        border: none; /* Butonlarda kenarlık olmasın */
        cursor: pointer;
    }

    .btn:hover {
        opacity: 0.8;
    }

    .btn.btn-secondary {
        background: #fff;
        border-color: var(--border);
        color: var(--text);
    }

    .btn.btn-secondary:hover {
        background: #f8f8f8;
    }

    #logo {
        display: none;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-card {
        background: #fff;
        padding: 24px;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .modal-card h3 {
        margin-top: 0;
    }

    .modal-changes {
        margin-top: 16px;
        margin-bottom: 24px;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--border);
        padding: 12px;
        border-radius: 8px;
        font-size: 14px;
    }

    .modal-changes p {
        margin: 8px 0;
    }

    .old-data {
        color: #ef4444;
        text-decoration: line-through;
    }

    .new-data {
        color: #22c55e;
        font-weight: bold;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    .textarea {
        min-height: 160px;
        resize: vertical;
    }
    
    /* YENİ: Logo kartı için yatay düzen */
    .logo-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .logo-preview {
        width: 100px;
        height: 100px;
        border-radius: 18px;
        object-fit: cover;
        border: 1px solid var(--border);
        background: #fff;
        display: block;
        flex-shrink: 0;
    }

    .logo-actions {
        text-align: left;
    }

    .change-logo-label {
        display: inline-block;
        padding: 8px 16px;
        background-color: #f0f0f0;
        border: 1px solid var(--border);
        border-radius: 12px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }

    .change-logo-label:hover {
        background-color: #e5e5e5;
    }

    .logo-card .help {
        margin-top: 8px;
        display: block;
    }

    /* MENÜ DEĞİŞİKLİKLERİ */
    .sidebar-header {
        text-align: center; /* Takım Paneli yazısını ortala */
    }

    /* BİLDİRİM BUTONU STİLLERİ */
    .notification-button {
        position: relative;
        background: none;
        border: none;
        color: black;
        cursor: pointer;
        padding: 0;
    }

    .notification-button .lucide {
        color: black; /* İkon rengini siyah yap */
        width: 24px;
        height: 24px;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ffc107;
        color: black;
        font-size: 10px;
        font-weight: bold;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid white;
    }
    .menu-label-with-badge{display:inline-flex;align-items:center;gap:8px}
    .menu-badge{
        background:#ef4444;color:#fff;border-radius:9999px;
        font-size:11px;line-height:1;padding:4px 6px;min-height: 15px;
        display:inline-flex;align-items:center;justify-content:center;
        font-weight:600;
    }
    /* ====== LAYOUT (aynı panel sayfasındaki gibi) ====== */
    :root{ --sidebar-w: 280px; }

    body, html { height: 100%; }
    .main-wrapper{
        display:grid;
        grid-template-columns: var(--sidebar-w) 1fr;
        min-height: 100vh;
    }

    /* Hamburger: küçük ekranda görünür, büyükte gizle */
    #sidebarToggle { display: inline-flex; }
    @media (min-width: 1024px){
        #sidebarToggle { display: none; }
    }

    /* Büyük ekran: sidebar sabit */
    @media (min-width: 1024px){
        .sidebar{
            position: sticky;
            top: 0;
            height: 100vh;
            transform: translateX(0) !important;
            transition: none;
            box-shadow: inset -1px 0 0 rgba(0,0,0,.05);

        }
        .sidebar-overlay{ display:none !important; }
    }

    /* Küçük ekran: sidebar çekme menü */
    @media (max-width: 1023px){
        .main-wrapper{ grid-template-columns: 1fr; }

        .sidebar{
            position: fixed;
            inset: 0 auto 0 0;
            width: 50vw;
            max-width: 85vw;
            background: #fff;
            z-index: 50;
            border-right: 1px solid #e5e7eb;
            transform: translateX(-100%);
            transition: transform .25s ease;
            overflow-y: auto;
        }
        .sidebar.open{ transform: translateX(0); }

        .sidebar-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            backdrop-filter: blur(1px);
            z-index: 40;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }
        .sidebar-overlay.show{
            opacity: 1;
            pointer-events: auto;
        }

        .top-bar{
            position: sticky;
            top: 0;
            z-index: 30;
            background: #fff;
        }
    }

    /* Üst bar ve genel boşluklar */
    .top-bar{
        display:flex; align-items:center; justify-content:space-between;
        padding: 12px 16px; border-bottom: 1px solid #eee;
    }
    .main-content{ padding: 16px; }
    @media (min-width: 1024px){ .main-content{ padding: 24px; } }

    /* ====== TIPOGRAFI — okunaklılık ====== */
    html { font-size: 16px; }
    @media (min-width: 1280px){ html { font-size: 17px; } }
    body { font-size: 1rem; line-height: 1.6; }

    .page-header h1{
        font-size: clamp(1.25rem, 2.2vw, 1.6rem);
        line-height: 1.3;
        margin: 0 0 14px;
    }
    .sidebar-nav a{
        display:flex; align-items:center; gap:10px;
        font-size: 1rem; padding: 12px 14px;
    }
    .sidebar-nav i.lucide{ width: 20px; height: 20px; flex: 0 0 20px; }

    .btn{
        font-size: 0.98rem;
        padding: 10px 14px;
        border-radius: 10px;
        min-height: 38px;
    }
    .btn.btn-sm{
        font-size: 0.95rem;
        padding: 8px 12px;
        min-height: 34px;
    }
    .btn i.lucide{ width: 18px; height: 18px; }

    /* Bildirim ikonu biraz büyük */
    .notification-button .lucide{ width: 22px; height: 22px; }

    /* ====== FORM KIRILIMLARI ====== */
    .form-card{
        display:flex; gap: 24px;
        align-items: stretch;
    }
    .form-main{ flex: 1 1 0; }

    /* Sağ kartın masaüstünde sabit genişliği */
    .form-aside{ width: 320px; max-width: 100%; }

    /* Grid: mobilde tek sütun, md ile iki sütun */
    .form-grid{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .form-grid .col-span-2{ grid-column: span 2; }

    @media (max-width: 1023px){
        .form-card{ flex-direction: column; }
        .form-grid{ grid-template-columns: 1fr; }
        .form-grid .col-span-2{ grid-column: span 1; } /* tek sütunda doğal davranış */
    }

    /* Form alanları */
    .label{ font-weight:700; font-size: 14px; }
    .input, .textarea, .file{
        width: 100%; background: #fff; color: var(--text);
        border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px;
    }
    .input:focus, .textarea:focus, .file:focus{
        outline: none; border-color: var(--brand);
        box-shadow: 0 0 0 4px rgba(229, 174, 50, .15);
    }
    .textarea{ min-height: 160px; resize: vertical; }
    .help{ font-size: 12px; color: var(--muted); }

    /* Logo kartı yan yana kalsın, mobilde de iyi dursun */
    .logo-card{
        background:#fff; border:1px solid var(--border); border-radius:16px;
        padding:16px; display:flex; align-items:center; gap:16px;
    }
    .logo-preview{ width:100px; height:100px; border-radius:18px; object-fit:cover; border:1px solid var(--border); }
    .change-logo-label{
        display:inline-block; padding:8px 16px; background:#f0f0f0;
        border:1px solid var(--border); border-radius:12px; cursor:pointer;
        font-weight:600; font-size:14px;
    }
    .change-logo-label:hover{ background:#e5e5e5; }
    #logo{ display:none; }

    @media (max-width: 640px){
        body{ font-size: 1.02rem; }
        .btn{ min-height: 40px; }
        .sidebar-nav a{ padding: 12px 16px; }
        .top-bar{ padding: 12px 14px; }
    }

    </style>

<div class="main-wrapper">

<aside class="sidebar">
    <?php
    if (!isset($_SESSION['admin_panel_view'])):
        ?>
        <a class="flex items-center space-x-2" href="<?php echo BASE_URL; ?>">
            <span class="rookieverse">FRC ROOKIEVERSE</span>
        </a>
    <?php endif;?>
    <div class="sidebar-profile">
        <h2>Hoş Geldin,</h2>
        <p>Takım #<?php echo $teamNumber; ?></p>
    </div>
    <nav class="sidebar-nav">
        <a href="panel.php"><i data-lucide="layout-dashboard"></i> Panelim</a>
        <a href="create_course.php"><i data-lucide="plus-square"></i> Yeni Kurs Oluştur</a>
        <a href="profile.php" class="active"><i data-lucide="settings"></i> Profilimi Düzenle</a>
        <a href="notifications.php"><i data-lucide="bell"></i> Bildirimler<?php if ($unreadTotal > 0): ?><span class="menu-badge"><?php echo $unreadTotal; ?></span><?php endif; ?></a>
        <a href="list_questions.php" ><i data-lucide="message-square"></i> Soru Yönetimi</a>
        <a href="support_requests.php"><i data-lucide="life-buoy"></i> Destek</a>

        <?php
        if (!isset($_SESSION['admin_panel_view'])):
            ?>
            <a href="logout.php" class="logout-link"><i data-lucide="log-out"></i> Güvenli Çıkış</a>
        <?php endif;?>
    </nav>
</aside>

<main style="margin-left: 0px; padding: 0px" class="main-content">
    <div class="top-bar">
        <button style="background-color: white" id="sidebarToggle"
                class="inline-flex items-center justify-center w-10 h-10 rounded-md border"
                aria-label="Menüyü aç/kapat" type="button">
            <i data-lucide="menu"></i>
        </button>
        <div class="font-bold">Takım #<?php echo $teamNumber; ?> Profil Ayarları</div>
        <div class="actions">
            <button id="notif-button" class="notification-button">
                <i data-lucide="bell"></i>
                <?php if ($unreadTotal > 0): ?>
                    <div class="notification-badge"><?= htmlspecialchars($unreadTotal) ?></div>
                <?php endif; ?>
            </button>

            <?php
            if (isset($_SESSION['admin_panel_view'])):
                ?>
                <a href="../admin/accessTeamPanel.php?exit=1" class="btn btn-sm"><i data-lucide="arrow-left"></i>Admin Paneline Dön</a>
            <?php endif;?>
        </div>
    </div>

    <div class="content-area">
        <div class="page-header">
            <h1>Profilimi Düzenle</h1>
        </div>

        <?php if ($flash_success) : ?>
            <div class="alert success"><strong>Başarılı:</strong> <?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if ($flash_error) : ?>
            <div class="alert error"><strong>Hata:</strong> <?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

            <div class="form-card">
                <div class="form-main">
                    <div class="form-grid">
                        <div class="field">
                            <label class="label" for="display_name">Görünen Ad</label>
                            <input class="input" type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($team['team_name'] ?? ''); ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="contact_email">İletişim E-postası</label>
                            <input class="input" type="email" id="contact_email" name="contact_email" placeholder="ornek@takim.com" value="<?php echo htmlspecialchars($team['email'] ?? ''); ?>">
                        </div>
                        <div class="field col-span-2">
                            <label class="label" for="bio">Hakkınızda</label>
                            <textarea class="textarea" id="bio" name="bio" placeholder="Takımınız hakkında kısa bir açıklama yazın..." maxlength="1000"><?php echo htmlspecialchars($team['bio'] ?? ''); ?></textarea>
                            <span class="help">En fazla 1000 karakter önerilir.</span>
                        </div>
                    </div>
                </div>

                <div class="form-aside">
                    <div class="logo-card">
                        <img src="<?= htmlspecialchars($logoUrl) ?>" class="logo-preview" id="logoPreview" alt="Takım Logosu">
                        <div class="logo-actions">
                            <label for="logo" class="change-logo-label">
                                <i data-lucide="upload-cloud" style="width:16px; height:16px; vertical-align:middle; margin-right:4px;"></i> Değiştir
                            </label>
                            <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/webp" class="file">
                            <span class="help">JPG, PNG, WEBP (max 5MB)</span>
                        </div>
                    </div>

                    <div class="card" style="margin-top:16px;">
                        <div class="field">
                            <label class="label">Takım Numarası</label>
                            <input class="input" value="<?= $team['team_number']; ?>" disabled>
                            <span class="help">Bu alan sistem tarafından belirlenir.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 24px;">
                <h3 style="margin-top:0; margin-bottom: 16px; border-bottom:1px solid var(--border); padding-bottom:12px;">Sosyal Medya Linkleri</h3>
                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="website">Web Sitesi</label>
                        <input class="input" type="url" id="website" name="website" placeholder="https://takimim.com" value="<?php echo htmlspecialchars($team['website'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label class="label" for="instagram_url">Instagram</label>
                        <input class="input" type="url" id="instagram_url" name="instagram_url" placeholder="https://instagram.com/takimim" value="<?php echo htmlspecialchars($team['instagram'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label class="label" for="youtube_url">YouTube</label>
                        <input class="input" type="url" id="youtube_url" name="youtube_url" placeholder="https://youtube.com/c/takimim" value="<?php echo htmlspecialchars($team['youtube'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label class="label" for="linkedin_url">LinkedIn</label>
                        <input class="input" type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/company/takimim" value="<?php echo htmlspecialchars($team['linkedin'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="actions" style="margin-top:24px; border-top: 1px solid var(--border); padding-top:16px;">
                <button type="submit" class="btn"><i data-lucide="save"></i> Değişiklikleri Kaydet</button>
                <a href="panel.php" class="btn btn-sm btn-secondary">İptal</a>
            </div>
        </form>
    </div>

    <div class="modal-overlay" id="confirmationModal">
        <div class="modal-card">
            <h3>Değişiklikleri Onaylıyor Musunuz?</h3>
            <p>Aşağıdaki değişiklikler kalıcı olarak kaydedilecektir:</p>
            <div class="modal-changes" id="changesSummary"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-sm btn-secondary" id="cancelBtn">İptal Et</button>
                <button type="button" class="btn" id="confirmBtn"><i data-lucide="check-circle"></i> Onayla ve Kaydet</button>
            </div>
        </div>
    </div>
</main>
</div>
<div class="sidebar-overlay"></div>


<script>
    try {
        if (window.lucide?.createIcons) {
            lucide.createIcons();
        }
    } catch (e) {
        console.error("Lucide icons could not be created.", e);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('profileForm');
        if (!form) return;

        const modal = document.getElementById('confirmationModal');
        const changesSummary = document.getElementById('changesSummary');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logoPreview');

        logoInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        const initialData = {
            display_name: form.querySelector('#display_name').value.trim(),
            contact_email: form.querySelector('#contact_email').value.trim(),
            website: form.querySelector('#website').value.trim(),
            bio: form.querySelector('#bio').value.trim(),
            instagram_url: form.querySelector('#instagram_url').value.trim(),
            youtube_url: form.querySelector('#youtube_url').value.trim(),
            linkedin_url: form.querySelector('#linkedin_url').value.trim(),
        };

        const fieldLabels = {
            display_name: 'Görünen Ad',
            contact_email: 'İletişim E-postası',
            website: 'Web Sitesi',
            bio: 'Biyografi',
            instagram_url: 'Instagram',
            youtube_url: 'YouTube',
            linkedin_url: 'LinkedIn',
            logo: 'Takım Logosu'
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const currentData = {
                display_name: form.querySelector('#display_name').value.trim(),
                contact_email: form.querySelector('#contact_email').value.trim(),
                website: form.querySelector('#website').value.trim(),
                bio: form.querySelector('#bio').value.trim(),
                instagram_url: form.querySelector('#instagram_url').value.trim(),
                youtube_url: form.querySelector('#youtube_url').value.trim(),
                linkedin_url: form.querySelector('#linkedin_url').value.trim()
            };
            const currentLogoInput = form.querySelector('#logo');
            let changesHtml = '';
            let hasChanges = false;

            for (const key in initialData) {
                if (initialData[key] !== currentData[key]) {
                    hasChanges = true;
                    changesHtml += `<p>
                            <strong>${fieldLabels[key]}:</strong>
                            <span class="old-data">${initialData[key] || 'Boş'}</span> →
                            <span class="new-data">${currentData[key] || 'Boş'}</span>
                        </p>`;
                }
            }

            if (currentLogoInput.files.length > 0) {
                hasChanges = true;
                changesHtml += `<p>
                            <strong>${fieldLabels.logo}:</strong>
                            <span class="new-data">Yeni bir dosya seçildi (${currentLogoInput.files[0].name})</span>
                        </p>`;
            }

            if (hasChanges) {
                changesSummary.innerHTML = changesHtml;
                modal.style.display = 'flex';
            } else {
                alert('Kaydedilecek bir değişiklik bulunamadı.');
            }
        });

        confirmBtn.addEventListener('click', () => {
            form.submit();
        });
        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    document.querySelector('#notif-button').addEventListener('click', ()=>{
        window.location.href = 'notifications.php';
    })
    try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}

    (function(){
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const toggle  = document.getElementById('sidebarToggle');

        if(!sidebar || !toggle || !overlay) return;

        const open = () => {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            try { lucide.createIcons(); } catch(_){}
        };
        const close = () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        };

        toggle.addEventListener('click', () => {
            if(sidebar.classList.contains('open')) close(); else open();
        });
        overlay.addEventListener('click', close);

        document.addEventListener('keydown', (e)=>{
            if(e.key === 'Escape' && sidebar.classList.contains('open')) close();
        });

        // Küçük ekranda menü linkine tıklayınca kapan
        sidebar.querySelectorAll('a').forEach(a=>{
            a.addEventListener('click', ()=>{
                if (window.matchMedia('(max-width: 1023px)').matches){ close(); }
            });
        });
    })();
</script>


<?php require_once __DIR__ . '/../admin/admin_footer.php'; ?>