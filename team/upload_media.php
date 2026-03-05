<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }

$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND team_db_id = ?");
$stmt->execute([$course_id, $_SESSION['team_db_id']]);
$course = $stmt->fetch();
if (!$course) { die("Hata: Bu kurs size ait değil veya bulunamadı."); }

$page_title = "İçerik Yükle";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($course['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_content.css">
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">2. Adım: Görsel İçerikleri Yükleyin</h1>
            <p class="text-gray-600">"<?php echo htmlspecialchars($course['title']); ?>" kursu için materyalleri ekleyin.</p>
        </div>
        <a href="panel.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300">Panele Geri Dön</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="upload-card">
            <h2><i data-lucide="image"></i> Kurs Kapağı</h2>
            <div id="cover-drop-zone" class="drop-zone">
                <input type="file" name="cover_image" id="cover-image-input" class="hidden-input" accept="image/jpeg, image/png">
                <p class="drop-zone-text-light">Sadece JPG ve PNG formatları</p>
            </div>
            <div id="cover-progress-container" class="progress-bar-container hidden">
                <div id="cover-progress-bar" class="progress-bar-fill"></div>
                <span id="cover-progress-text" class="progress-bar-text">0%</span>
            </div>
            <div id="cover-preview-container" class="preview-container hidden">
                <img id="cover-preview" class="aspect-ratio-16-9" alt="Kapak önizlemesi">
            </div>
        </div>

        <div class="upload-card">
            <h2><i data-lucide="video"></i> Kurs Tanıtım Videosu</h2>
            <div id="video-drop-zone" class="drop-zone">
                <input type="file" name="intro_video" id="video-input" class="hidden-input" accept="video/mp4, video/quicktime">
                <p class="drop-zone-text-light">Sadece MP4 ve MOV formatları</p>
            </div>
            <div id="video-progress-container" class="progress-bar-container hidden">
                <div id="video-progress-bar" class="progress-bar-fill"></div>
                <span id="video-progress-text" class="progress-bar-text">0%</span>
            </div>
            <div id="video-preview-container" class="preview-container hidden">
                <video id="video-preview" controls></video>
            </div>
        </div>
    </div>
    <div class="text-right mt-8">
        <a href="manage_curriculum.php?id=<?php echo $course_id; ?>" class="btn text-lg"><i data-lucide="arrow-right" class="mr-2"></i> 3. Adıma Geç</a>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // ## GÜNCELLENMİŞ VE YÜKLEME BARI EKLENMİŞ JAVASCRIPT KODU ##
    function setupDropZone(options) {
        const { dropZoneId, inputId, previewContainerId, previewElementId, progressContainerId, progressBarId, progressTextId, allowedTypes, errorMessage, uploadUrl } = options;
        
        const dropZone = document.getElementById(dropZoneId);
        const fileInput = document.getElementById(inputId);
        const previewContainer = document.getElementById(previewContainerId);
        const previewElement = document.getElementById(previewElementId);
        const progressContainer = document.getElementById(progressContainerId);
        const progressBar = document.getElementById(progressBarId);
        const progressText = document.getElementById(progressTextId);
        
        dropZone.addEventListener('click', () => fileInput.click());
        // ... Sürükle-bırak olayları aynı kalıyor ...

        fileInput.addEventListener('change', () => { if (fileInput.files.length) { handleFile(fileInput.files[0]); } });

        function handleFile(file) {
            if (!allowedTypes.includes(file.type)) {
                alert(errorMessage);
                fileInput.value = "";
                return;
            }
            uploadFile(file);
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('course_id', <?php echo $course_id; ?>);
            formData.append('upload_type', inputId.includes('cover') ? 'cover' : 'intro');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);

            // Yükleme ilerlemesini dinle
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressContainer.classList.remove('hidden');
                    dropZone.classList.add('hidden');
                    progressBar.style.width = percentComplete.toFixed(2) + '%';
                    progressText.textContent = Math.round(percentComplete) + '%';
                }
            });

            // Yükleme tamamlandığında
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            previewElement.src = reader.result;
                            progressContainer.classList.add('hidden');
                            previewContainer.classList.remove('hidden');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        alert('Yükleme hatası: ' + response.message);
                        resetDropZone();
                    }
                } else {
                    alert('Sunucu hatası oluştu.');
                    resetDropZone();
                }
            };
            
            xhr.send(formData);
        }
        
        function resetDropZone() {
            progressContainer.classList.add('hidden');
            dropZone.classList.remove('hidden');
            fileInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupDropZone({
            dropZoneId: 'cover-drop-zone', inputId: 'cover-image-input', 
            previewContainerId: 'cover-preview-container', previewElementId: 'cover-preview',
            progressContainerId: 'cover-progress-container', progressBarId: 'cover-progress-bar', progressTextId: 'cover-progress-text',
            allowedTypes: ['image/jpeg', 'image/png'],
            errorMessage: 'Hata: Lütfen sadece JPG veya PNG formatında bir resim yükleyin.',
            uploadUrl: 'save_course_files.php'
        });
        setupDropZone({
            dropZoneId: 'video-drop-zone', inputId: 'video-input',
            previewContainerId: 'video-preview-container', previewElementId: 'video-preview',
            progressContainerId: 'video-progress-container', progressBarId: 'video-progress-bar', progressTextId: 'video-progress-text',
            allowedTypes: ['video/mp4', 'video/quicktime'],
            errorMessage: 'Hata: Lütfen sadece MP4 veya MOV formatında bir video yükleyin.',
            uploadUrl: 'save_course_files.php'
        });
    });
</script>
</body>
</html>