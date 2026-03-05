<?php
// Session kontrolü
session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }

$pdo = get_db_connection();

// KURAL 1: Bu sayfa sadece ilk defa içerik oluşturulurken kullanılır.
$stmt_check = $pdo->prepare("SELECT COUNT(*) as module_count FROM course_modules WHERE course_id = ?");
$stmt_check->execute([$course_id]);
$result = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($result && $result['module_count'] > 0) {
    header('Location: view_curriculum.php?id=' . $course_id);
    exit();
}

$stmt_course = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND team_db_id = ?");
$stmt_course->execute([$course_id, $_SESSION['team_db_id']]);
$course = $stmt_course->fetch();
if (!$course) { die("Hata: Bu kurs size ait değil veya bulunamadı."); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3. Adım: Kurs İçeriğini Oluştur - <?php echo htmlspecialchars($course['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <style>
        :root {
            --primary-color: #E5AE32; --primary-hover: #c4952b; --background-color: #f7f7ff;
            --card-background: #ffffff; --text-dark: #0f172a; --text-light: #64748b; --border-color: #e2e8f0;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: var(--background-color); }
        .card { background-color: var(--card-background); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background-color: var(--primary-color); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: background-color 0.2s; }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: #475569; }
        .hidden { display: none !important; }
        .action-button { background:none; border:none; cursor:pointer; color: var(--text-light); transition: color 0.2s; padding: 0.25rem; }
        .action-button:hover { color: var(--text-dark); }
        .action-button.disabled { color: #d1d5db; cursor: not-allowed; }
        .module-card { margin-bottom: 1.5rem; }
        .module-header { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.5rem; border: 1px solid var(--border-color); border-radius: 0.75rem 0.75rem 0 0; background-color: var(--card-background); }
        .module-header h3 { font-size: 1.125rem; font-weight: 600; flex-grow: 1; }
        .module-content { padding: 1.5rem; border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 0.75rem 0.75rem; background: #fdfdfd; }
        .content-item { background-color: var(--card-background); border: 1px solid var(--border-color); border-radius: 0.5rem; }
        .content-item.dragging { opacity: 0.6; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .drag-handle { cursor: grab; } .drag-handle:active { cursor: grabbing; }
        .content-item-header { display: flex; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); }
        .content-item-title { font-weight: 500; flex-grow: 1; margin-left: 0.5rem; }
        .content-item-body { padding: 1rem; }
        .editor-toolbar { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 0.5rem; margin-bottom: 1rem; }
        .editor-select { border: 1px solid var(--border-color); border-radius: 0.375rem; padding: 0.4rem; font-size: 0.875rem; }
        .editor-button { background-color: white; border: 1px solid var(--border-color); padding: 0.4rem; cursor: pointer; border-radius: 0.375rem; display: flex; align-items: center; }
        .editor-button.is-active { background-color: #eef2ff; color: var(--primary-color); border-color: var(--primary-color); }
        .editable-content { min-height: 200px; border: 1px solid var(--border-color); padding: 1rem; border-radius: 0.5rem; outline-color: var(--primary-color); }
        .editable-content:focus { border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
        .editable-content h2 { font-size: 1.5em; font-weight: bold; } .editable-content h3 { font-size: 1.25em; font-weight: bold; }
        .editable-content a { color: var(--primary-color); text-decoration: underline; }
        .editable-content ul, .editable-content ol { padding-left: 1.5rem; margin: 0.5rem 0; list-style-position: inside; }
        .editable-content ul > li::marker, .editable-content ol > li::marker { color: var(--primary-color); font-weight: bold; }
        .horizontal-upload-card { display: flex; align-items: center; gap: 1.5rem; }
        .upload-preview-thumb { width: 120px; height: 67.5px; flex-shrink: 0; background-color: var(--background-color); border: 1px solid var(--border-color); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: var(--text-light); overflow: hidden; }
        .upload-preview-thumb video { width: 100%; height: 100%; object-fit: cover; }
        .upload-drop-area { flex-grow: 1; border: 2px dashed var(--border-color); border-radius: 0.5rem; padding: 1.5rem; text-align: center; color: var(--text-light); cursor: pointer; transition: all 0.2s; }
        .upload-drop-area.drag-over { border-color: var(--primary-color); background-color: rgba(229, 174, 50, 0.05); }
        .file-display { display: flex; align-items: center; gap: 1rem; width: 100%; }
        .file-display .file-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 5000; opacity: 0; visibility: hidden; transition: opacity 0.3s; }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content { background: white; border-radius: 0.75rem; width: 90%; max-width: 400px; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .preview-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); display: flex; justify-content: center; align-items: center; z-index: 5001; }
        .preview-modal-content { max-width: 90vw; max-height: 90vh; background: #fff; }
        .preview-modal-content iframe, .preview-modal-content video { width: 80vw; height: 90vh; border: none; }
        .preview-modal-close { position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; cursor: pointer; z-index: 5002; }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div><h1 class="text-3xl font-bold">3. Adım: Kurs İçeriğini Oluştur</h1><p class="text-gray-600">Bölümünüze içerikler ekleyin ve sıralayın.</p></div>
        <a href="panel.php" class="btn btn-secondary">Panele Geri Dön</a>
    </div>

    <form id="curriculum-form" action="save_curriculum.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div id="modules-container"></div>
        <div id="add-module-area" class="mt-6">
            <div id="add-module-prompt" class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-yellow-500 hover:text-yellow-600 transition flex items-center justify-center gap-2 cursor-pointer"><i data-lucide="plus"></i> Bölüm Ekle</div>
            <div id="add-module-form" class="card hidden"><label for="new_module_title" class="font-bold text-lg mb-2 block">Bölüm Adı</label><input type="text" id="new_module_title" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Örn: Giriş ve Temel Kavramlar"><div class="flex gap-4 mt-4"><button type="button" id="add-module-btn" class="btn">Bölümü Ekle</button><button type="button" id="cancel-add-module-btn" class="btn btn-secondary">İptal</button></div></div>
        </div>
        <div class="text-right mt-8"><button type="submit" class="btn text-lg"><i data-lucide="save" class="w-5 h-5 mr-2"></i>İçeriği Kaydet ve Bitir</button></div>
    </form>
</div>

<div id="link-modal" class="modal-overlay">
    <div class="modal-content"><h2 class="text-xl font-bold mb-4">Bağlantı Ekle</h2><input type="url" id="link-url" class="w-full p-2 border rounded" placeholder="https://www.example.com"><div class="flex justify-end gap-4 mt-6"><button type="button" id="cancel-link-btn" class="btn btn-secondary">İptal</button><button type="button" id="apply-link-btn" class="btn">Uygula</button></div></div>
</div>
<div id="paste-modal" class="modal-overlay">
    <div class="modal-content text-center"><h2 class="text-xl font-bold mb-4">Pano İçeriği Bulundu</h2><p class="mb-6 text-gray-600">Panonuzdaki metni editöre yapıştırmak ister misiniz?</p><div class="flex gap-4"><button type="button" id="paste-confirm-btn" class="btn w-full">Evet, Yapıştır</button><button type="button" id="paste-cancel-btn" class="btn btn-secondary w-full">Hayır, Boş Başla</button></div></div>
</div>
<div id="preview-modal" class="preview-modal-overlay hidden">
    <button id="preview-modal-close" class="preview-modal-close"><i data-lucide="x" class="w-10 h-10"></i></button>
    <div id="preview-modal-content"></div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // === TÜR LİMİTLERİ ===
// Hepsinden max 1: istersen 'text' için 999 yapıp sınırsız gibi kullanabilirsin.
    const TYPE_LIMITS = { text: 999, video: 50, document: 100 };

// Yardımcılar
    function getTypeCount(container, type) {
        return container.querySelectorAll(`input[name*="[type]"][value="${type}"]`).length;
    }
    function canAdd(container, type) {
        const limit = TYPE_LIMITS[type] ?? Infinity;
        return getTypeCount(container, type) < limit;
    }
    function refreshAddButtons(moduleEl) {
        const container = moduleEl.querySelector('.content-items-container');
        moduleEl.querySelectorAll('.add-content-btn').forEach((btn) => {
            const type = btn.dataset.type;
            const allowed = canAdd(container, type);
            btn.disabled = !allowed;
            btn.classList.toggle('opacity-50', !allowed);
            btn.classList.toggle('cursor-not-allowed', !allowed);
        });
    }


    lucide.createIcons();
    const curriculumForm = document.getElementById('curriculum-form');
    const modulesContainer = document.getElementById('modules-container');
    const addModuleArea = document.getElementById('add-module-area');
    const addModulePrompt = document.getElementById('add-module-prompt');
    const addModuleForm = document.getElementById('add-module-form');
    const addModuleBtn = document.getElementById('add-module-btn');
    const cancelAddModuleBtn = document.getElementById('cancel-add-module-btn');
    const newModuleTitleInput = document.getElementById('new_module_title');

    let isFormDirty = false, activeEditor = null, savedSelection = null;
    const linkModal = document.getElementById('link-modal');
    const pasteModal = document.getElementById('paste-modal');
    const previewModal = document.getElementById('preview-modal');

    // === GÜVENLİK VE SAYFA AKIŞI ===
    curriculumForm.addEventListener('input', () => { isFormDirty = true; });
    window.addEventListener('beforeunload', (e) => { if (isFormDirty) { e.preventDefault(); e.returnValue = ''; } });
    curriculumForm.addEventListener('submit', () => { isFormDirty = false; });

    // === BÖLÜM YÖNETİMİ ===
    addModulePrompt.addEventListener('click', () => { addModulePrompt.classList.add('hidden'); addModuleForm.classList.remove('hidden'); newModuleTitleInput.focus(); });
    cancelAddModuleBtn.addEventListener('click', () => { addModuleForm.classList.add('hidden'); addModulePrompt.classList.remove('hidden'); });
    addModuleBtn.addEventListener('click', () => {
        const title = newModuleTitleInput.value.trim();
        if (title) { createModule({ title: title }); addModuleArea.style.display = 'none'; } 
        else { alert('Bölüm adı boş bırakılamaz.'); }
    });

    function createModule(moduleData) {
        const moduleKey = `new_${Date.now()}`;
        const moduleElement = document.createElement('div');
        moduleElement.className = 'module-card';
        moduleElement.innerHTML = `<div class="module-header"><h3 class="flex-grow">${moduleData.title}</h3><input type="hidden" name="modules[${moduleKey}][title]" value="${moduleData.title}"></div><div class="module-content"><div class="content-items-container space-y-4"></div><div class="flex gap-4 mt-6 border-t pt-4"><button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="text"><i data-lucide="type" class="w-4 h-4 mr-1"></i>Metin</button><button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="video"><i data-lucide="video" class="w-4 h-4 mr-1"></i>Video</button><button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="document"><i data-lucide="file-plus" class="w-4 h-4 mr-1"></i>Döküman</button></div></div>`;
        modulesContainer.innerHTML = '';
        modulesContainer.appendChild(moduleElement);
        attachModuleListeners(moduleElement, moduleKey);
        lucide.createIcons();
        refreshAddButtons(moduleElement);

    }

    // === İÇERİK YÖNETİMİ ===
    function attachModuleListeners(moduleElement, moduleKey) {
        const contentContainer = moduleElement.querySelector('.content-items-container');
        makeSortable(contentContainer);
        moduleElement.querySelectorAll('.add-content-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const type = btn.dataset.type;
                const contentContainer = moduleElement.querySelector('.content-items-container');

                // LİMİT KONTROLÜ: Eklenebiliyor mu?
                if (!canAdd(contentContainer, type)) {
                    alert(`Bu modülde "${type}" içeriği en fazla ${TYPE_LIMITS[type]} kez eklenebilir.`);
                    return;
                }

                if (type === 'text') {
                    try {
                        const clipboardText = await navigator.clipboard.readText();
                        if (clipboardText.trim() && clipboardText.length < 750) {
                            pasteModal.classList.add('show');
                            document.getElementById('paste-confirm-btn').onclick = () => {
                                addContentItem(contentContainer, type, moduleKey, { data: clipboardText });
                                pasteModal.classList.remove('show');
                                refreshAddButtons(moduleElement);
                            };
                            document.getElementById('paste-cancel-btn').onclick = () => {
                                addContentItem(contentContainer, type, moduleKey);
                                pasteModal.classList.remove('show');
                                refreshAddButtons(moduleElement);
                            };
                        } else { throw new Error("Pano boş veya limit aşıldı"); }
                    } catch (err) {
                        addContentItem(contentContainer, type, moduleKey);
                        refreshAddButtons(moduleElement);
                    }
                } else {
                    addContentItem(contentContainer, type, moduleKey);
                    refreshAddButtons(moduleElement);
                }
            });

        });
    }

    function addContentItem(container, type, moduleKey, contentData = {}) {
        const contentKey = `new_c_${Date.now()}`;
        const itemElement = document.createElement('div');
        itemElement.className = 'content-item';
        const data = contentData.data || '';
        const typeText = type === 'text' ? 'Metin İçeriği' : (type === 'video' ? 'Video' : 'Döküman');
        const icon = type === 'text' ? 'type' : (type === 'video' ? 'video' : 'file-text');
        let bodyHTML = '';

        if (type === 'text') {
            const editorData = data || '<p>Metninizi buraya yazın...</p>';
            bodyHTML = `<div class="editor-toolbar"><div class="toolbar-group flex gap-1"><button type="button" class="editor-button" data-action="bold" title="Kalın"><i data-lucide="bold"></i></button><button type="button" class="editor-button" data-action="italic" title="İtalik"><i data-lucide="italic"></i></button></div><div class="toolbar-group flex gap-1"><button type="button" class="editor-button" data-action="insertUnorderedList" title="Liste"><i data-lucide="list"></i></button><button type="button" class="editor-button" data-action="insertOrderedList" title="Numaralı Liste"><i data-lucide="list-ordered"></i></button></div><div class="toolbar-group"><button type="button" class="editor-button" data-action="createLink" title="Link"><i data-lucide="link"></i></button></div></div><div class="editable-content" contenteditable="true" spellcheck="false">${editorData}</div><textarea class="hidden" name="modules[${moduleKey}][contents][${contentKey}][paragraph]">${editorData}</textarea>`;
        } else {
            const accept = type === 'video' ? 'video/*' : '.pdf,.doc,.docx,.ppt,.pptx';
            bodyHTML = `<div class="upload-container"><div class="file-display hidden"></div><div class="horizontal-upload-card"><div class="upload-preview-thumb"><i data-lucide="${icon}"></i></div><div class="upload-drop-area"><p>Dosyayı buraya sürükleyin veya <span class="font-bold text-yellow-600">tıklayın</span></p></div></div></div><input type="file" class="hidden-file-input" name="modules[${moduleKey}][contents][${contentKey}][file]" accept="${accept}" style="display:none;"><input type="hidden" name="modules[${moduleKey}][contents][${contentKey}][existing_file]" value="${data}">`;
        }
        itemElement.innerHTML = `<div class="content-item-header"><i  class="text-gray-500"></i><span class="content-item-title">${typeText}</span><div class="ml-auto flex items-center gap-2"><button type="button" class="action-button move-up-btn" title="Yukarı Taşı"><i data-lucide="chevron-up"></i></button><button type="button" class="action-button move-down-btn" title="Aşağı Taşı"><i data-lucide="chevron-down"></i></button><button type="button" class="action-button delete-content-btn text-red-500" title="Sil"><i data-lucide="trash-2"></i></button></div></div><div class="content-item-body">${bodyHTML}</div><input type="hidden" name="modules[${moduleKey}][contents][${contentKey}][type]" value="${type}"><input type="hidden" name="modules[${moduleKey}][contents][${contentKey}][sort_order]" value="">`;

        container.appendChild(itemElement);
        updateSortOrder(container);
        attachContentItemListeners(itemElement, type);
        lucide.createIcons();
    }

    function attachContentItemListeners(itemElement, type) {
        itemElement.querySelector('.delete-content-btn').addEventListener('click', () => {
            if (confirm('İçeriği sil?')) {
                const moduleEl = itemElement.closest('.module-card');
                itemElement.remove();
                updateSortOrder(itemElement.parentElement);
                // SİLİNDİ: Ekleme butonlarını güncelle
                refreshAddButtons(moduleEl);
            }
        });
        itemElement.querySelector('.move-up-btn').addEventListener('click', (e) => moveContent(e.currentTarget, 'up'));
        itemElement.querySelector('.move-down-btn').addEventListener('click', (e) => moveContent(e.currentTarget, 'down'));
        
        if (type === 'text') {
            const editor = itemElement.querySelector('.editable-content');
            const toolbar = itemElement.querySelector('.editor-toolbar');
            const textarea = itemElement.querySelector('textarea');
            editor.addEventListener('focus', () => { activeEditor = editor; });
            editor.addEventListener('blur', () => { savedSelection = saveSelection(); });
            editor.addEventListener('input', () => { textarea.value = editor.innerHTML; isFormDirty = true; });
            editor.addEventListener('keyup', () => updateToolbarState(toolbar));
            editor.addEventListener('mouseup', () => updateToolbarState(toolbar));
            toolbar.addEventListener('change', (e) => handleEditorCommand(e.target.dataset.action, e.target.value));
            toolbar.addEventListener('click', (e) => { const button = e.target.closest('button'); if (button) handleEditorCommand(button.dataset.action); });
        } else {
            const dropArea = itemElement.querySelector('.upload-drop-area');
            const fileInput = itemElement.querySelector('.hidden-file-input');
            dropArea.addEventListener('click', () => fileInput.click());
            dropArea.addEventListener('dragover', (e) => { e.preventDefault(); dropArea.classList.add('drag-over'); });
            dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
            dropArea.addEventListener('drop', (e) => { e.preventDefault(); dropArea.classList.remove('drag-over'); if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showFileInPreview(itemElement, fileInput.files[0]); } });
            fileInput.addEventListener('change', () => { if (fileInput.files.length) showFileInPreview(itemElement, fileInput.files[0]); });
        }
    }

    // === SIRALAMA FONKSİYONLARI ===
    function makeSortable(container) {
        let draggingElement = null;
        container.addEventListener('dragstart', e => { if (e.target.closest('.drag-handle')) { draggingElement = e.target.closest('.content-item'); setTimeout(() => draggingElement.classList.add('dragging'), 0); } });
        container.addEventListener('dragend', () => { if (draggingElement) { draggingElement.classList.remove('dragging'); draggingElement = null; updateSortOrder(container); } });
        container.addEventListener('dragover', e => { e.preventDefault(); const afterElement = getDragAfterElement(container, e.clientY); if(draggingElement) container.insertBefore(draggingElement, afterElement); });
    }
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.content-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => { const box = child.getBoundingClientRect(); const offset = y - box.top - box.height / 2; if (offset < 0 && offset > closest.offset) { return { offset, element: child }; } else { return closest; } }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    function moveContent(button, direction) {
        const item = button.closest('.content-item'); if (button.classList.contains('disabled')) return;
        const container = item.parentElement;
        if (direction === 'up' && item.previousElementSibling) { container.insertBefore(item, item.previousElementSibling); } 
        else if (direction === 'down' && item.nextElementSibling) { container.insertBefore(item.nextElementSibling, item); }
        updateSortOrder(container);
    }
    function updateSortOrder(container) {
        const items = container.querySelectorAll('.content-item');
        items.forEach((item, index) => {
            item.querySelector('input[name*="[sort_order]"]').value = index;
            item.querySelector('.move-up-btn').classList.toggle('disabled', index === 0);
            item.querySelector('.move-down-btn').classList.toggle('disabled', index === items.length - 1);
        });
    }

    // === DOSYA ÖNİZLEME FONKSİYONLARI ===
    function showFileInPreview(itemElement, fileObject) {
        const type = itemElement.querySelector('input[name*="[type]"]').value;
        const uploadContainer = itemElement.querySelector('.upload-container');
        const fileDisplay = uploadContainer.querySelector('.file-display');
        const uploadCard = uploadContainer.querySelector('.horizontal-upload-card');
        const filePath = URL.createObjectURL(fileObject);
        fileDisplay.innerHTML = `<div class="upload-preview-thumb">${type === 'video' ? `<video src="${filePath}"></video>` : '<i data-lucide="file-text"></i>'}</div><div class="flex-grow min-w-0"><div class="file-name" title="${fileObject.name}">${fileObject.name}</div><div class="file-size">${(fileObject.size / 1024 / 1024).toFixed(2)} MB</div></div><div class="file-actions"><button type="button" class="action-button preview-btn" title="Önizle"><i data-lucide="eye"></i></button><button type="button" class="action-button change-btn" title="Değiştir"><i data-lucide="refresh-cw"></i></button></div>`;
        fileDisplay.querySelector('.preview-btn').addEventListener('click', () => showPreview(filePath, type));
        fileDisplay.querySelector('.change-btn').addEventListener('click', () => itemElement.querySelector('.hidden-file-input').click());
        uploadCard.classList.add('hidden');
        fileDisplay.classList.remove('hidden');
        lucide.createIcons({nodes: [fileDisplay]});
    }
    function showPreview(filePath, fileType) {
        let content = '';
        const fileExtension = filePath.split('.').pop().toLowerCase();
        if (fileType === 'video') { content = `<video src="${filePath}" controls autoplay></video>`; } 
        else if (fileType === 'document') {
            if (fileExtension === 'pdf') { content = `<iframe src="${filePath}"></iframe>`; } 
            else if (['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(fileExtension) && !filePath.startsWith('blob:')) {
                const viewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
                content = `<iframe src="${viewerUrl}"></iframe>`;
            } else { content = `<div class="p-8 text-center text-white"><h3>Bu dosya türü için önizleme desteklenmiyor veya dosyanın önce kaydedilmesi gerekiyor.</h3></div>`; }
        }
        document.getElementById('preview-modal-content').innerHTML = content;
        previewModal.classList.remove('hidden');
    }
    document.getElementById('preview-modal-close').addEventListener('click', () => previewModal.classList.add('hidden'));

    // === METİN EDİTÖRÜ FONKSİYONLARI ===
    function saveSelection() { if (window.getSelection) { const sel = window.getSelection(); if (sel.getRangeAt && sel.rangeCount) return sel.getRangeAt(0); } return null; }
    function restoreSelection(range) { if (range && window.getSelection) { const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range); } }
    function handleEditorCommand(command, value = null) {
        if (!command || !activeEditor) return; activeEditor.focus();
        if (command === 'createLink') {
            savedSelection = saveSelection();
            if(!savedSelection || savedSelection.collapsed) { alert("Lütfen link eklemek için bir metin seçin."); return; }
            linkModal.classList.add('show'); return;
        }
        document.execCommand(command, false, value);
        updateToolbarState(activeEditor.closest('.content-item-body').querySelector('.editor-toolbar'));
    }
    function updateToolbarState(toolbar) { if (!toolbar) return; toolbar.querySelectorAll('[data-action]').forEach(button => { if (button.tagName !== 'SELECT' && document.queryCommandState(button.dataset.action)) { button.classList.add('is-active'); } else { button.classList.remove('is-active'); } }); }
    document.getElementById('apply-link-btn').addEventListener('click', () => {
        const url = document.getElementById('link-url').value;
        if (url && activeEditor) { activeEditor.focus(); restoreSelection(savedSelection); document.execCommand('createLink', false, url); }
        linkModal.classList.remove('show'); document.getElementById('link-url').value = '';
    });
    document.getElementById('cancel-link-btn').addEventListener('click', () => { linkModal.classList.remove('show'); });
});
</script>
</body>
</html>