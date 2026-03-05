<?php
$pdo = get_db_connection();

$pending_approvals = $pdo->query("SELECT count(*) FROM courses WHERE status='pending'")->fetchColumn();
$pending_approvals += $pdo->query("SELECT count(*) FROM course_modules WHERE status='pending' OR status='draft'")->fetchColumn();

// Rozette aşırı uzun görünmesin diye:
$pending_badge = $pending_approvals > 99 ? '99+' : (string)$pending_approvals;



?>

<style>
    .menu-label-with-badge{display:inline-flex;align-items:center;gap:8px}
    .menu-badge{
        background:#ef4444;color:#fff;border-radius:9999px;
        font-size:11px;line-height:1;padding:4px 6px;min-height: 15px;
        display:inline-flex;align-items:center;justify-content:center;
        font-weight:600;
    }
    .submenu-note{color:#6b7280;font-size:12px;padding:6px 12px;display:block}
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <a style="text-decoration: none" href="../index.php"><span class="rookieverse">FRC ROOKIEVERSE</span></a>
    </div>

    <nav class="sidebar-nav">
        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="home" class="menu-icon"></i>Dashboard
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu"><a href="panel.php" class="<?php echo ($current_page == 'panel.php') ? 'active' : ''; ?>">Kontrol Paneli</a></div>
        </div>

        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="users" class="menu-icon"></i>Takımlar
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu"><a href="team_actions.php">Takımları Görüntüle</a></div>
        </div>

        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="book-open" class="menu-icon"></i>Kurslar
                    <?php if ($pending_approvals > 0): ?>
                        <span class="menu-badge"><?php echo $pending_badge; ?></span>
                    <?php endif; ?>
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu">
                <a href="course_actions.php">Kursları Görüntüle</a>

            </div>
            <div class="submenu">
                <a href="delete_requests.php">Kurs Silme Talepleri</a>

            </div>
        </div>

        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="layers-2" class="menu-icon"></i>Kategoriler
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu">
                <a href="manage_categories.php">Kategorileri Yönet</a>

            </div>

        </div>

        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="user-cog" class="menu-icon"></i>Admin Yönetimi
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu"><a href="admin_manage.php">Adminleri Yönet</a><a href="logout.php">Çıkış Yap</a></div>
        </div>
        <div class="menu-item">
            <div class="menu-title">
                <div class="menu-label-with-badge">
                    <i data-lucide="send" class="menu-icon"></i>İletişim
                </div>
                <i data-lucide="chevron-right" class="menu-arrow"></i>
            </div>
            <div class="submenu"><a href="contact.php">Kullanıcı Mesajları</a></div>
            <div class="submenu"><a href="team_support.php">Takım Destek Mesajları</a></div>
        </div>
    </nav>
</aside>
