<?php
$projectRoot = dirname(__DIR__);
require_once($projectRoot . '/config.php');
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);$page_title = "Takım Yönetimi";
$pdo = get_db_connection();
$teams = $pdo->query("SELECT id, team_number, team_id_generated, team_name FROM teams ORDER BY team_number ASC")->fetchAll(PDO::FETCH_ASSOC);

// HTML kısmı başlıyor
require_once 'admin_header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-color: #FBBF24; /* Sarı */
            --primary-dark-color: #F59E0B; /* Koyu Sarı */
            --secondary-color: #2DD4BF;
            --background-color: #F8FAFC;
            --card-background-color: #FFFFFF;
            --text-color: #1F2937;
            --border-color: #E5E7EB;
            --danger-color: #EF4444;
            --info-color: #6B7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-background-color);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s, transform 0.2s;
            white-space: nowrap;
        }
        .btn:hover {
            background-color: var(--primary-dark-color);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-info {
            background-color: var(--info-color);
        }
        .btn-info:hover {
            background-color: #4B5563;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #DC2626;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 1rem;
        }

        thead th {
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            font-size: 0.875rem;
            padding: 0 1rem 0.75rem 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            vertical-align: middle;
        }

        tbody tr {
            background-color: var(--card-background-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

        td.actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        td.actions form {
            margin: 0;
        }

        /* Modal Stilleri */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            position: relative;
            border-style: dashed;
            border-width: 2px;
            animation: fadeIn 0.3s ease-out;
            text-align: center;
        }
        .modal-content .icon {
            margin: 0 auto 1rem;
            width: 3rem;
            height: 3rem;
        }
        .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #9CA3AF;
            transition: color 0.2s;
        }
        .modal-close-btn:hover {
            color: #4B5563;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-alert-success {
            border-color: var(--secondary-color);
        }
        .modal-alert-warning {
            border-color: #FDE68A;
        }

        input {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.875rem 1.25rem;
            width: 100%;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .copy-icon {
            cursor: pointer;
            color: #6B7280;
            transition: color 0.2s;
            margin-left: 0.5rem;
        }
        .copy-icon:hover {
            color: var(--primary-dark-color);
        }

        @media (max-width: 768px) {
            .main-content { padding: 1.5rem 1rem; }
            .page-header h1 { font-size: 2rem; }
            .card { padding: 1.5rem; }
            table, thead, tbody, th, td, tr { display: block; width: 100%; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1.5rem; padding: 1rem; background-color: white; }
            td { border: none; position: relative; padding: 0.5rem 0; display: flex; justify-content: space-between; align-items: center; }
            td:before { content: attr(data-label); font-weight: bold; text-transform: uppercase; color: #6B7280; margin-right: 1rem; }
            td.actions:before { display: none; }
            td.actions { border-top: 1px solid var(--border-color); margin-top: 1rem; padding-top: 1rem; justify-content: flex-start; }
            .modal-content { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<?php require_once 'admin_sidebar.php'; ?>

<div style="width: 100%" class="page-container">
    <main class="main-content">
        <div class="content-area">
            <div class="page-header mb-8 flex flex-col">
    <h1 class="text-4xl font-extrabold text-gray-800">Takım Yönetimi</h1>
    <h2 class="text-gray-600 mt-2 text-sm">
        Takım listelerini yönetebilir, yeni takımlar ekleyebilir veya mevcut takımları silebilirsiniz.</h2>
</div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="card lg:col-span-3 flex flex-col md:flex-row md:items-end gap-6">
                    <form action="team_actions.php" method="POST" class="flex-grow flex flex-col md:flex-row md:items-end gap-6 w-full">
                        <input type="hidden" name="action" value="add_team">
                        <div class="flex-grow">
                            <label for="team_number" class="font-medium text-gray-600">Takım Numarası</label>
                            <input id="team_number" name="team_number" type="number" required placeholder="Örn: 6228" class="mt-2">
                        </div>
                        <button type="submit" class="btn h-14 md:w-auto w-full">
                            <i data-lucide="user-plus" class="mr-2"></i> Takım Oluştur
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2 class="text-2xl font-semibold mb-6 text-gray-700">Kayıtlı Takımlar</h2>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Takım No</th>
                                <th>Takım Adı</th>
                                <th>Takım ID</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teams)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-8">
                                        Henüz kayıtlı takım bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($teams as $team): ?>
                                    <tr>
                                        <td data-label="Takım No"><strong>#<?php echo htmlspecialchars($team['team_number']); ?></strong></td>
                                        <td data-label="Takım Adı"><?php echo htmlspecialchars($team['team_name'] ?: 'Belirtilmemiş'); ?></td>
                                        <td data-label="Takım ID">
                                            <div class="flex items-center gap-2">
                                                <code id="team-id-<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_id_generated']); ?></code>
                                                <i data-lucide="copy" class="copy-icon" onclick="copyToClipboard('<?php echo htmlspecialchars($team['team_id_generated']); ?>')"></i>
                                            </div>
                                        </td>
                                        <td data-label="İşlemler" class="actions justify-end">
                                            <a href="accessTeamPanel.php?team_id=<?php echo htmlspecialchars($team['id']); ?>" class="btn btn-sm btn-info">
                                                <i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>Panele Git
                                            </a>
                                            <form action="team_actions.php" method="POST" onsubmit="return confirm('Bu takımı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                                <input type="hidden" name="action" value="delete_team">
                                                <input type="hidden" name="team_db_id" value="<?php echo $team['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>Sil
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if (isset($_GET['fail'])): ?>
<div id="warningModal" class="modal-overlay">
    <div class="modal-content modal-alert-warning">
        <button onclick="closeModal('warningModal')" class="modal-close-btn">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        <div class="icon">
            <i data-lucide="alert-triangle" class="w-full h-full text-yellow-500"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">Uyarı!</h3>
        <p class="mt-4 text-gray-600">
            <strong>#<?php echo htmlspecialchars($_GET['number']); ?></strong> numaralı takım zaten mevcut. Lütfen farklı bir numara deneyin.
        </p>
    </div>
</div>
<?php endif; ?>

<?php if(isset($_GET['new_team_info'])):
    $info = json_decode(urldecode($_GET['new_team_info']), true);
?>
<div id="successModal" class="modal-overlay">
    <div class="modal-content modal-alert-success">
        <button onclick="closeModal('successModal')" class="modal-close-btn">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        <div class="icon">
            <i data-lucide="check-circle" class="w-full h-full text-green-500"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">Takım Oluşturuldu!</h3>
        <p class="mt-4 text-gray-600">
            <strong>Takım #<?php echo htmlspecialchars($info['number']); ?></strong> başarıyla oluşturuldu.
        </p>
        <div class="mt-6 text-left">
            <h4 class="font-semibold text-gray-700">Takım Bilgileri</h4>
            <div class="bg-gray-100 p-4 rounded-lg mt-2 font-mono text-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <span>Takım ID:</span>
                    <div class="flex items-center gap-2">
                        <code id="team-id-code"><?php echo htmlspecialchars($info['id']); ?></code>
                        <i data-lucide="copy" class="copy-icon" onclick="copyToClipboard('<?php echo htmlspecialchars($info['id']); ?>')"></i>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1">
                    <span>Şifre:</span>
                    <div class="flex items-center gap-2">
                        <code id="team-password-code"><?php echo htmlspecialchars($info['password']); ?></code>
                        <i data-lucide="copy" class="copy-icon" onclick="copyToClipboard('<?php echo htmlspecialchars($info['password']); ?>')"></i>
                    </div>
                </div>
            </div>
        </div>
        <button onclick="copyAllTeamInfo()" class="btn w-full mt-6">
            <i data-lucide="copy" class="mr-2"></i> Bilgileri Kopyala
        </button>
    </div>
</div>
<?php endif; ?>

<?php require_once 'admin_footer.php'; ?>
<script>
    lucide.createIcons();

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text)
            .then(() => {
                alert('Panoya kopyalandı: ' + text);
            })
            .catch(err => {
                console.error('Kopyalama işlemi başarısız oldu:', err);
                alert('Kopyalama işlemi başarısız oldu.');
            });
    }

    function copyAllTeamInfo() {
        const teamNumber = "<?php echo isset($info['number']) ? htmlspecialchars($info['number']) : ''; ?>";
        const teamId = document.getElementById('team-id-code').innerText;
        const teamPassword = document.getElementById('team-password-code').innerText;

        const message = `Değerli ${teamNumber} takımı, Rookiverse projemize destek verdiğiniz için teşekkürler. Kullanıcı bilgileriniz aşağıda yer almaktadır:\n\nTakım ID: ${teamId}\nŞifre: ${teamPassword}\n\nBu bilgileri kullanarak Rookiverse'e giriş yapabilirsiniz. Herhangi bir sorun durumunda bizlerle iletişime geçebilirsiniz.`;

        navigator.clipboard.writeText(message)
            .then(() => {
                alert('Bilgiler panoya kopyalandı!');
                closeModal('successModal');
            })
            .catch(err => {
                console.error('Kopyalama işlemi başarısız oldu:', err);
                alert('Kopyalama işlemi başarısız oldu.');
            });
    }
</script>
</body>
</html>