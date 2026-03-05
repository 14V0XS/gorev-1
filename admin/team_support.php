<?php
/** admin_support.php — Support kutusu (fix: tablo & detay) */
declare(strict_types=1);
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$pdo = get_db_connection();

/* İstatistikler */
$stats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM team_support WHERE type='support' AND archived=0 ")->fetchColumn(),
    'open'     => (int)$pdo->query("SELECT COUNT(*) FROM team_support WHERE type='support' AND archived=0 AND is_resolved=0 AND content_id IS NULL")->fetchColumn(),
    'resolved' => (int)$pdo->query("SELECT COUNT(*) FROM team_support WHERE type='support' AND archived=0 AND is_resolved=1 AND content_id IS NULL")->fetchColumn(),
];

/* Liste */
$q = $pdo->prepare("
  SELECT s.id, s.team_id, s.message, s.is_resolved, s.created_at,
         COALESCE(CONCAT('#', t.team_number, ' ', t.team_name), CONCAT('team_id ', s.team_id)) AS team_display
    FROM team_support s
    LEFT JOIN teams t ON t.id = s.team_id
   WHERE s.type='support' AND s.archived=0 AND s.content_id IS NULL
   ORDER BY s.created_at DESC
   LIMIT 200
");
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

/* CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$CSRF = $_SESSION['csrf'];

require_once $projectRoot . '/admin/admin_header.php';
require_once $projectRoot . '/admin/admin_sidebar.php';
?>
<style>
    .badge{display:inline-block;font-size:12px;font-weight:700;padding:4px 8px;border-radius:9999px}
    .badge.open{background:#fee2e2;color:#991b1b}
    .badge.done{background:#dcfce7;color:#166534}
    .btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#111827;font-weight:600;cursor:pointer}
    .btn-ghost:hover{background:#f9fafb}
    .search-input{padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;width:100%}
    .table{border-collapse:separate;border-spacing:0;width:100%}
    .table th,.table td{padding:10px 12px;border-bottom:1px solid #e5e7eb;vertical-align:top}
    .table thead th{background:#fafafa;font-weight:700}
    .muted{color:#6b7280}
    .detail-panel{position:fixed;inset:0 0 0 auto;width:min(560px,92vw);background:#fff;border-left:1px solid #e5e7eb;box-shadow:-24px 0 48px rgba(0,0,0,.08);transform:translateX(100%);transition:transform .25s ease;z-index:50;display:flex;flex-direction:column}
    .detail-panel.open{transform:translateX(0)}
    .detail-header{padding:16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between}
    .detail-body{padding:16px;overflow:auto}
    .thread{display:flex;flex-direction:column;gap:12px}
    .bubble{border:1px solid #e5e7eb;border-radius:12px;padding:10px}
    .bubble.support{background:#f9fafb}
    .bubble.response{background:#ecfeff}
    .thread-meta{font-size:12px;color:#6b7280;margin-bottom:6px}
    .textarea{width:100%;min-height:100px;border:1px solid #e5e7eb;border-radius:10px;padding:10px}
    .bubble.followup{background:#fffbeb;border-left:3px solid #eab308}

</style>

<main class="main-content" style="width:100%">
    <div class="content-area">
        <div class="page-header"><h1>Support Mesajları</h1></div>

        <div class="stats-grid mb-8" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
            <div class="stat-card card"><div class="value"><?= $stats['total'] ?></div><div class="label">Toplam</div></div>
            <div class="stat-card card"><div class="value" style="color:#dc2626;"><?= $stats['open'] ?></div><div class="label">Açık</div></div>
            <div class="stat-card card"><div class="value" style="color:#16a34a;"><?= $stats['resolved'] ?></div><div class="label">Çözüldü</div></div>
        </div>

        <div class="card mb-6" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;padding:12px">
            <div style="flex:1;min-width:280px"><input id="sup-search" class="search-input" type="text" placeholder="Takım/mesaj ara…"></div>
            <select id="sup-filter" class="btn-ghost" style="appearance:auto">
                <option value="">Durum: Tümü</option>
                <option value="0">Açık</option>
                <option value="1">Çözüldü</option>
            </select>
            <select id="sup-sort" class="btn-ghost" style="appearance:auto">
                <option value="date_desc">Tarih (Yeni → Eski)</option>
                <option value="date_asc">Tarih (Eski → Yeni)</option>
            </select>
            <button id="sup-refresh" type="button" class="btn-ghost">Yenile</button>
        </div>

        <div class="card" style="padding:1rem">
            <h2>Mesaj Listesi</h2>

            <div class="table-responsive" style="margin-top:12px;overflow:auto">
                <table class="table" aria-describedby="support-table-caption">
                    <caption id="support-table-caption" class="muted" style="text-align:left;padding:8px 0">Support kayıtları</caption>
                    <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="sup-check-all"></th>
                        <th>Takım</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th style="width:180px">Tarih</th>
                        <th style="width:120px">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody id="support-tbody">
                    <?php if ($rows): foreach ($rows as $r):
                        $isDone = (int)$r['is_resolved'] === 1;
                        $badge  = $isDone ? 'badge done' : 'badge open';
                        $btxt   = $isDone ? 'çözüldü' : 'açık';
                        $short  = mb_strimwidth(strip_tags($r['message'] ?? ''), 0, 100, '…', 'UTF-8');
                        // data-created ISO olsun ki Date.parse çalışsın
                        $isoCreated = date('c', strtotime($r['created_at']));
                        ?>
                        <tr data-id="<?= (int)$r['id'] ?>" data-resolved="<?= (int)$r['is_resolved'] ?>" data-created="<?= htmlspecialchars($isoCreated) ?>">
                            <td><input class="sup-row-check" type="checkbox" value="<?= (int)$r['id'] ?>"></td>
                            <td><strong><?= htmlspecialchars($r['team_display']) ?></strong><div class="muted">team_id: <?= (int)$r['team_id'] ?></div></td>
                            <td class="muted"><?= htmlspecialchars($short) ?></td>
                            <td><span class="<?= $badge ?>"><?= $btxt ?></span></td>
                            <td class="muted"><?= htmlspecialchars($r['created_at']) ?></td>
                            <td>
                                <div class="table-actions" style="display:flex;gap:8px">
                                    <button type="button" class="btn-ghost open-detail">Detay</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="muted">Kayıt yok.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;gap:.5rem;align-items:center;margin-top:12px;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:8px">
                    <input id="sup-select-all" type="checkbox"> <span class="muted">Tümünü Seç</span>
                </label>
                <div style="height:20px;width:1px;background:#e5e7eb"></div>
                <button id="sup-bulk-res" type="button" class="btn-ghost">Çözüldü</button>
                <button id="sup-bulk-unres" type="button" class="btn-ghost">Açık Yap</button>
                <button id="sup-bulk-del" type="button" class="btn-ghost" style="color:#b91c1c">Sil</button>
            </div>
        </div>
    </div>
</main>

<!-- Detay paneli -->
<aside id="detail-panel" class="detail-panel" aria-hidden="true">
    <div class="detail-header">
        <div>
            <div id="detail-title" style="font-weight:700">Support Mesajı</div>
            <div id="detail-meta" class="muted" style="font-size:13px">Takım • tarih • durum</div>
        </div>
        <button id="panel-close" type="button" class="btn-ghost">Kapat</button>
    </div>
    <div class="detail-body">
        <div id="detail-status" class="badge open">açık</div>
        <div style="margin-top:12px">
            <h3>Destek</h3>
            <div id="thread" class="thread" style="margin-top:8px"></div>
        </div>
        <div class="card" style="margin-top:12px">
            <h3>Yanıt Yaz</h3>
            <textarea id="reply-text" class="textarea" maxlength="250" placeholder="Yanıtınızı yazın (max 250)"></textarea>
            <div style="display:flex;gap:.5rem;margin-top:10px;flex-wrap:wrap">
                <button id="send-reply"  type="button" class="btn-ghost">Gönder</button>
                <button id="btn-resolve"  type="button" class="btn-ghost">Çözüldü</button>
                <button id="btn-unresolve" type="button" class="btn-ghost">Açık Yap</button>
                <button id="btn-delete"   type="button" class="btn-ghost" style="color:#b91c1c">Sil</button>
            </div>
        </div>
    </div>
</aside>

<script>
    // util
    const $  = (s,r=document)=>r.querySelector(s);
    const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));
    const panel = $('#detail-panel');

    // basit kontroller
    $('#sup-refresh')?.addEventListener('click', ()=>location.reload());
    $('#panel-close')?.addEventListener('click', ()=>panel.classList.remove('open'));

    // arama/filtre/sırala
    $('#sup-search')?.addEventListener('input', e=>{
        const q=e.target.value.toLowerCase();
        $$('#support-tbody tr').forEach(tr=>{
            tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });
    $('#sup-filter')?.addEventListener('change', e=>{
        const v=e.target.value; // '', '0', '1'
        $$('#support-tbody tr').forEach(tr=>{
            tr.style.display = (!v || v===tr.dataset.resolved) ? '' : 'none';
        });
    });
    $('#sup-sort')?.addEventListener('change', e=>{
        const v=e.target.value;
        const arr=$$('#support-tbody tr').map(el=>({el, ts:Date.parse(el.dataset.created||'')||0}));
        arr.sort((a,b)=> v==='date_asc' ? a.ts-b.ts : b.ts-a.ts);
        const tb=$('#support-tbody'); arr.forEach(o=>tb.appendChild(o.el));
    });

    // toplu seçim
    const syncMaster = (checked)=>{ $$('.sup-row-check').forEach(cb=>cb.checked=checked); };
    $('#sup-check-all')?.addEventListener('change', e=>{ syncMaster(e.target.checked); $('#sup-select-all').checked=e.target.checked; });
    $('#sup-select-all')?.addEventListener('change', e=>{ syncMaster(e.target.checked); $('#sup-check-all').checked=e.target.checked; });

    // detay aç — güvenli delegation (ikon/btn/td neresine tıklansa çalışır)
    document.addEventListener('click', async (ev)=>{
        const openBtn = ev.target.closest('.open-detail');
        if (!openBtn) return;
        ev.preventDefault();
        const tr = openBtn.closest('tr[data-id]');
        if (!tr) return;
        await openThread(tr.dataset.id, tr);
    });

    async function openThread(id, trRow){
        try{
            const res = await fetch('support_action.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ action:'thread', id })
            });
            const data = await res.json();
            if(!data?.success){ alert(data?.message||'Thread alınamadı'); return; }

            $('#detail-title').textContent = `#${data.item.id} • ${data.item.team_display||'Takım'}`;
            $('#detail-meta').textContent  = `${data.item.team_display||'Takım'} • ${data.item.created_at} • ${(data.item.is_resolved?'çözüldü':'açık')}`;
            const badge = $('#detail-status');
            badge.textContent = data.item.is_resolved ? 'çözüldü' : 'açık';
            badge.className   = 'badge ' + (data.item.is_resolved ? 'done' : 'open');

            const T = $('#thread'); T.innerHTML='';
            T.appendChild(makeBubble('support', data.item.message, `${data.item.created_at} • support #${data.item.id}`));

            (data.replies||[]).forEach(r=>{
                // Önce admin cevabını ekle
                T.appendChild(
                    makeBubble('response', r.message, `${r.created_at} • response #${r.id}`, { id: r.id })
                );

                // Sonra bu cevaba gelen takım follow-uplarını ekle
                const fus = (data.followups_by && data.followups_by[r.id]) ? data.followups_by[r.id] : [];
                fus.forEach(f=>{
                    T.appendChild(
                        makeBubble('followup', f.message, `${f.created_at} • takım yanıtı (content_id=${r.id})`)
                    );
                });
            });



            panel.dataset.id = id;
            panel.classList.add('open');
        }catch(e){ console.error(e); alert('Sunucu hatası'); }
    }

    function makeBubble(kind, text, meta, opts = {}){
        const d=document.createElement('div');
        d.className='bubble ' + (kind==='response'?'response' : (kind==='followup'?'followup':'support'));

        const head=document.createElement('div');
        head.style.display='flex';
        head.style.alignItems='center';
        head.style.justifyContent='space-between';
        head.style.gap='8px';

        const m=document.createElement('div');
        m.className='thread-meta';
        m.textContent=meta;

        head.appendChild(m);

        // sadece admin yanıtlarında (support_response) sil butonu göster
        if(kind==='response' && opts.id){
            const del=document.createElement('button');
            del.type='button';
            del.className='btn-ghost reply-delete';
            del.dataset.replyId = String(opts.id);
            del.textContent='Sil';
            del.style.color='#b91c1c';
            head.appendChild(del);
        }

        const p=document.createElement('div');
        p.textContent=text||'';

        d.appendChild(head);
        d.appendChild(p);
        return d;
    }


    // panel aksiyonları
    $('#send-reply')?.addEventListener('click', async ()=>{
        const id = panel.dataset.id; const msg = ($('#reply-text')?.value||'').trim();
        if(!id || !msg) return;
        const res = await fetch('support_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reply',id,message:msg})});
        const data = await res.json(); if(!data?.success){ alert(data?.message||'Gönderilemedi'); return; }
        $('#reply-text').value=''; await openThread(id);
    });
    $('#btn-resolve')?.addEventListener('click', ()=>single('resolve'));
    $('#btn-unresolve')?.addEventListener('click', ()=>single('unresolve'));
    $('#btn-archive')?.addEventListener('click', ()=>single('archive'));
    $('#btn-delete') ?.addEventListener('click', ()=>{ if(confirm('Silinsin mi?')) single('delete'); });

    async function single(action){
        const id=panel.dataset.id; if(!id) return;
        const res = await fetch('support_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,ids:[id]})});
        const data = await res.json(); if(!data?.success){ alert(data?.message||'İşlem başarısız'); return; }
        // UI güncelle
        if (['archive','delete'].includes(action)){
            document.querySelector(`tr[data-id="${id}"]`)?.remove(); panel.classList.remove('open'); return;
        }
        const tr=document.querySelector(`tr[data-id="${id}"]`);
        if (tr){
            tr.dataset.resolved = (action==='resolve'?'1':'0');
            const b=tr.querySelector('.badge');
            if (b){ if (action==='resolve'){ b.textContent='çözüldü'; b.className='badge done'; } else { b.textContent='açık'; b.className='badge open'; } }
        }
        await openThread(id);
    }

    // toplu
    $('#sup-bulk-res')  ?.addEventListener('click', ()=>bulk('resolve'));
    $('#sup-bulk-unres')?.addEventListener('click', ()=>bulk('unresolve'));
    $('#sup-bulk-arch') ?.addEventListener('click', ()=>bulk('archive'));
    $('#sup-bulk-del')  ?.addEventListener('click', ()=>{ if(confirm('Seçili kayıtlar silinsin mi?')) bulk('delete'); });
    // reply silme (delegation)
    document.addEventListener('click', async (ev)=>{
        const btn = ev.target.closest('.reply-delete');
        if(!btn) return;
        const replyId = btn.dataset.replyId;
        if(!replyId) return;

        if(!confirm('Bu yanıtı silmek istediğinize emin misiniz?')) return;

        try{
            const res = await fetch('support_action.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ action:'delete_reply', reply_id: replyId })
            });
            const data = await res.json();
            if(!data?.success){ alert(data?.message||'Silinemedi'); return; }

            // UI: balonu kaldır
            const bubble = btn.closest('.bubble');
            bubble?.remove();
        }catch(e){
            console.error(e);
            alert('Sunucu hatası');
        }
    });


    async function bulk(action){
        const ids = $$('.sup-row-check:checked').map(x=>x.value);
        if(!ids.length) return;
        const res = await fetch('support_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,ids})});
        const data = await res.json(); if(!data?.success){ alert(data?.message||'İşlem başarısız'); return; }
        if (['archive','delete'].includes(action)){
            ids.forEach(id=>document.querySelector(`tr[data-id="${id}"]`)?.remove());
            panel.classList.remove('open');
        } else {
            ids.forEach(id=>{
                const tr=document.querySelector(`tr[data-id="${id}"]`); if(!tr) return;
                tr.dataset.resolved=(action==='resolve'?'1':'0');
                const b=tr.querySelector('.badge');
                if(b){ if(action==='resolve'){ b.textContent='çözüldü'; b.className='badge done'; } else { b.textContent='açık'; b.className='badge open'; } }
            });
        }
    }
</script>
<?php require_once $projectRoot . '/admin/admin_footer.php'; ?>
