<?php
// File: edit_module_content.php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$pdo = get_db_connection();

$module_id = $_GET['id'] ?? null;
if (!$module_id) { die("Bölüm ID'si bulunamadı."); }

// Bölüm gerçekten bu takıma mı ait?
$stmt = $pdo->prepare("
    SELECT m.id, m.course_id, m.title, c.team_db_id
    FROM course_modules m
    JOIN courses c ON c.id = m.course_id
    WHERE m.id = ? AND c.team_db_id = ?
");
$stmt->execute([$module_id, $_SESSION['team_db_id']]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) { die("Hata: Bu bölümü düzenleme yetkiniz yok veya bölüm bulunamadı."); }

// Mevcut içerikleri çek
$stmtC = $pdo->prepare("
    SELECT id, type, data, data_file, sort_order
    FROM module_contents
    WHERE module_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmtC->execute([$module_id]);
$contents = $stmtC->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Bölüm İçeriğini Düzenle";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_curriculum.css">
    <style>
        /* ufak yardımcılar, senin css’inle uyumlu */
        .card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
        .module-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
        .module-header h3{ font-weight:700; font-size:20px; }
        .content-item{ border:1px dashed #e5e7eb; border-radius:12px; padding:12px; background:#fff; }
        .content-item-header{ display:flex; align-items:center; gap:8px; border-bottom:1px solid #f3f4f6; padding-bottom:8px; margin-bottom:8px; }
        .content-item-title{ font-weight:600; }
        .action-button{ padding:6px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; }
        .action-button.disabled{ opacity:.4; pointer-events:none; }
        .editor-toolbar{ display:flex; gap:8px; border:1px solid #e5e7eb; border-radius:10px; padding:6px; margin-bottom:8px; }
        .editor-button{ padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
        .editor-button.is-active{ border-color:#f59e0b; box-shadow:0 0 0 2px rgb(245 158 11 / 20%); }
        .editable-content{ min-height:140px; border:1px solid #e5e7eb; border-radius:10px; padding:10px; outline:none; }
        .horizontal-upload-card{ display:flex; gap:12px; align-items:center; border:1px dashed #e5e7eb; border-radius:12px; padding:14px; }
        .upload-drop-area{ cursor:pointer; }
        .upload-drop-area.drag-over{ border:2px dashed #f59e0b; }
        .file-display{ display:flex; align-items:center; gap:12px; }
        .file-name{ font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .file-actions{ display:flex; gap:8px; }
        .btn{ background:#f59e0b; color:#111827; padding:.6rem 1rem; border-radius:.75rem; font-weight:700; }
        .btn:hover{ filter:brightness(.95); }
        .btn-secondary{ background:#eef2ff; color:#1f2937; }
        .btn-sm{ padding:.45rem .8rem; font-size:.875rem; }
        .modal-overlay{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.4); }
        .modal-overlay.show{ display:flex; }
        .modal-content{ background:#fff; width:min(640px, 92vw); border-radius:12px; overflow:hidden; }
        .form-input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once $projectRoot . '/navbar.php'; ?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($page_title) ?></h1>
            <p class="text-gray-600">Bölüm: <strong><?= htmlspecialchars($module['title']) ?></strong></p>
        </div>
        <a href="view_curriculum.php?id=<?= (int)$module['course_id'] ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Geri Dön</a>
    </div>

    <form id="content-form" action="update_module_content.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="module_id" value="<?= (int)$module['id'] ?>">
        <div class="module-card">
            <div class="module-header">
                <h3>İçerik Öğeleri</h3>
            </div>

            <div class="module-content">
                <div class="content-items-container space-y-4" id="content-items">
                    <?php
                    // Mevcut içerikleri render et
                    foreach ($contents as $row):
                        $ckey = 'exist_'.$row['id'];
                        $type = $row['type'];
                        $icon = $type === 'text' ? 'type' : ($type === 'video' ? 'video' : 'file-text');
                        $typeText = $type === 'text' ? 'Metin İçeriği' : ($type === 'video' ? 'Video' : 'Döküman');
                        $paragraph = $row['paragraph'] ?? '';
                        $filePath = $row['file_path'] ?? '';
                        ?>
                        <div class="content-item">
                            <div class="content-item-header">
                                <i class="text-gray-500"></i>
                                <span class="content-item-title"><?= $typeText ?></span>
                                <div class="ml-auto flex items-center gap-2">
                                    <button type="button" class="action-button move-up-btn" title="Yukarı Taşı"><i data-lucide="chevron-up"></i></button>
                                    <button type="button" class="action-button move-down-btn" title="Aşağı Taşı"><i data-lucide="chevron-down"></i></button>
                                    <button type="button" class="action-button delete-content-btn text-red-500" title="Sil"><i data-lucide="trash-2"></i></button>
                                </div>
                            </div>
                            <div class="content-item-body">
                                <?php if ($type === 'text'): ?>
                                    <div class="editor-toolbar">
                                        <div class="toolbar-group flex gap-1">
                                            <button type="button" class="editor-button" data-action="bold" title="Kalın"><i data-lucide="bold"></i></button>
                                            <button type="button" class="editor-button" data-action="italic" title="İtalik"><i data-lucide="italic"></i></button>
                                        </div>
                                        <div class="toolbar-group flex gap-1">
                                            <button type="button" class="editor-button" data-action="insertUnorderedList" title="Liste"><i data-lucide="list"></i></button>
                                            <button type="button" class="editor-button" data-action="insertOrderedList" title="Numaralı Liste"><i data-lucide="list-ordered"></i></button>
                                        </div>
                                        <div class="toolbar-group">
                                            <button type="button" class="editor-button" data-action="createLink" title="Link"><i data-lucide="link"></i></button>
                                        </div>
                                    </div>
                                    <div class="editable-content" contenteditable="true" spellcheck="false"><?= $paragraph ? $paragraph : '<p>Metninizi buraya yazın...</p>' ?></div>
                                    <textarea class="hidden" name="contents[<?= $ckey ?>][paragraph]"><?= htmlspecialchars($paragraph) ?></textarea>
                                <?php else: ?>
                                    <div class="upload-container">
                                        <div class="file-display <?= $filePath ? '' : 'hidden' ?>">
                                            <?php if ($filePath): ?>
                                                <div class="upload-preview-thumb">
                                                    <?php if ($type === 'video'): ?>
                                                        <video src="<?= htmlspecialchars($filePath) ?>"></video>
                                                    <?php else: ?>
                                                        <i data-lucide="file-text"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow min-w-0">
                                                    <div class="file-name" title="<?= htmlspecialchars(basename($filePath)) ?>"><?= htmlspecialchars(basename($filePath)) ?></div>
                                                    <div class="file-size">Kaydedilmiş dosya</div>
                                                </div>
                                                <div class="file-actions">
                                                    <button type="button" class="action-button preview-btn" data-prev="<?= htmlspecialchars($filePath) ?>" data-kind="<?= $type ?>" title="Önizle"><i data-lucide="eye"></i></button>
                                                    <button type="button" class="action-button change-btn" title="Değiştir"><i data-lucide="refresh-cw"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="horizontal-upload-card <?= $filePath ? 'hidden' : '' ?>">
                                            <div class="upload-preview-thumb"><i data-lucide="<?= $icon ?>"></i></div>
                                            <div class="upload-drop-area">
                                                <p>Dosyayı buraya sürükleyin veya <span class="font-bold text-yellow-600">tıklayın</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="file"
                                           class="hidden-file-input"
                                           name="contents[<?= $ckey ?>][file]"
                                           accept="<?= $type === 'video' ? 'video/*' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx' ?>"
                                           style="display:none;">
                                    <input type="hidden" name="contents[<?= $ckey ?>][existing_file]" value="<?= htmlspecialchars($filePath) ?>">
                                <?php endif; ?>
                            </div>

                            <input type="hidden" name="contents[<?= $ckey ?>][type]" value="<?= htmlspecialchars($type) ?>">
                            <input type="hidden" name="contents[<?= $ckey ?>][sort_order]" value="<?= (int)$row['sort_order'] ?>">
                            <input type="hidden" name="contents[<?= $ckey ?>][id]" value="<?= (int)$row['id'] ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Ekleme butonları -->
                <div class="flex gap-4 mt-6 border-t pt-4">
                    <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="text"><i data-lucide="type" class="w-4 h-4 mr-1"></i>Metin</button>
                    <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="video"><i data-lucide="video" class="w-4 h-4 mr-1"></i>Video</button>
                    <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="document"><i data-lucide="file-plus" class="w-4 h-4 mr-1"></i>Döküman</button>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mt-8">
            <a href="panel.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Panele Geri Dön</a>
            <div class="flex gap-4">
                <a href="edit_module_title.php?id=<?= (int)$module['id'] ?>" class="btn btn-secondary">Başlığı Düzenle</a>
                <button type="submit" class="btn text-lg"><i data-lucide="save" class="mr-2"></i>İçeriği Kaydet</button>
            </div>
        </div>
    </form>
</div>

<!-- Link Modal -->
<div id="link-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">Link Ekle/Düzenle</h2>
            <div class="space-y-2">
                <label for="link-url" class="font-medium text-gray-700">URL Adresi</label>
                <input type="url" id="link-url" class="form-input p-2 border" placeholder="https://www.example.com">
            </div>
        </div>
        <div class="bg-gray-100 p-4 flex justify-end gap-4 rounded-b-lg">
            <button type="button" id="cancel-link-btn" class="btn btn-secondary">İptal</button>
            <button type="button" id="apply-link-btn" class="btn">Linki Uygula</button>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="modal-overlay">
    <div class="modal-content">
        <div id="preview-modal-content" class="bg-black"></div>
        <div class="bg-gray-100 p-4 flex justify-end gap-4 rounded-b-lg">
            <button type="button" id="preview-modal-close" class="btn btn-secondary">Kapat</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        lucide.createIcons();

        // === TÜR LİMİTLERİ ===
        const TYPE_LIMITS = { text: 1, video: 1, document: 1 }; // istersen text:999 yap

        const contentContainer = document.getElementById('content-items');
        const addBtns = document.querySelectorAll('.add-content-btn');

        // Yardımcılar
        function getTypeCount(type) {
            return contentContainer.querySelectorAll(`input[name*="[type]"][value="${type}"]`).length;
        }
        function canAdd(type) {
            const limit = TYPE_LIMITS[type] ?? Infinity;
            return getTypeCount(type) < limit;
        }
        function refreshAddButtons() {
            addBtns.forEach(btn => {
                const type = btn.dataset.type;
                const allowed = canAdd(type);
                btn.disabled = !allowed;
                btn.classList.toggle('opacity-50', !allowed);
                btn.classList.toggle('cursor-not-allowed', !allowed);
            });
        }

        // Mevcut öğelerde listener’ları bağla
        contentContainer.querySelectorAll('.content-item').forEach(item => {
            attachContentItemListeners(item, item.querySelector('input[name*="[type]"]').value);
        });
        refreshAddButtons();
        updateSortOrder(contentContainer);

        // İçerik ekleme
        addBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                if (!canAdd(type)) {
                    alert(`"${type}" için maksimum ${TYPE_LIMITS[type]} öğe eklenebilir.`);
                    return;
                }
                addContentItem(type);
                refreshAddButtons();
            });
        });

        function addContentItem(type, contentData = {}) {
            const key = `new_${Date.now()}`;
            const typeText = type === 'text' ? 'Metin İçeriği' : (type === 'video' ? 'Video' : 'Döküman');
            const icon = type === 'text' ? 'type' : (type === 'video' ? 'video' : 'file-text');

            const wrapper = document.createElement('div');
            wrapper.className = 'content-item';
            let bodyHTML = '';

            if (type === 'text') {
                const editorData = contentData.data || '<p>Metninizi buraya yazın...</p>';
                bodyHTML = `
                <div class="editor-toolbar">
                    <div class="toolbar-group flex gap-1">
                        <button type="button" class="editor-button" data-action="bold" title="Kalın"><i data-lucide="bold"></i></button>
                        <button type="button" class="editor-button" data-action="italic" title="İtalik"><i data-lucide="italic"></i></button>
                    </div>
                    <div class="toolbar-group flex gap-1">
                        <button type="button" class="editor-button" data-action="insertUnorderedList" title="Liste"><i data-lucide="list"></i></button>
                        <button type="button" class="editor-button" data-action="insertOrderedList" title="Numaralı Liste"><i data-lucide="list-ordered"></i></button>
                    </div>
                    <div class="toolbar-group">
                        <button type="button" class="editor-button" data-action="createLink" title="Link"><i data-lucide="link"></i></button>
                    </div>
                </div>
                <div class="editable-content" contenteditable="true" spellcheck="false">${editorData}</div>
                <textarea class="hidden" name="contents[${key}][paragraph]">${editorData}</textarea>
            `;
            } else {
                const accept = type === 'video' ? 'video/*' : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx';
                bodyHTML = `
                <div class="upload-container">
                    <div class="file-display hidden"></div>
                    <div class="horizontal-upload-card">
                        <div class="upload-preview-thumb"><i data-lucide="${icon}"></i></div>
                        <div class="upload-drop-area"><p>Dosyayı buraya sürükleyin veya <span class="font-bold text-yellow-600">tıklayın</span></p></div>
                    </div>
                </div>
                <input type="file" class="hidden-file-input" name="contents[${key}][file]" accept="${accept}" style="display:none;">
                <input type="hidden" name="contents[${key}][existing_file]" value="">
            `;
            }

            wrapper.innerHTML = `
            <div class="content-item-header">
                <i class="text-gray-500"></i><span class="content-item-title">${typeText}</span>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" class="action-button move-up-btn" title="Yukarı Taşı"><i data-lucide="chevron-up"></i></button>
                    <button type="button" class="action-button move-down-btn" title="Aşağı Taşı"><i data-lucide="chevron-down"></i></button>
                    <button type="button" class="action-button delete-content-btn text-red-500" title="Sil"><i data-lucide="trash-2"></i></button>
                </div>
            </div>
            <div class="content-item-body">${bodyHTML}</div>
            <input type="hidden" name="contents[${key}][type]" value="${type}">
            <input type="hidden" name="contents[${key}][sort_order]" value="">
        `;

            contentContainer.appendChild(wrapper);
            attachContentItemListeners(wrapper, type);
            updateSortOrder(contentContainer);
            lucide.createIcons({nodes:[wrapper]});
        }

        // === Listener ve Editor ===
        let activeEditor = null, savedSelection = null;

        function attachContentItemListeners(itemElement, type) {
            itemElement.querySelector('.delete-content-btn').addEventListener('click', () => {
                if (confirm('İçeriği silmek istiyor musunuz?')) {
                    itemElement.remove();
                    updateSortOrder(contentContainer);
                    refreshAddButtons();
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
                editor.addEventListener('input', () => { textarea.value = editor.innerHTML; });
                editor.addEventListener('keyup', () => updateToolbarState(toolbar));
                editor.addEventListener('mouseup', () => updateToolbarState(toolbar));
                toolbar.addEventListener('click', (e) => {
                    const button = e.target.closest('button');
                    if (button) handleEditorCommand(button.dataset.action);
                });
            } else {
                const dropArea = itemElement.querySelector('.upload-drop-area');
                const fileInput = itemElement.querySelector('.hidden-file-input');
                const fileDisplay = itemElement.querySelector('.file-display');
                const uploadCard = itemElement.querySelector('.horizontal-upload-card');

                if (dropArea) {
                    dropArea.addEventListener('click', () => fileInput.click());
                    dropArea.addEventListener('dragover', (e) => { e.preventDefault(); dropArea.classList.add('drag-over'); });
                    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
                    dropArea.addEventListener('drop', (e) => {
                        e.preventDefault(); dropArea.classList.remove('drag-over');
                        if (e.dataTransfer.files.length) {
                            fileInput.files = e.dataTransfer.files;
                            showFileInPreview(itemElement, fileInput.files[0]);
                        }
                    });
                }
                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length) showFileInPreview(itemElement, fileInput.files[0]);
                });

                // var olan kayıtlarda preview/change
                const previewBtn = fileDisplay?.querySelector('.preview-btn');
                const changeBtn = fileDisplay?.querySelector('.change-btn');
                if (previewBtn) {
                    previewBtn.addEventListener('click', () => showPreview(previewBtn.dataset.prev, previewBtn.dataset.kind));
                }
                if (changeBtn) changeBtn.addEventListener('click', () => fileInput.click());
            }
        }

        // Sürükle-bırak yerine butonlarla yukarı-aşağı
        function moveContent(button, direction) {
            const item = button.closest('.content-item');
            const container = item.parentElement;
            if (direction === 'up' && item.previousElementSibling) {
                container.insertBefore(item, item.previousElementSibling);
            } else if (direction === 'down' && item.nextElementSibling) {
                container.insertBefore(item.nextElementSibling, item);
            }
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

        // Dosya önizleme
        function showFileInPreview(itemElement, fileObject) {
            const type = itemElement.querySelector('input[name*="[type]"]').value;
            const uploadContainer = itemElement.querySelector('.upload-container');
            const fileDisplay = uploadContainer.querySelector('.file-display');
            const uploadCard = uploadContainer.querySelector('.horizontal-upload-card');

            const filePath = URL.createObjectURL(fileObject);
            fileDisplay.innerHTML = `
            <div class="upload-preview-thumb">
                ${type === 'video' ? `<video src="${filePath}"></video>` : '<i data-lucide="file-text"></i>'}
            </div>
            <div class="flex-grow min-w-0">
                <div class="file-name" title="${fileObject.name}">${fileObject.name}</div>
                <div class="file-size">${(fileObject.size / 1024 / 1024).toFixed(2)} MB</div>
            </div>
            <div class="file-actions">
                <button type="button" class="action-button preview-btn" title="Önizle"><i data-lucide="eye"></i></button>
                <button type="button" class="action-button change-btn" title="Değiştir"><i data-lucide="refresh-cw"></i></button>
            </div>
        `;
            fileDisplay.querySelector('.preview-btn').addEventListener('click', () => showPreview(filePath, type));
            fileDisplay.querySelector('.change-btn').addEventListener('click', () => itemElement.querySelector('.hidden-file-input').click());
            uploadCard.classList.add('hidden');
            fileDisplay.classList.remove('hidden');
            lucide.createIcons({nodes:[fileDisplay]});
        }

        function showPreview(filePath, fileType) {
            let content = '';
            const ext = (filePath.split('.').pop() || '').toLowerCase();
            if (fileType === 'video') {
                content = `<video src="${filePath}" controls autoplay style="width:100%; height:70vh;"></video>`;
            } else if (fileType === 'document') {
                if (ext === 'pdf' || filePath.startsWith('blob:')) {
                    content = `<iframe src="${filePath}" style="width:100%; height:70vh;"></iframe>`;
                } else if (['doc','docx','ppt','pptx','xls','xlsx'].includes(ext) && !filePath.startsWith('blob:')) {
                    const viewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
                    content = `<iframe src="${viewerUrl}" style="width:100%; height:70vh;"></iframe>`;
                } else {
                    content = `<div class="p-8 text-center"><h3>Bu dosya türü için önizleme desteklenmiyor veya dosyanın önce kaydedilmesi gerekiyor.</h3></div>`;
                }
            }
            document.getElementById('preview-modal-content').innerHTML = content;
            document.getElementById('preview-modal').classList.add('show');
        }
        document.getElementById('preview-modal-close').addEventListener('click', () => {
            document.getElementById('preview-modal').classList.remove('show');
            document.getElementById('preview-modal-content').innerHTML = '';
        });

        // Metin editörü
        function saveSelection() {
            if (window.getSelection) {
                const sel = window.getSelection();
                if (sel.getRangeAt && sel.rangeCount) return sel.getRangeAt(0);
            }
            return null;
        }
        function restoreSelection(range) {
            if (range && window.getSelection) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
        function handleEditorCommand(command, value = null) {
            if (!command || !activeEditor) return;
            activeEditor.focus();
            if (command === 'createLink') {
                savedSelection = saveSelection();
                if(!savedSelection || savedSelection.collapsed) { alert("Lütfen link eklemek için bir metin seçin."); return; }
                document.getElementById('link-modal').classList.add('show'); return;
            }
            document.execCommand(command, false, value);
            const toolbar = activeEditor.closest('.content-item-body').querySelector('.editor-toolbar');
            updateToolbarState(toolbar);
        }
        function updateToolbarState(toolbar) {
            if (!toolbar) return;
            toolbar.querySelectorAll('[data-action]').forEach(btn => {
                if (btn.tagName !== 'SELECT' && document.queryCommandState(btn.dataset.action)) {
                    btn.classList.add('is-active');
                } else {
                    btn.classList.remove('is-active');
                }
            });
        }
        document.getElementById('apply-link-btn').addEventListener('click', () => {
            const url = (document.getElementById('link-url').value || '').trim();
            if (url && activeEditor) {
                activeEditor.focus();
                restoreSelection(savedSelection);
                document.execCommand('createLink', false, url);
            }
            document.getElementById('link-modal').classList.remove('show');
            document.getElementById('link-url').value = '';
        });
        document.getElementById('cancel-link-btn').addEventListener('click', () => {
            document.getElementById('link-modal').classList.remove('show');
        });

        // textarea değer eşitleme (delegation)
        contentContainer.addEventListener('input', (e) => {
            const editor = e.target.closest('.editable-content');
            if (editor) {
                const textarea = editor.parentElement.querySelector('textarea');
                if (textarea) textarea.value = editor.innerHTML;
            }
        });

    });
</script>
</body>
</html>
