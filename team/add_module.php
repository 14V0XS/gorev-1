<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }

$projectRoot = dirname(__DIR__); // Ör: C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

$pdo = get_db_connection();

/** course_id'ı erken doğrula */
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) { die("Kurs ID'si bulunamadı."); }

/** Kapak kontrolü */
$chk = $pdo->prepare("
  SELECT cover_image_url
  FROM courses
  WHERE id = ? AND team_db_id = ?
  LIMIT 1
");
$chk->execute([$course_id, (int)$_SESSION['team_db_id']]);
$row = $chk->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['cover_image_url'])) {
    header('Location: upload_content.php?id=' . $course_id . '&err=cover_required');
    exit;
}

/** Kursta yetki kontrolü */
$course = getCourseDetailsById($course_id);
if ($_SESSION['team_db_id'] != $course['team_db_id']) { header('Location: ../index.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Yeni Bölüm Ekle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_curriculum.css">
    <style>
        .loading-overlay { position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.55); backdrop-filter: blur(2px); }
        .loading-overlay.show { display: flex; }
        .loading-box { background: #E5AE32; color: #ffffff; border: 1px solid #ac7e25; border-radius: 14px; padding: 18px 22px; min-width: 260px; display: flex; align-items: center; gap: 12px; box-shadow: 0 18px 48px rgba(2,6,23,.35); }
        .spinner { width: 22px; height: 22px; border-radius: 50%; border: 3px solid rgba(228, 228, 228, 0.25); border-top-color: #ffffff; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { font-weight: 700; }
        .loading-sub { font-size: 12px; opacity: .75; margin-top: 2px; }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once $projectRoot . '/navbar.php'; ?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Yeni Bölüm Ekle</h1>
            <p class="text-gray-600">Bir başlık, bir metin; isteğe bağlı olarak video, doküman ve soru ekle.</p>
        </div>
        <a href="view_curriculum.php?id=<?= (int)$course_id ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Geri Dön</a>
    </div>

    <form id="curriculum-form" action="save_module.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?php echo (int)$course_id; ?>">
        <div id="modules-container"></div>

        <div id="add-module-area" class="mt-6">
            <div id="add-module-prompt" class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-yellow-500 hover:text-yellow-600 transition flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="plus"></i> Bölüm Ekle
            </div>
            <div id="add-module-form" class="card hidden">
                <label for="new_module_title" class="font-bold text-lg mb-2 block">Bölüm Adı</label>
                <input type="text" id="new_module_title" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Örn: Giriş ve Temel Kavramlar">
                <div class="flex gap-4 mt-4">
                    <button type="button" id="add-module-btn" class="btn">Bölümü Ekle</button>
                    <button type="button" id="cancel-add-module-btn" class="btn btn-secondary">İptal</button>
                </div>
            </div>
        </div>

        <div class="text-right mt-8">
            <button type="submit" class="btn text-lg">
                <i data-lucide="save" class="w-5 h-5 mr-2"></i>İçeriği Kaydet ve Bitir
            </button>
        </div>
    </form>
</div>

<!-- Link Modal -->
<div id="link-modal" class="modal-overlay hidden">
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

<!-- Paste Modal -->
<div id="paste-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-2">Panodan yapıştır</h2>
            <p class="text-gray-600">Panodaki kısa metni doğrudan içerik olarak eklemek ister misin?</p>
        </div>
        <div class="bg-gray-100 p-4 flex justify-end gap-4 rounded-b-lg">
            <button type="button" id="paste-cancel-btn" class="btn btn-secondary">Düzenleyici aç</button>
            <button type="button" id="paste-confirm-btn" class="btn">Panodan yapıştır</button>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div id="preview-modal-content" class="p-0"></div>
        <div class="bg-gray-100 p-4 flex justify-end rounded-b-lg">
            <button type="button" id="preview-modal-close" class="btn btn-secondary">Kapat</button>
        </div>
    </div>
</div>

<div id="loading-overlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-box">
        <div class="spinner" aria-hidden="true"></div>
        <div>
            <div class="loading-text">Yükleniyor…</div>
            <div class="loading-sub">Büyük dosyalarda bu işlem birkaç saniye sürebilir.</div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const TYPE_LIMITS = { text: 100, video: 50, document: 50, youtube: 50, question: 100 };

        let WARN_ON_UNLOAD = false;
        let isFormDirty = false;
        let activeEditor = null, savedSelection = null;

        function handleBeforeUnload(e) {
            if (!WARN_ON_UNLOAD) return;
            e.preventDefault();
            e.returnValue = '';
        }
        window.addEventListener('beforeunload', handleBeforeUnload);

        // Helpers
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

        function parseYouTubeId(input) {
            if (!input) return null;
            input = input.trim();
            if (/^[a-zA-Z0-9_-]{10,20}$/.test(input)) return input;
            try {
                const u = new URL(input);
                if (u.hostname.includes('youtu.be')) {
                    const id = u.pathname.split('/').filter(Boolean)[0];
                    return id || null;
                }
                if (u.hostname.includes('youtube.com')) {
                    if (u.pathname.startsWith('/shorts/')) {
                        const id = u.pathname.split('/').filter(Boolean)[1];
                        return id || null;
                    }
                    const v = u.searchParams.get('v');
                    if (v) return v;
                    if (u.pathname.startsWith('/embed/')) {
                        const id = u.pathname.split('/').filter(Boolean)[1];
                        return id || null;
                    }
                }
            } catch (_) {}
            return null;
        }

        function buildYouTubePreviewHTML(embedId) {
            return `
        <div class="mt-3">
          <div class="aspect-video">
            <iframe
              class="w-full h-full"
              src="https://www.youtube.com/embed/${encodeURIComponent(embedId)}"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen>
            </iframe>
          </div>
        </div>
    `;
        }

        lucide.createIcons();

        const curriculumForm   = document.getElementById('curriculum-form');
        const modulesContainer = document.getElementById('modules-container');
        const addModuleArea    = document.getElementById('add-module-area');
        const addModulePrompt  = document.getElementById('add-module-prompt');
        const addModuleForm    = document.getElementById('add-module-form');
        const addModuleBtn     = document.getElementById('add-module-btn');
        const cancelAddModuleBtn = document.getElementById('cancel-add-module-btn');
        const newModuleTitleInput = document.getElementById('new_module_title');

        const linkModal   = document.getElementById('link-modal');
        const pasteModal  = document.getElementById('paste-modal');
        const previewModal= document.getElementById('preview-modal');

        curriculumForm.addEventListener('input', () => { isFormDirty = true; WARN_ON_UNLOAD = true; });

        // === Bölüm yönetimi ===
        addModulePrompt.addEventListener('click', () => {
            addModulePrompt.classList.add('hidden');
            addModuleForm.classList.remove('hidden');
            newModuleTitleInput.focus();
        });
        cancelAddModuleBtn.addEventListener('click', () => {
            addModuleForm.classList.add('hidden');
            addModulePrompt.classList.remove('hidden');
        });
        addModuleBtn.addEventListener('click', () => {
            const title = newModuleTitleInput.value.trim();
            if (title) {
                createModule({ title });
                addModuleArea.style.display = 'none';
            } else {
                alert('Bölüm adı boş bırakılamaz.');
            }
        });

        function createModule(moduleData) {
            const moduleKey = `new_${Date.now()}`;
            const moduleElement = document.createElement('div');
            moduleElement.className = 'module-card';
            moduleElement.innerHTML = `
      <div class="module-header">
        <h3 class="flex-grow">${moduleData.title}</h3>
        <input type="hidden" name="modules[${moduleKey}][title]" value="${moduleData.title}">
      </div>
      <div class="module-content">
        <div class="content-items-container space-y-4"></div>
        <div class="flex gap-4 mt-6 border-t pt-4">
          <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="text">
            <i data-lucide="type" class="w-4 h-4 mr-1"></i>Metin
          </button>
          <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="video">
            <i data-lucide="video" class="w-4 h-4 mr-1"></i>Video
          </button>
          <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="document">
            <i data-lucide="file-plus" class="w-4 h-4 mr-1"></i>Döküman
          </button>
          <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="question">
            <i data-lucide="help-circle" class="w-4 h-4 mr-1"></i>Soru
          </button>
          <button type="button" class="add-content-btn btn btn-sm btn-secondary" data-type="youtube">
            <i data-lucide="link-2" class="w-4 h-4 mr-1"></i>YouTube
          </button>
        </div>
      </div>`;
            modulesContainer.innerHTML = '';
            modulesContainer.appendChild(moduleElement);
            attachModuleListeners(moduleElement, moduleKey);
            lucide.createIcons();
            refreshAddButtons(moduleElement);
        }

        // === İçerik yönetimi ===
        function attachModuleListeners(moduleElement, moduleKey) {
            const contentContainer = moduleElement.querySelector('.content-items-container');
            makeSortable(contentContainer);

            moduleElement.querySelectorAll('.add-content-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const type = btn.dataset.type;

                    if (!canAdd(contentContainer, type)) {
                        alert(`Bu modülde "${type}" içeriği en fazla ${TYPE_LIMITS[type]} kez eklenebilir.`);
                        return;
                    }

                    if (type === 'text') {
                        try {
                            const clipboardText = await navigator.clipboard.readText();
                            if (clipboardText.trim() && clipboardText.length < 750) {
                                if (pasteModal) pasteModal.classList.remove('hidden'), pasteModal.classList.add('show');
                                const confirmBtn = document.getElementById('paste-confirm-btn');
                                const cancelBtn  = document.getElementById('paste-cancel-btn');
                                if (confirmBtn) confirmBtn.onclick = () => {
                                    addContentItem(contentContainer, type, moduleKey, { data: clipboardText });
                                    if (pasteModal) pasteModal.classList.add('hidden'), pasteModal.classList.remove('show');
                                    refreshAddButtons(moduleElement);
                                };
                                if (cancelBtn) cancelBtn.onclick = () => {
                                    addContentItem(contentContainer, type, moduleKey);
                                    if (pasteModal) pasteModal.classList.add('hidden'), pasteModal.classList.remove('show');
                                    refreshAddButtons(moduleElement);
                                };
                            } else {
                                throw new Error("Pano boş veya limit aşıldı");
                            }
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
            let typeText = '';
            let icon = '';
            let bodyHTML = '';

            switch (type) {
                case 'text':     typeText = 'Metin İçeriği'; icon = 'type'; break;
                case 'video':    typeText = 'Video';         icon = 'video'; break;
                case 'document': typeText = 'Döküman';       icon = 'file-text'; break;
                case 'youtube':  typeText = 'YouTube';       icon = 'link-2'; break;
                case 'question': typeText = 'Soru';          icon = 'help-circle'; break;
                default:         typeText = 'İçerik';        icon = 'file';
            }

            if (type === 'text') {
                const editorData = data || '<p>Metninizi buraya yazın...</p>';
                bodyHTML = `
            <div class="editor-toolbar">
              <button type="button" class="editor-button" data-action="bold" title="Kalın"><i data-lucide="bold"></i></button>
              <button type="button" class="editor-button" data-action="italic" title="İtalik"><i data-lucide="italic"></i></button>
              <button type="button" class="editor-button" data-action="insertUnorderedList" title="Liste"><i data-lucide="list"></i></button>
              <button type="button" class="editor-button" data-action="insertOrderedList" title="Numaralı Liste"><i data-lucide="list-ordered"></i></button>
              <button type="button" class="editor-button" data-action="createLink" title="Link"><i data-lucide="link"></i></button>
            </div>
            <div class="editable-content" contenteditable="true" spellcheck="false">${editorData}</div>
            <textarea class="hidden" name="modules[${moduleKey}][contents][${contentKey}][paragraph]">${editorData}</textarea>
        `;
            } else if (type === 'youtube') {
                const initial = (data || '').trim();
                bodyHTML = `
            <div class="space-y-2">
              <label class="font-medium text-gray-700">YouTube URL veya Video ID</label>
              <div class="flex gap-2">
                <input type="text" class="yt-url-input w-full p-2 border border-gray-300 rounded-lg"
                  placeholder="https://youtu.be/dQw4w9WgXcQ veya video ID" value="${initial}">
                <button type="button" class="yt-apply btn btn-sm">Uygula</button>
                <button type="button" class="yt-clear btn btn-sm btn-secondary">Temizle</button>
              </div>
              <p class="yt-error text-red-600 text-sm" style="display:none;"></p>
              <div class="yt-preview"></div>
              <input type="hidden"
                name="modules[${moduleKey}][contents][${contentKey}][youtube_url]"
                class="yt-hidden" value="${initial}">
            </div>
        `;
            } else if (type === 'question') {
                const qText = (contentData.questionText || '').replace(/"/g, '&quot;');
                const radioName = `qcorrect_${moduleKey}_${contentKey}`;
                bodyHTML = `
<div class="space-y-3">
  <label class="font-medium text-gray-700">Soru İçeriği</label>
  <textarea class="q-text w-full p-2 border border-gray-300 rounded-lg" rows="3"
    name="modules[${moduleKey}][contents][${contentKey}][question][text]"
    placeholder="Soru metnini yazın...">${qText || ''}</textarea>

  <div class="flex items-center justify-between mt-2">
    <span class="font-medium text-gray-700">Şıklar (maks. 5) • Doğru şıkkı işaretle</span>
    <button type="button" class="q-add-option btn btn-sm">Şık Ekle</button>
  </div>

  <div class="q-options space-y-2" data-rname="${radioName}">
    ${[0,1].map((i)=>`
      <div class="q-option flex items-center gap-2">
        <input type="radio" class="q-correct" name="${radioName}" value="${i}" title="Doğru şık">
        <input type="text" class="q-option-input flex-1 p-2 border border-gray-300 rounded-lg"
          name="modules[${moduleKey}][contents][${contentKey}][question][choices][]" placeholder="Şık ${i+1}">
        <button type="button" class="q-del-option action-button" title="Sil">
          <i data-lucide="trash-2"></i>
        </button>
      </div>`).join('')}
  </div>

  <div class="space-y-2 mt-3">
    <label class="font-medium text-gray-700">Cevap açıklaması (opsiyonel)</label>
    <textarea class="q-expl w-full p-2 border border-gray-300 rounded-lg" rows="3"
      maxlength="1000"
      placeholder="Doğru cevabın neden doğru olduğunu açıklayın (opsiyonel)."
      name="modules[${moduleKey}][contents][${contentKey}][question][explanation]"></textarea>
    <p class="text-xs text-gray-500">Maksimum 1000 karakter.</p>
  </div>

  <input type="hidden" class="q-answers-payload"
    name="modules[${moduleKey}][contents][${contentKey}][question][answers_payload]" value="">
  <p class="q-error text-red-600 text-sm" style="display:none;"></p>
</div>
`;
            } else {
                const accept = (type === 'video') ? 'video/*' : '.pdf,.doc,.docx,.ppt,.pptx';
                bodyHTML = `
            <div class="upload-container">
              <div class="file-display hidden"></div>
              <div class="horizontal-upload-card">
                <div class="upload-preview-thumb"><i data-lucide="${icon}"></i></div>
                <div class="upload-drop-area">
                  <p>Dosyayı buraya sürükleyin veya <span class="font-bold text-yellow-600">tıklayın</span></p>
                </div>
              </div>
            </div>
            <input type="file" class="hidden-file-input"
                   name="modules[${moduleKey}][contents][${contentKey}][file]"
                   accept="${accept}" style="display:none;">
            <input type="hidden"
                   name="modules[${moduleKey}][contents][${contentKey}][existing_file]"
                   value="${data}">
        `;
            }

            itemElement.innerHTML = `
        <div class="content-item-header">
          <i class="text-gray-500"></i>
          <span class="content-item-title">${typeText}</span>
          <div class="ml-auto flex items-center gap-2">
            <button type="button" class="action-button move-up-btn" title="Yukarı Taşı"><i data-lucide="chevron-up"></i></button>
            <button type="button" class="action-button move-down-btn" title="Aşağı Taşı"><i data-lucide="chevron-down"></i></button>
            <button type="button" class="action-button delete-content-btn text-red-500" title="Sil"><i data-lucide="trash-2"></i></button>
          </div>
        </div>
        <div class="content-item-body">${bodyHTML}</div>
        <input type="hidden" name="modules[${moduleKey}][contents][${contentKey}][type]" value="${type}">
        <input type="hidden" name="modules[${moduleKey}][contents][${contentKey}][sort_order]" value="">
    `;

            container.appendChild(itemElement);
            updateSortOrder(container);
            attachContentItemListeners(itemElement, type, moduleKey, contentKey);
            lucide.createIcons();
        }

        function attachContentItemListeners(itemElement, type, moduleKey, contentKey) {
            itemElement.querySelector('.delete-content-btn').addEventListener('click', () => {
                if (confirm('İçeriği sil?')) {
                    const moduleEl = itemElement.closest('.module-card');
                    itemElement.remove();
                    updateSortOrder(itemElement.parentElement);
                    refreshAddButtons(moduleEl);
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                }
            });
            itemElement.querySelector('.move-up-btn').addEventListener('click', (e) => moveContent(e.currentTarget, 'up'));
            itemElement.querySelector('.move-down-btn').addEventListener('click', (e) => moveContent(e.currentTarget, 'down'));

            if (type === 'text') {
                const editor   = itemElement.querySelector('.editable-content');
                const toolbar  = itemElement.querySelector('.editor-toolbar');
                const textarea = itemElement.querySelector('textarea');

                editor.addEventListener('focus', () => { activeEditor = editor; });
                editor.addEventListener('blur', () => { savedSelection = saveSelection(); });
                editor.addEventListener('input', () => { textarea.value = editor.innerHTML; isFormDirty = true; WARN_ON_UNLOAD = true; });
                editor.addEventListener('keyup', () => updateToolbarState(toolbar));
                editor.addEventListener('mouseup', () => updateToolbarState(toolbar));
                toolbar?.addEventListener('click', (e) => {
                    const button = e.target.closest('button');
                    if (button) handleEditorCommand(button.dataset.action);
                });

            } else if (type === 'youtube') {
                const urlInput = itemElement.querySelector('.yt-url-input');
                const applyBtn = itemElement.querySelector('.yt-apply');
                const clearBtn = itemElement.querySelector('.yt-clear');
                const err   = itemElement.querySelector('.yt-error');
                const prev  = itemElement.querySelector('.yt-preview');
                const hidden= itemElement.querySelector('.yt-hidden');

                function setPreviewFromInput() {
                    err.style.display = 'none';
                    const id = parseYouTubeId(urlInput.value);
                    if (!id) {
                        prev.innerHTML = '';
                        hidden.value = '';
                        err.textContent = 'Geçerli bir YouTube bağlantısı veya video ID girin.';
                        err.style.display = 'block';
                        return;
                    }
                    hidden.value = `https://youtu.be/${id}`;
                    prev.innerHTML = buildYouTubePreviewHTML(id);
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                }

                applyBtn.addEventListener('click', setPreviewFromInput);
                urlInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); setPreviewFromInput(); } });
                clearBtn.addEventListener('click', () => {
                    urlInput.value = '';
                    hidden.value = '';
                    err.style.display = 'none';
                    prev.innerHTML = '';
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                });

                if (urlInput.value.trim()) {
                    const id = parseYouTubeId(urlInput.value.trim());
                    if (id) prev.innerHTML = buildYouTubePreviewHTML(id);
                }

            } else if (type === 'question') {
                const optionsWrap = itemElement.querySelector('.q-options');
                const addBtn      = itemElement.querySelector('.q-add-option');
                const errEl       = itemElement.querySelector('.q-error');
                const payloadEl   = itemElement.querySelector('.q-answers-payload');
                const explEl      = itemElement.querySelector('.q-expl');

                function optionRows() { return [...optionsWrap.querySelectorAll('.q-option')]; }
                function optionCount() { return optionsWrap.querySelectorAll('.q-option').length; }

                function refreshError(msg='') {
                    if (msg) { errEl.textContent = msg; errEl.style.display = 'block'; }
                    else { errEl.style.display = 'none'; errEl.textContent = ''; }
                }

                function reindexOptions() {
                    optionRows().forEach((row, idx) => {
                        const rb  = row.querySelector('.q-correct');
                        const inp = row.querySelector('.q-option-input');
                        if (rb)  rb.value = String(idx);
                        if (inp && !inp.value) inp.placeholder = `Şık ${idx+1}`;
                    });
                }

                function addOption(initialValue='') {
                    if (optionCount() >= 5) { refreshError('En fazla 5 şık ekleyebilirsiniz.'); return; }
                    const row = document.createElement('div');
                    row.className = 'q-option flex items-center gap-2';

                    const radioName = optionsWrap.dataset.rname || `qcorrect_${moduleKey}_${contentKey}`;

                    row.innerHTML = `
<input type="radio" class="q-correct" name="${radioName}" value="${optionCount()}" title="Doğru şık">
<input type="text" class="q-option-input flex-1 p-2 border border-gray-300 rounded-lg"
  name="${optionsWrap.querySelector('.q-option-input')?.name || `modules[${moduleKey}][contents][${contentKey}][question][choices][]`}"
  placeholder="Şık ${optionCount()+1}"
  value="${initialValue.replace(/"/g,'&quot;')}">
<button type="button" class="q-del-option action-button" title="Sil">
  <i data-lucide="trash-2"></i>
</button>
`;
                    optionsWrap.appendChild(row);
                    lucide.createIcons({nodes:[row]});
                    reindexOptions();
                    refreshError('');
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                }

                addBtn.addEventListener('click', () => addOption());

                optionsWrap.addEventListener('click', (e) => {
                    const btn = e.target.closest('.q-del-option');
                    if (!btn) return;
                    const rows = optionRows();
                    if (rows.length <= 2) { refreshError('En az 2 şık bırakmalısınız.'); return; }
                    btn.closest('.q-option').remove();
                    reindexOptions();
                    refreshError('');
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                });

                optionsWrap.addEventListener('mousedown', (e) => {
                    const rb = e.target.closest('.q-correct');
                    if (!rb) return;
                    rb.dataset.wasChecked = rb.checked ? '1' : '0';
                });
                optionsWrap.addEventListener('click', (e) => {
                    const rb = e.target.closest('.q-correct');
                    if (!rb) return;
                    if (rb.dataset.wasChecked === '1') {
                        rb.checked = false;
                        rb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    isFormDirty = true; WARN_ON_UNLOAD = true;
                });

                // Submit öncesi payload oluşturma validasyonu form submit içinde yapılacak.

                // Açıklama alanı değiştiğinde işaretle
                if (explEl) explEl.addEventListener('input', () => { isFormDirty = true; WARN_ON_UNLOAD = true; });
            } else {
                const dropArea = itemElement.querySelector('.upload-drop-area');
                const fileInput = itemElement.querySelector('.hidden-file-input');
                dropArea.addEventListener('click', () => fileInput.click());
                dropArea.addEventListener('dragover', (e) => { e.preventDefault(); dropArea.classList.add('drag-over'); });
                dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
                dropArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropArea.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        showFileInPreview(itemElement, fileInput.files[0]);
                        isFormDirty = true; WARN_ON_UNLOAD = true;
                    }
                });
                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length) {
                        showFileInPreview(itemElement, fileInput.files[0]);
                        isFormDirty = true; WARN_ON_UNLOAD = true;
                    }
                });
            }
        }

        // === Sıralama ===
        function makeSortable(container) {
            let draggingElement = null;
            container.addEventListener('dragstart', e => {
                if (e.target.closest('.drag-handle')) {
                    draggingElement = e.target.closest('.content-item');
                    setTimeout(() => draggingElement.classList.add('dragging'), 0);
                }
            });
            container.addEventListener('dragend', () => {
                if (draggingElement) {
                    draggingElement.classList.remove('dragging');
                    draggingElement = null;
                    updateSortOrder(container);
                }
            });
            container.addEventListener('dragover', e => {
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                if (draggingElement) container.insertBefore(draggingElement, afterElement);
            });
        }
        function getDragAfterElement(container, y) {
            const els = [...container.querySelectorAll('.content-item:not(.dragging)')];
            return els.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
        function moveContent(button, direction) {
            const item = button.closest('.content-item'); if (button.classList.contains('disabled')) return;
            const container = item.parentElement;
            if (direction === 'up' && item.previousElementSibling) { container.insertBefore(item, item.previousElementSibling); }
            else if (direction === 'down' && item.nextElementSibling) { container.insertBefore(item.nextElementSibling, item); }
            updateSortOrder(container);
            isFormDirty = true; WARN_ON_UNLOAD = true;
        }
        function updateSortOrder(container) {
            const items = container.querySelectorAll('.content-item');
            items.forEach((item, index) => {
                item.querySelector('input[name*="[sort_order]"]').value = index;
                item.querySelector('.move-up-btn').classList.toggle('disabled', index === 0);
                item.querySelector('.move-down-btn').classList.toggle('disabled', index === items.length - 1);
            });
        }

        // === Dosya önizleme ===
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
            lucide.createIcons({nodes: [fileDisplay]});
        }

        function showPreview(filePath, fileType) {
            let content = '';
            const fileExtension = filePath.split('.').pop().toLowerCase();

            if (fileType === 'video') {
                content = `<video src="${filePath}" controls autoplay></video>`;
            } else if (fileType === 'document') {
                if (fileExtension === 'pdf') {
                    content = `<iframe src="${filePath}"></iframe>`;
                } else if (['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(fileExtension) && !filePath.startsWith('blob:')) {
                    const viewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
                    content = `<iframe src="${viewerUrl}"></iframe>`;
                } else {
                    content = `<div class="p-8 text-center text-white"><h3>Bu dosya türü için önizleme desteklenmiyor veya dosyanın önce kaydedilmesi gerekiyor.</h3></div>`;
                }
            }

            const pmc = document.getElementById('preview-modal-content');
            if (pmc && previewModal) {
                pmc.innerHTML = content;
                previewModal.classList.remove('hidden');
                previewModal.classList.add('show');
            }
        }

        document.getElementById('preview-modal-close')?.addEventListener('click', () => {
            if (previewModal) {
                previewModal.classList.add('hidden');
                previewModal.classList.remove('show');
            }
        });

        // === Basit WYSIWYG ===
        function saveSelection() { if (window.getSelection) { const sel = window.getSelection(); if (sel.getRangeAt && sel.rangeCount) return sel.getRangeAt(0); } return null; }
        function restoreSelection(range) { if (range && window.getSelection) { const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range); } }
        function handleEditorCommand(command, value = null) {
            if (!command || !activeEditor) return; activeEditor.focus();
            if (command === 'createLink') {
                savedSelection = saveSelection();
                const linkModal = document.getElementById('link-modal');
                if(!savedSelection || savedSelection.collapsed) { alert("Lütfen link eklemek için bir metin seçin."); return; }
                if (linkModal) linkModal.classList.remove('hidden'), linkModal.classList.add('show');
                return;
            }
            document.execCommand(command, false, value);
        }
        function updateToolbarState(toolbar) {
            if (!toolbar) return;
            toolbar.querySelectorAll('[data-action]').forEach(button => {
                if (button.tagName !== 'SELECT' && document.queryCommandState(button.dataset.action)) {
                    button.classList.add('is-active');
                } else {
                    button.classList.remove('is-active');
                }
            });
        }
        document.getElementById('apply-link-btn')?.addEventListener('click', () => {
            const url = document.getElementById('link-url').value;
            if (url && activeEditor) { activeEditor.focus(); restoreSelection(savedSelection); document.execCommand('createLink', false, url); }
            const linkModal = document.getElementById('link-modal');
            if (linkModal) linkModal.classList.add('hidden'), linkModal.classList.remove('show');
            document.getElementById('link-url').value = '';
            isFormDirty = true; WARN_ON_UNLOAD = true;
        });
        document.getElementById('cancel-link-btn')?.addEventListener('click', () => {
            const linkModal = document.getElementById('link-modal');
            if (linkModal) linkModal.classList.add('hidden'), linkModal.classList.remove('show');
        });

        // === Submit validasyonu + overlay ===
        const overlayEl = document.getElementById('loading-overlay');
        const submitBtn = curriculumForm?.querySelector('button[type="submit"]');

        function validateBeforeSubmit() {
            // 1) Modül başlığı dolu mu?
            const moduleTitleInput = document.querySelector('input[name^="modules"][name$="[title]"]');
            if (!moduleTitleInput || moduleTitleInput.value.trim() === '') {
                alert('Bölüm adı boş bırakılamaz.');
                moduleTitleInput?.focus();
                return false;
            }

            // 2) Tüm soru bloklarını kontrol et
            const questionItems = document.querySelectorAll('.content-item input[name*="[type]"][value="question"]');
            for (const typeInput of questionItems) {
                const item = typeInput.closest('.content-item');
                const errEl = item.querySelector('.q-error');

                const qTextEl = item.querySelector('.q-text');
                const optionRows = [...item.querySelectorAll('.q-option')];
                const optionInputs = optionRows.map(r => r.querySelector('.q-option-input'));
                const radios = optionRows.map(r => r.querySelector('.q-correct'));
                const explEl = item.querySelector('.q-expl');

                // 2.1: Soru metni
                if (!qTextEl || qTextEl.value.trim() === '') {
                    if (errEl){ errEl.textContent = 'Soru metni boş olamaz.'; errEl.style.display = 'block'; }
                    qTextEl?.focus();
                    item.scrollIntoView({behavior:'smooth', block:'center'});
                    return false;
                }

                // 2.2: En az 2 DOLU şık
                const nonEmptyOptions = optionInputs.map(i => i.value.trim()).filter(Boolean);
                if (nonEmptyOptions.length < 2) {
                    if (errEl){ errEl.textContent = 'Lütfen en az 2 şık girin.'; errEl.style.display = 'block'; }
                    (optionInputs.find(i => i.value.trim()==='') || optionInputs[0])?.focus();
                    item.scrollIntoView({behavior:'smooth', block:'center'});
                    return false;
                }

                // 2.3: Doğru şık seçilmiş mi?
                const checkedIndex = radios.findIndex(rb => rb && rb.checked);
                if (checkedIndex === -1) {
                    if (errEl){ errEl.textContent = 'Lütfen bir doğru şık seçin.'; errEl.style.display = 'block'; }
                    radios[0]?.focus();
                    item.scrollIntoView({behavior:'smooth', block:'center'});
                    return false;
                }

                // 2.4: Seçilen doğru şık boş mu?
                const correctLabel = optionInputs[checkedIndex]?.value.trim() || '';
                if (!correctLabel) {
                    if (errEl){ errEl.textContent = 'Doğru şık için bir metin girin.'; errEl.style.display = 'block'; }
                    optionInputs[checkedIndex]?.focus();
                    item.scrollIntoView({behavior:'smooth', block:'center'});
                    return false;
                }

                // 2.5: Açıklama uzunluk kontrolü (opsiyonel alan)
                if (explEl && explEl.value.length > 1000) {
                    if (errEl){ errEl.textContent = 'Açıklama en fazla 1000 karakter olabilir.'; errEl.style.display = 'block'; }
                    explEl.focus();
                    item.scrollIntoView({behavior:'smooth', block:'center'});
                    return false;
                }

                // 2.6: Payload’ı üret
                const payloadEl = item.querySelector('.q-answers-payload');
                if (payloadEl) {
                    const parts = [];
                    optionRows.forEach((row, idx) => {
                        const label = optionInputs[idx]?.value.trim();
                        if (!label) return;
                        const isTrue = idx === checkedIndex;
                        parts.push(`${label}=${isTrue ? 'TRUE' : 'FALSE'}`);
                    });
                    payloadEl.value = parts.join('&');
                }

                // temizle
                if (errEl){ errEl.textContent=''; errEl.style.display='none'; }
            }

            return true;
        }

        curriculumForm.addEventListener('submit', (e) => {
            // validate
            if (!validateBeforeSubmit()) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            // valid → overlay aç, uyarıyı kapat
            WARN_ON_UNLOAD = false;
            isFormDirty = false;

            overlayEl.classList.add('show');
            submitBtn.disabled = true;
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = `
          <span class="inline-flex items-center gap-2">
            <span class="spinner" style="width:16px; height:16px; border-width:2px;"></span>
            Kaydediliyor…
          </span>
        `;
        });

    }); // DOMContentLoaded
</script>
</body>
</html>
