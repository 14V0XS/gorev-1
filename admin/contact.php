<?php
$page_title = "İletişim Mesajları";
$projectRoot = dirname(__DIR__);
require_once($projectRoot . '/config.php');
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);

$pdo = get_db_connection();

/* İstatistikler */
$total_messages  = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE archived = 0")->fetchColumn();
$unread_messages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0 AND archived = 0")->fetchColumn();

/* Liste (ilk 100) */
$stmt = $pdo->prepare("
  SELECT id, name, email, subject, message, is_read, received_at
  FROM contact_messages
  WHERE archived = 0
  ORDER BY received_at DESC
  LIMIT 100
");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once 'admin_header.php';
require_once($projectRoot . '/admin/admin_sidebar.php');

?>
<style>
    .badge {display:inline-block;font-size:12px;font-weight:700;padding:4px 8px;border-radius:9999px}
    .badge.unread{background:#fee2e2;color:#991b1b}
    .badge.read{background:#dcfce7;color:#166534}
    .chip{display:inline-block;font-size:11px;color:#6b7280;background:#f3f4f6;border:1px solid #e5e7eb;padding:2px 6px;border-radius:9999px}
    .btn-ghost{border:1px solid var(--border,#e5e7eb);padding:6px 10px;border-radius:8px;background:#fff}
    .btn-ghost:hover{background:#f9fafb}
    .search-input{padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;width:100%}
    .detail-panel{position:fixed;inset:0 0 0 auto;width:min(520px,92vw);background:#fff;border-left:1px solid #e5e7eb;box-shadow:-24px 0 48px rgba(0,0,0,.08);transform:translateX(100%);transition:transform .25s ease;z-index:50;display:flex;flex-direction:column}
    .detail-panel.open{transform:translateX(0)}
    .detail-header{padding:16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
    .detail-body{padding:16px;overflow:auto}
    .muted{color:#6b7280}
</style>

<main style="width: 100%" class="main-content">
    <div class="content-area">
        <div class="page-header"><h1>İletişim Mesajları</h1></div>

        <div class="stats-grid mb-8">
            <div class="stat-card card"><div class="value"><?= $total_messages ?></div><div class="label">Toplam Mesaj</div></div>
            <div class="stat-card card"><div class="value" style="color:#dc2626;"><?= $unread_messages ?></div><div class="label">Okunmamış</div></div>
        </div>

        <div class="card mb-6">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
                <div style="flex:1;min-width:280px"><input id="search" class="search-input" type="text" placeholder="Ad, e-posta, konu, içerikte ara…"></div>
                <select id="filter-read" class="btn-ghost">
                    <option value="">Durum: Tümü</option>
                    <option value="0">Okunmamış</option>
                    <option value="1">Okundu</option>
                </select>
                <select id="sort-by" class="btn-ghost">
                    <option value="date_desc">Tarih (Yeni → Eski)</option>
                    <option value="date_asc">Tarih (Eski → Yeni)</option>
                </select>
                <button id="btn-refresh" class="btn-ghost"><i data-lucide="rotate-cw"></i> Yenile</button>
            </div>

            <div style="display:flex;gap:.5rem;align-items:center;margin-top:12px;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:8px">
                    <input id="select-all" type="checkbox"> <span class="muted">Tümünü Seç</span>
                </label>
                <div style="height:20px;width:1px;background:#e5e7eb"></div>
                <button id="bulk-read" class="btn-ghost"><i data-lucide="mail-open"></i> Okundu</button>
                <button id="bulk-unread" class="btn-ghost"><i data-lucide="mail"></i> Okunmamış</button>
                <button id="bulk-delete" class="btn-ghost" style="color:#b91c1c"><i data-lucide="trash-2"></i> Sil</button>
            </div>
        </div>

        <div style="padding: 1rem" class="card">
            <h2>Mesaj Listesi</h2>
            <div  class="table-responsive" style="margin-top:12px;">
                <table style="width: 100%" class="table">
                    <thead>
                    <tr>
                        <th ><input type="checkbox" id="head-check"></th>
                        <th>Gönderen</th>
                        <th>E-posta</th>
                        <th>Konu</th>
                        <th>Özet</th>
                        <th>Durum</th>
                        <th style="width:160px">Tarih</th>
                        <th >İşlemler</th>
                    </tr>
                    </thead>
                    <tbody  id="message-table">
                    <?php foreach ($messages as $m):
                        $badge = $m['is_read'] ? 'badge read' : 'badge unread';
                        $badgeText = $m['is_read'] ? 'okundu' : 'okunmamış';
                        $short = mb_strimwidth(strip_tags($m['message'] ?? ''), 0, 80, '…', 'UTF-8');
                        ?>
                        <tr data-id="<?= (int)$m['id'] ?>" data-is_read="<?= (int)$m['is_read'] ?>" data-received="<?= htmlspecialchars($m['received_at']) ?>">
                            <td><input type="checkbox" class="row-check" value="<?= (int)$m['id'] ?>"></td>
                            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                            <td class="muted"><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= htmlspecialchars($m['subject']) ?></td>
                            <td class="muted"><?= htmlspecialchars($short) ?></td>
                            <td><span class="<?= $badge ?>"><?= $badgeText ?></span></td>
                            <td class="muted"><?= htmlspecialchars($m['received_at']) ?></td>
                            <td>
                                <div class="table-actions" style="display:flex;align-items:center">
                                    <button style="display: flex; flex-direction: column; align-items: center" class="btn-ghost open-detail"><i data-lucide="panel-right-open"></i> Detay</button>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="8" class="muted">Henüz mesaj yok.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Sağdan açılan detay -->
<aside id="detail-panel" class="detail-panel" aria-hidden="true">
    <div class="detail-header">
        <div>
            <div id="detail-subject" style="font-weight:700">Konu</div>
            <div id="detail-meta" class="muted" style="font-size:13px">Gönderen • e-posta • tarih</div>
        </div>
        <button id="panel-close" class="btn-ghost" title="Kapat"><i data-lucide="x"></i></button>
    </div>
    <div class="detail-body">
        <div id="detail-status" class="badge unread">okunmamış</div>
        <div id="detail-message" style="margin:12px 0; line-height:1.6"></div>
        <div class="card" style="margin-top:12px">
            <h3>İletişim</h3>
            <div id="detail-contact" class="muted" style="margin-top:8px;font-size:14px"></div>
        </div>
        <div style="display:flex;gap:.5rem;margin-top:12px;flex-wrap:wrap">
            <button class="btn-ghost" data-action="read"><i data-lucide="mail-open"></i> Okundu</button>
            <button class="btn-ghost" data-action="unread"><i data-lucide="mail"></i> Okunmamış</button>
            <button class="btn-ghost" data-action="delete" style="color:#b91c1c"><i data-lucide="trash-2"></i> Sil</button>
        </div>
    </div>
</aside>

<?php require_once 'admin_footer.php'; ?>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    try{ lucide.createIcons(); }catch(e){}

    document.getElementById('btn-refresh').addEventListener('click', () =>{
        window.location.reload();
    })

    const $ = (s,root=document)=>root.querySelector(s);
    const $$= (s,root=document)=>Array.from(root.querySelectorAll(s));
    const panel = $('#detail-panel');
    $('#panel-close')?.addEventListener('click', ()=> panel.classList.remove('open'));

    // Arama (frontend)
    $('#search')?.addEventListener('input', e=>{
        const q = e.target.value.toLowerCase();
        $$('#message-table tr').forEach(tr=>{
            tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Durum filtresi (frontend)
    $('#filter-read')?.addEventListener('change', e=>{
        const val = e.target.value; // '' | '0' | '1'
        $$('#message-table tr').forEach(tr=>{
            const r = tr.dataset.is_read;
            tr.style.display = (!val || val===r) ? '' : 'none';
        });
    });

    // Sıralama (frontend)
    $('#sort-by')?.addEventListener('change', e=>{
        const val = e.target.value;
        const rows = $$('#message-table tr');
        const arr = rows.map(r=>({el:r, ts: Date.parse(r.dataset.received)}));
        arr.sort((a,b)=> val==='date_asc' ? a.ts-b.ts : b.ts-a.ts);
        const tbody = $('#message-table');
        arr.forEach(o=>tbody.appendChild(o.el));
    });

    // Toplu seçim
    $('#head-check')?.addEventListener('change', e=>{
        $$('.row-check').forEach(cb=> cb.checked = e.target.checked);
        $('#select-all').checked = e.target.checked;
    });
    $('#select-all')?.addEventListener('change', e=>{
        $$('.row-check').forEach(cb=> cb.checked = e.target.checked);
        $('#head-check').checked = e.target.checked;
    });

    // Detay aç
    document.addEventListener('click', e=>{
        const tr = e.target.closest('tr[data-id]');
        const openBtn = e.target.closest('.open-detail');
        if (openBtn && tr) {
            const id      = tr.dataset.id;
            const name    = tr.children[1].innerText.trim();
            const email   = tr.children[2].innerText.trim();
            const subject = tr.children[3].innerText.trim();
            const summary = tr.children[4].innerText.trim();
            const isRead  = tr.dataset.is_read === '1';
            const date    = tr.children[6].innerText.trim();

            $('#detail-subject').textContent = subject;
            $('#detail-meta').textContent    = `${name} • ${email} • ${date}`;
            $('#detail-message').textContent = summary; // istersen backend'ten full message getirebilirsin
            $('#detail-contact').innerHTML   = `<div><b>Ad:</b> ${name}</div>
        <div><b>E-posta:</b> <a href="mailto:${email}">${email}</a></div>
        <div><b>Tarih:</b> ${date}</div>
        <div><b>Mesaj ID:</b> ${id}</div>`;

            const badge = $('#detail-status');
            badge.textContent = isRead ? 'okundu' : 'okunmamış';
            badge.className = 'badge ' + (isRead ? 'read' : 'unread');

            panel.dataset.id = id;
            panel.classList.add('open');
        }
    });

    // Detay paneli aksiyonları
    panel.addEventListener('click', async e=>{
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const id = panel.dataset.id;
        const action = btn.dataset.action; // read | unread | delete
        await bulkAction(action, [id]);
    });

    // Toplu aksiyonlar
    $('#bulk-read')?.addEventListener('click', async()=> {
        const ids = $$('.row-check:checked').map(x=>x.value);
        if (!ids.length) return;
        await bulkAction('read', ids);
    });
    $('#bulk-unread')?.addEventListener('click', async()=> {
        const ids = $$('.row-check:checked').map(x=>x.value);
        if (!ids.length) return;
        await bulkAction('unread', ids);
    });
    $('#bulk-delete')?.addEventListener('click', async()=> {
        const ids = $$('.row-check:checked').map(x=>x.value);
        if (!ids.length) return;
        if (!confirm('Seçili mesajlar silinsin mi?')) return;
        await bulkAction('delete', ids);
    });

    async function bulkAction(action, ids){
        try{
            const res = await fetch('messages_action.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ action, ids })
            });
            const data = await res.json();
            if (!data.success) { alert(data.message || 'İşlem başarısız'); return; }

            // UI güncelle
            if (action==='read' || action==='unread') {
                ids.forEach(id=>{
                    const tr = document.querySelector(`tr[data-id="${id}"]`);
                    if (!tr) return;
                    tr.dataset.is_read = (action==='read' ? '1' : '0');
                    const badge = tr.querySelector('.badge');
                    if (!badge) return;
                    if (action==='read'){ badge.textContent='okundu'; badge.className='badge read'; }
                    else { badge.textContent='okunmamış'; badge.className='badge unread'; }
                });
            } else if (action==='delete') {
                ids.forEach(id=>{
                    const tr = document.querySelector(`tr[data-id="${id}"]`);
                    tr?.remove();
                });
                panel.classList.remove('open');
            }
            try{ lucide.createIcons(); }catch(e){}
        }catch(err){
            console.error(err);
            alert('Sunucu hatası');
        }
    }
</script>
