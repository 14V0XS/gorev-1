<?php
session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

// --- GÜVENLİK KONTROLLERİ ---

// 1. KURAL: Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['team_logged_in']) || !isset($_SESSION['team_db_id'])) {
    header("Location: $projectRoot/team/unauthorized.php");
    exit();
}

// 2. KURAL: Kurs ID'si geçerli mi?
$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header('Location: http://localhost:8888/frc_rookieverse/team/unauthorized.php');
    exit();
}

$pdo = get_db_connection();
// --- BAĞLANTI DÜZELTMESİ SONU ---


// Veritabanından kursu, ID ve takım ID'si eşleşiyorsa çek.
$stmt = $pdo->prepare("
  SELECT title, cover_image_url
  FROM courses
  WHERE id = :course_id AND team_db_id = :team_id
");
$stmt->execute([
    ':course_id' => $course_id,
    ':team_id' => $_SESSION['team_db_id']
]);
$course = $stmt->fetch();

// Eğer sorgu sonuç döndürmezse, kurs ya yoktur ya da bu takıma ait değildir.
if (!$course) {
    header('Location: http://localhost:8888/frc_rookieverse/team/unauthorized.php');
    exit();
}

$page_title = "İçerik Yükle";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/rokiverse_icon.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_content.css">
</head>
<body class="bg-gray-100">
<?php require_once $projectRoot . '/navbar.php'; ?>

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
                <input type="file" id="cover-image-input" class="hidden-input" accept="image/jpeg, image/png">
                <i data-lucide="upload-cloud" class="icon"></i>
                <p class="drop-zone-text-bold">Sürükleyin veya Tıklayın</p>
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
            <!-- YouTube URL giriş alanı (başta gizli) -->
            <div id="youtube-url-box" class="hidden">
                <input
                        type="url"
                        id="youtube-url-input"
                        placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX"
                        class="w-full border rounded-md p-3 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        inputmode="url"
                >
                <div class="mt-3 flex items-center gap-3">
                    <button id="youtube-url-save"
                            class="bg-yellow-500 text-white font-semibold px-4 py-2 rounded-md hover:bg-yellow-600">
                        Kaydet
                    </button>
                    <span id="youtube-save-msg" class="text-sm text-gray-500"></span>
                </div>

                <!-- Önizleme -->
                <div id="youtube-embed-wrap" class="mt-3 hidden w-full" style="aspect-ratio:16/9;">
                    <iframe id="youtube-embed" class="w-full h-full rounded-lg" allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <p class="text-xs text-gray-500 mt-2">
                    Bu seçenek açıkken MP4 dosyası yerine verdiğiniz YouTube bağlantısı kullanılır.
                </p>
            </div>
            <div id="video-drop-zone" class="drop-zone">
                <input type="file" id="video-input" class="hidden-input" accept="video/mp4">
                <i data-lucide="upload-cloud" class="icon"></i>
                <p class="drop-zone-text-bold">Sürükleyin veya Tıklayın</p>
                <p class="drop-zone-text-light">Sadece MP4 formatı</p>
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
    <!-- YouTube URL toggle -->
    <div class="flex items-center gap-2 mb-3">
        <input id="use-yt-checkbox" type="checkbox" class="rounded border-gray-300">
        <label for="use-yt-checkbox" class="text-sm text-gray-700">YouTube linki kullan</label>
    </div>



    <div class="text-right mt-8">
        <p id="cover-required-msg" class="text-sm text-red-600 mb-2 hidden">
            Devam etmek için bir kurs kapağı yükleyin.
        </p>

        <a id="next-btn"
           href="add_module.php?course_id=<?php echo $course_id; ?>"
           class="btn text-lg opacity-50 pointer-events-none"
           aria-disabled="true">
            <i data-lucide="arrow-right" class="mr-2"></i> 3. Adıma Geç
        </a>
    </div>

</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

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
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                handleFile(fileInput.files[0]);
            }
        });

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
            formData.append('course_id', '<?php echo $course_id; ?>');
            formData.append('upload_type', inputId.includes('cover') ? 'cover' : 'intro');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_course_files.php', true);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressContainer.classList.remove('hidden');
                    dropZone.classList.add('hidden');
                    progressBar.style.width = percentComplete.toFixed(2) + '%';
                    progressText.textContent = Math.round(percentComplete) + '%';
                }
            });

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const reader = new FileReader();
                            reader.onload = () => {
                                previewElement.src = reader.result;
                                progressContainer.classList.add('hidden');
                                previewContainer.classList.remove('hidden');
                            }
                            reader.readAsDataURL(file);
                            if (formData.get('upload_type') === 'cover' && typeof lockNext === 'function') {
                                lockNext(false);
                            }
                        } else {
                            alert('Yükleme hatası: ' + response.message);
                            resetDropZone();
                        }
                    } catch (e) {
                        alert('Sunucudan geçersiz bir yanıt alındı. Lütfen sunucu loglarını kontrol edin.');
                        console.error("JSON Parse Error:", e);
                        console.error("Response Text:", xhr.responseText);
                        resetDropZone();
                    }
                } else {
                    alert('Sunucu hatası oluştu. (Status: ' + xhr.status + ')');
                    resetDropZone();
                }
            };
            
            xhr.onerror = function() {
                alert('Ağ hatası oluştu.');
                resetDropZone();
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
            allowedTypes: ['video/mp4'],
            errorMessage: 'Hata: Lütfen sadece MP4 formatında bir video yükleyin.',
            uploadUrl: 'save_course_files.php'
        });
    });
    // --- YouTube URL kullan toggling + kayıt ---

    const ytToggle       = document.getElementById('use-yt-checkbox');
    const ytBox          = document.getElementById('youtube-url-box');
    const ytInput        = document.getElementById('youtube-url-input');
    const ytSaveBtn      = document.getElementById('youtube-url-save');
    const ytSaveMsg      = document.getElementById('youtube-save-msg');
    const ytPreviewWrap  = document.getElementById('youtube-embed-wrap');
    const ytIframe       = document.getElementById('youtube-embed');

    // Mevcut MP4 UI elemanları
    const videoDropZone        = document.getElementById('video-drop-zone');
    const videoProgressContainer = document.getElementById('video-progress-container');
    const videoPreviewContainer  = document.getElementById('video-preview-container');

    function parseYouTubeId(url) {
        try {
            const u = new URL(url);
            const host = u.hostname.replace(/^www\./,'');
            if (host === 'youtu.be') return u.pathname.slice(1);
            if (host === 'youtube.com' || host === 'm.youtube.com') {
                if (u.searchParams.get('v')) return u.searchParams.get('v');
                const m1 = u.pathname.match(/\/embed\/([^/?#]+)/);   if (m1) return m1[1];
                const m2 = u.pathname.match(/\/shorts\/([^/?#]+)/);  if (m2) return m2[1];
            }
        } catch (_) {}
        const m = String(url).match(/(?:v=|\/)([0-9A-Za-z_-]{11})(?:[&?/]|$)/);
        return m ? m[1] : null;
    }

    function refreshYtPreview() {
        const id = parseYouTubeId(ytInput.value.trim());
        if (id) {
            ytIframe.src = `https://www.youtube.com/embed/${id}`;
            ytPreviewWrap.classList.remove('hidden');
        } else {
            ytPreviewWrap.classList.add('hidden');
            ytIframe.src = '';
        }
    }

    // Toggle: kutu işaretlenince MP4 yükleme alanlarını gizle, URL alanını göster
    ytToggle?.addEventListener('change', () => {
        const on = ytToggle.checked;
        ytBox.classList.toggle('hidden', !on);

        // MP4 UI’ı yönet
        videoDropZone?.classList.toggle('hidden', on);
        videoProgressContainer?.classList.add('hidden');
        videoPreviewContainer?.classList.add('hidden');

        // İkonları tazele
        try { lucide.createIcons(); } catch (_) {}
    });

    ytInput?.addEventListener('input', refreshYtPreview);

    ytSaveBtn?.addEventListener('click', async () => {
        const raw = ytInput.value.trim();
        const id = parseYouTubeId(raw);
        if (!id) {
            ytSaveMsg.textContent = 'Geçerli bir YouTube bağlantısı girin.';
            ytSaveMsg.classList.remove('text-green-600');
            ytSaveMsg.classList.add('text-red-600');
            return;
        }

        ytSaveBtn.disabled = true;
        ytSaveMsg.textContent = 'Kaydediliyor...';
        ytSaveMsg.classList.remove('text-red-600');
        ytSaveMsg.classList.add('text-gray-500');

        try {
            // Server tarafında save_course_files.php'de bunu işle:
            // if ($_POST['upload_type']==='intro_youtube') { $_POST['youtube_url'] ... }
            const res = await fetch('save_course_files.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    course_id: '<?= htmlspecialchars($course_id, ENT_QUOTES, "UTF-8") ?>',
                    upload_type: 'intro_youtube',
                    youtube_url: raw
                })
            });
            const data = await res.json().catch(() => ({}));
            if (data && (data.success || data.ok)) {
                ytSaveMsg.textContent = 'Kaydedildi.';
                ytSaveMsg.classList.remove('text-gray-500','text-red-600');
                ytSaveMsg.classList.add('text-green-600');
                refreshYtPreview();
            } else {
                throw new Error(data?.message || 'Sunucu hatası');
            }
        } catch (err) {
            ytSaveMsg.textContent = 'Kaydedilemedi: ' + (err?.message || 'Hata');
            ytSaveMsg.classList.remove('text-gray-500','text-green-600');
            ytSaveMsg.classList.add('text-red-600');
        } finally {
            ytSaveBtn.disabled = false;
        }

    });
    const HAS_COVER = <?= (!empty($course['cover_image_url']) ? 'true' : 'false') ?>;
    const nextBtn   = document.getElementById('next-btn');
    const coverMsg  = document.getElementById('cover-required-msg');

    function lockNext(lock) {
        if (lock) {
            nextBtn.classList.add('opacity-50','pointer-events-none');
            nextBtn.setAttribute('aria-disabled','true');
            coverMsg.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('opacity-50','pointer-events-none');
            nextBtn.removeAttribute('aria-disabled');
            coverMsg.classList.add('hidden');
        }
    }
    // sayfa açılışında
    lockNext(!HAS_COVER);
</script>
<script>

</script>

</body>
</html>