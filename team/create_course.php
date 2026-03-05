<?php
// TARAYICIYA ÖNBELLEKLEME YAPMAMASINI SÖYLE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_start();
// ... kodunuzun geri kalanı ...
// Dosyanın en başındaki PHP kodunu bu şekilde güncelleyin

if (!isset($_SESSION['team_logged_in']) || !isset($_SESSION['team_db_id'])) { 
    header('Location: ../team-login.php?error=session_expired'); exit(); 
}
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

$pdo = get_db_connection();
// Sadece durumu 'approved' olan kategorileri çek
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'approved'")->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Yeni Kurs Oluştur - Adım 1";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/create_course.css">
</head>
<body class="bg-gray-100">
<?php require_once $projectRoot . '/navbar.php'; ?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">1. Adım: Kurs Bilgilerini Girin</h1>
        <a href="panel.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300">İptal</a>
    </div>
    
    

    <form id="course-form" action="save_course_step1.php" method="POST">
        <div class="input-card"><label for="title">Kurs Adı</label><input type="text" id="title" name="title" class="form-input" placeholder="Kursunuza dikkat çekici bir başlık verin..." required></div>
        <div class="input-card"><label for="about">Kurs Hakkında</label><textarea id="about" name="about_text" class="form-textarea" placeholder="Bu kursta nelerin anlatıldığını genel olarak açıklayın..." required></textarea></div>
        <div class="input-card"><label for="goal">Kurs Amacı</label><textarea id="goal" name="goal_text" class="form-textarea" placeholder="Bu kursu tamamlayan bir öğrencinin neyi başarmış olacağını anlatın..." required></textarea></div>
        <div class="input-card"><label for="learnings">Ne Öğreteceksiniz?</label><textarea id="learnings" name="learnings_text" class="form-textarea" placeholder="Öğreteceğiniz konuları madde madde yazın (her satır yeni bir madde)..." required></textarea></div>
        
        <div class="input-card">
            <label for="category_id">Kategori</label>
            <div class="flex flex-col sm:flex-row gap-4 items-center">
                <select id="category_id" name="category_id" class="form-select w-full" required>
                    <option value="" disabled selected>Lütfen bir kategori seçin...</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="openPopup('category-popup')" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg whitespace-nowrap text-sm">Kategori Öner</button>
            </div>
        </div>

        <div class="input-card">
            <label for="level">Seviye</label>
            <select id="level" name="level" class="form-select w-full" required>
                <option value="" disabled selected>Lütfen bir seviye seçin...</option>
                <option value="Başlangıç">Başlangıç</option>
                <option value="Orta">Orta</option>
                <option value="İleri">İleri</option>
            </select>
        </div>

        <div class="input-card">
            <label for="comp"><?= __('create_course.competition') ?></label>
            <select id="comp" name="comp" class="form-select w-full" required>
                <option value="" disabled selected><?= __('create_course.competition_placeholder') ?></option>
                <option value="FRC">FRC (FIRST Robotics Competition)</option>
                <option value="FTC">FTC (FIRST Tech Challenge)</option>
                <option value="FLL">FLL (FIRST LEGO League)</option>
            </select>
        </div>

        <div class="input-card">
            <label for="language"><?= __('create_course.language') ?></label>
            <select id="language" name="language" class="form-select w-full" required>
                <option value="" disabled selected><?= __('create_course.language_placeholder') ?></option>
                <option value="tr">🇹🇷 <?= __('common.turkish') ?></option>
                <option value="en">🇬🇧 <?= __('common.english') ?></option>
            </select>
        </div>

        <div class="text-right mt-8">
            <button type="submit" class="btn text-lg"><i data-lucide="arrow-right" class="mr-2"></i> Kaydet ve 2. Adıma Geç</button>
        </div>
    </form>
</div>

<!-- Kategori Önerme Pop-up'ı -->
<div id="category-popup" class="popup-overlay">
    <div class="popup-content">
        <h2 class="text-2xl font-bold mb-4">Yeni Kategori Öner</h2>
        <div class="mb-4">
            <label for="new_category_name" class="block text-left font-semibold text-gray-700 mb-2">Önerdiğiniz Kategori Adı</label>
            <input type="text" id="new_category_name" class="form-input w-full" placeholder="Yeni kategori adı...">
            <div id="category-error-message" class="error-text"></div>

        </div>
        <div class="flex gap-4 mt-6">
            <button type="button" onclick="submitCategory()" class="btn w-full">Öneriyi Gönder</button>
            <button type="button" onclick="closePopup('category-popup')" class="w-full btn bg-gray-300 text-gray-800 hover:bg-gray-400">İptal</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    const form = document.getElementById('course-form');
    let isDirty = false;
    form.addEventListener('input', () => { isDirty = true; });
    window.addEventListener('beforeunload', (e) => {
        if (isDirty) {
            const confirmationMessage = 'Bazı bilgileri kaydetmediniz. Çıkış yaparsanız silinecektir.';
            (e || window.event).returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
    form.addEventListener('submit', () => { isDirty = false; });

    // Pop-up fonksiyonları
    const openPopup = (id) => document.getElementById(id).classList.add('show');
    const closePopup = (id) => {
        document.getElementById(id).classList.remove('show');
        // Pop-up kapandığında hata mesajını temizle
        const errorDiv = document.getElementById('category-error-message');
        const categoryNameInput = document.getElementById('new_category_name');
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
        categoryNameInput.classList.remove('input-error');
    };

    // Kategori input alanına yazılmaya başlandığında hatayı temizle
    document.getElementById('new_category_name').addEventListener('input', (e) => {
        const errorDiv = document.getElementById('category-error-message');
        errorDiv.style.display = 'none';
        e.target.classList.remove('input-error');
    });

    // ## EKRAN İÇİNDE HATA GÖSTEREN YENİ KATEGORİ ÖNERME FONKSİYONU ##
    async function submitCategory() {
        const categoryNameInput = document.getElementById('new_category_name');
        const errorDiv = document.getElementById('category-error-message');
        const categoryName = categoryNameInput.value.trim();

        // Önceki hataları temizle
        errorDiv.style.display = 'none';
        categoryNameInput.classList.remove('input-error');
        
        if (categoryName === '') {
            errorDiv.textContent = 'Kategori adı boş olamaz.';
            errorDiv.style.display = 'block';
            categoryNameInput.classList.add('input-error');
            return;
        }
        
        try {
            const response = await fetch('category_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: categoryName })
            });
            const result = await response.json();

            if (result.status === 'success') {
                const categorySelect = document.getElementById('category_id');
                // Yeni kategoriyi oluştur ve seçili hale getir
                const newOption = new Option(result.name + " (Onay Bekliyor)", result.id, true, true);
                categorySelect.add(newOption, 1);
                
                alert('Kategori öneriniz başarıyla gönderildi ve bu kurs için otomatik olarak seçildi!');
                categoryNameInput.value = '';
                closePopup('category-popup');
                isDirty = true;
            } else {
                // Hata mesajını alert yerine div içinde göster
                errorDiv.textContent = result.message || 'Bilinmeyen bir hata oluştu.';
                errorDiv.style.display = 'block';
                categoryNameInput.classList.add('input-error');
            }
        } catch (error) {
            errorDiv.textContent = 'Sunucuyla iletişim kurulamadı. Lütfen internet bağlantınızı kontrol edin.';
            errorDiv.style.display = 'block';
            categoryNameInput.classList.add('input-error');
            console.error('Fetch Hatası:', error);
        }
    }
</script>
<style>
    .error-text { color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem; display: none; }
    .input-error { border-color: #dc2626 !important; }
</style>

</body>
</html>