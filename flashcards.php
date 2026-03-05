<?php
require_once 'config.php';
?>

<!doctype html>
<html lang="<?= CURRENT_LANG ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/index.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">
    <title><?= __('fc.page_title') ?></title>
    <style>
        :root{ --rv-yellow:#E5AE32; --bg:#f5f5f7; --card:#ffffff; --ink:#111827; --muted:#6b7280; --line:#e5e7eb; }
        *{box-sizing:border-box}
        body{margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Helvetica,Arial,sans-serif; color:var(--ink); background:var(--bg)}
        .container{max-width:1100px; margin:0 auto; padding:16px}
        .btn{appearance:none; border:1px solid var(--line); background:#fff; padding:8px 12px; border-radius:10px; cursor:pointer}
        .btn:hover{background:#fafafa}
        .btn.primary{background:var(--rv-yellow); border-color:var(--rv-yellow); color:#111}
        .card{background:var(--card); border:1px solid var(--line); border-radius:18px; box-shadow:0 6px 20px rgba(0,0,0,.04)}
        .pad{padding:14px}
        /* progress */
        .progress{height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden}
        .progress > div{height:100%; background:var(--rv-yellow); width:0}
        .meta{display:flex; justify-content:space-between; color:var(--muted); font-size:13px; margin-bottom:6px}
        /* flip card */
        .stage{aspect-ratio:16/10; perspective:1200px}
        .flip{position:relative; width:100%; height:100%; transform-style:preserve-3d; transition:transform .5s}
        .face{position:absolute; inset:0; padding:20px; display:flex; flex-direction:column; justify-content:space-between; backface-visibility:hidden}
        .front{background:var(--card); border:1px solid var(--line); border-radius:18px}
        .back{background:var(--card); border:1px solid var(--line); border-radius:18px; transform:rotateY(180deg)}
        .flip.showback{transform:rotateY(180deg)}
        .toprow{display:flex; justify-content:space-between; align-items:center}
        .badge{border:1px solid var(--rv-yellow); padding:4px 8px; border-radius:999px; font-size:12px}
        .muted{color:var(--muted); font-size:12px}
        .term{font-size:28px; font-weight:800; text-align:center; line-height:1.2; padding:10px}
        .def{font-size:18px; line-height:1.6; overflow:auto; padding-right:8px}
        .actions{display:grid; grid-template-columns:repeat(3,1fr); gap:10px}
        .footer-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:10px}
        @media(max-width:640px){.footer-grid{grid-template-columns:repeat(2,1fr)}}
        .stat{background:#f8fafc; border:1px solid var(--line); border-radius:14px; padding:12px}
        .stat .k{font-size:12px; color:var(--muted)}
        .stat .v{font-size:20px; font-weight:700}
        .hint{display:flex; justify-content:space-between; align-items:center; color:var(--muted); font-size:13px; margin-top:6px}
    </style>
</head>
<body>
<?php require_once 'navbar.php'?>

<!-- PROGRESS -->
<div class="container" style="margin-top:12px">
    <div class="card pad">
        <div class="meta"><span id="metaLearned">0/0 <?= __('fc.learned') ?></span><span id="metaLeft">0 <?= __('fc.remaining') ?></span></div>
        <div class="progress"><div id="bar"></div></div>
    </div>
</div>

<!-- CARD AREA -->
<main class="container" style="padding-top:10px">
    <div id="stageWrap" class="card pad">
        <div class="stage">
            <div id="flip" class="flip">
                <section class="face front">
                    <div class="toprow">
                        <span id="catFront" class="badge"></span>
                        <span id="idxFront" class="muted"></span>
                    </div>
                    <div class="term" id="term"></div>
                    <div style="display:flex; justify-content:center; margin-bottom:6px">
                        <button id="flipBtn" class="btn primary"><?= __('fc.flip') ?></button>
                    </div>
                </section>
                <section class="face back">
                    <div class="toprow">
                        <span id="catBack" class="badge"></span>
                        <span id="idxBack" class="muted"></span>
                    </div>
                    <div class="def" id="def"></div>
                    <div class="actions">
                        <button id="prevBtn" class="btn"><?= __('fc.back') ?></button>
                        <button id="againBtn" class="btn"><?= __('fc.again') ?></button>
                        <button id="goodBtn" class="btn primary"><?= __('fc.know') ?></button>
                    </div>
                </section>
            </div>
        </div>
        <div class="hint">
            <span><?= __('fc.hint') ?></span>
            <div>
                <button id="navPrev" class="btn"><?= __('fc.prev') ?></button>
                <button id="navNext" class="btn"><?= __('fc.next') ?></button>
            </div>
        </div>
    </div>
</main>

<!-- SESSION SUMMARY + IMPORT/EXPORT -->
<div class="container" style="padding-bottom:24px">
    <div class="card pad">
        <h3 style="margin:8px 0 10px"><?= __('fc.session') ?></h3>
        <div class="footer-grid">
            <div class="stat"><div class="k"><?= __("fc.total") ?></div><div id="stTotal" class="v">0</div></div>
            <div class="stat"><div class="k"><?= __("fc.learned_stat") ?></div><div id="stLearned" class="v">0</div></div>
            <div class="stat"><div class="k"><?= __("fc.left") ?></div><div id="stLeft" class="v">0</div></div>
            <div class="stat"><div class="k"><?= __("fc.queue") ?></div><div id="stQueue" class="v">0</div></div>
        </div>
        <div style="margin-top:12px; display:flex; flex-wrap:wrap; gap:8px">

            <button id="shuffleBtn" class="btn"><?= __('fc.shuffle') ?></button>
            <button id="resetBtn" class="btn"><?= __('fc.reset') ?></button>
        </div>
    </div>
</div>

<?php require_once 'footer.php'?>

<script>
    lucide.createIcons();
    const LANG = '<?= CURRENT_LANG ?>';
    const RAW_TR = [
        { term: "FIRST", category: "Genel Terimler", definition: "'For Inspiration and Recognition of Science and Technology' kelimelerinin baş harflerinden oluşan, gençleri bilim ve teknolojiye teşvik etmek amacıyla kurulmuş olan kâr amacı gütmeyen organizasyon." },
        { term: "FRC", category: "Genel Terimler", definition: "FIRST Robotics Competition. FIRST organizasyonunun lise öğrencilerine yönelik en büyük ve kapsamlı robotik yarışması." },
        { term: "Gracious Professionalism", category: "Genel Terimler", definition: "Hem rekabetin hem de karşılıklı saygının bir arada yürüdüğü bir FRC felsefesi." },
        { term: "Coopertition", category: "Genel Terimler", definition: "İş birliği ve rekabetin birlikte yürütülmesini anlatan FIRST yaklaşımı." },
        { term: "Rookie", category: "Genel Terimler", definition: "FRC'ye ilk defa katılan takım veya üye." },
        { term: "Veteran", category: "Genel Terimler", definition: "FRC'de en az bir sezon tecrübesi olan takım veya üye." },
        { term: "Alliance", category: "Oyun ve Saha", definition: "Maç sırasında birlikte hareket eden takım grubu." },
        { term: "Autonomous Period", category: "Oyun ve Saha", definition: "Maçın ilk 15 saniyesi; robotlar sürücü kontrolü olmadan çalışır." },
        { term: "Tele-Op Period", category: "Oyun ve Saha", definition: "Sürücülerin robotları aktif olarak kontrol ettiği maç bölümü." },
        { term: "Endgame", category: "Oyun ve Saha", definition: "Maçın son bölümündeki ekstra puan fırsatları." },
        { term: "Game Piece", category: "Oyun ve Saha", definition: "Robotların topladığı, taşıdığı veya yerleştirdiği oyun nesneleri." },
        { term: "Driver Station", category: "Oyun ve Saha", definition: "Sürücülerin robotu yönettiği kontrol alanı." },
        { term: "Ranking Points", category: "Oyun ve Saha", definition: "Sıralama için kullanılan maç puanı sistemi." },
        { term: "Drivetrain", category: "Robot Mekaniği", definition: "Robotun hareketini sağlayan mekanik ve güç aktarım sistemi." },
        { term: "Bumper", category: "Robot Mekaniği", definition: "Robotu çarpmalara karşı koruyan güvenlik bileşeni." },
        { term: "roboRIO", category: "Robot Mekaniği", definition: "Robotun ana kontrol birimi." },
        { term: "Motor Controller", category: "Robot Mekaniği", definition: "Motorlara giden gücü ve komutları yöneten elektronik sürücü." },
        { term: "Pneumatics", category: "Robot Mekaniği", definition: "Basınçlı hava ile çalışan mekanizma sistemi." },
        { term: "Sensor", category: "Robot Mekaniği", definition: "Robotun çevreyi algılamasını sağlayan bileşenler." },
        { term: "Drive Team", category: "Takım Rolleri", definition: "Sürücü, operatör, koç ve insan oyuncudan oluşan maç ekibi." },
        { term: "Pit", category: "Takım Rolleri", definition: "Takımın robot bakımını yaptığı çalışma alanı." },
        { term: "Scouting", category: "Takım Rolleri", definition: "Rakiplerin performans verilerini toplama ve analiz süreci." },
        { term: "Mentor", category: "Takım Rolleri", definition: "Takıma teknik ve sosyal rehberlik sağlayan gönüllü uzman." },
        { term: "Kickoff", category: "Etkinlikler ve Ödüller", definition: "Yeni sezon oyununun ve kurallarının açıklandığı resmi başlangıç etkinliği." },
        { term: "Regional / District", category: "Etkinlikler ve Ödüller", definition: "Takımların sezon içinde yarıştığı resmi turnuva formatları." },
        { term: "Championship", category: "Etkinlikler ve Ödüller", definition: "Sezon sonunda en iyi takımların buluştuğu dünya düzeyi final etkinliği." },
        { term: "FIRST Impact Award", category: "Etkinlikler ve Ödüller", definition: "Toplumsal etki ve FIRST değerlerini en güçlü yansıtan takıma verilen ödül." },
        { term: "Engineering Inspiration Award", category: "Etkinlikler ve Ödüller", definition: "Mühendislik farkındalığı ve ilham etkisi yüksek takımları ödüllendirir." }
    ];

    const RAW_EN = [
        { term: "FIRST", category: "General Terms", definition: "A nonprofit organization that inspires young people in science and technology through robotics programs." },
        { term: "FRC", category: "General Terms", definition: "FIRST Robotics Competition, the largest high-school robotics program in FIRST." },
        { term: "Gracious Professionalism", category: "General Terms", definition: "A FIRST value that combines strong competition with respect and kindness." },
        { term: "Coopertition", category: "General Terms", definition: "A FIRST concept that blends cooperation and competition." },
        { term: "Rookie", category: "General Terms", definition: "A team or member participating in FRC for the first time." },
        { term: "Veteran", category: "General Terms", definition: "A team or member with at least one completed FRC season." },
        { term: "Alliance", category: "Game and Field", definition: "A group of teams that play together during a match." },
        { term: "Autonomous Period", category: "Game and Field", definition: "The first 15 seconds of a match where robots run pre-programmed actions." },
        { term: "Tele-Op Period", category: "Game and Field", definition: "The driver-controlled phase that follows autonomous play." },
        { term: "Endgame", category: "Game and Field", definition: "The final portion of a match with bonus scoring opportunities." },
        { term: "Game Piece", category: "Game and Field", definition: "Objects robots collect, move, or score during the game." },
        { term: "Driver Station", category: "Game and Field", definition: "The control area where the drive team operates the robot." },
        { term: "Ranking Points", category: "Game and Field", definition: "Points used to rank teams during qualification matches." },
        { term: "Drivetrain", category: "Robot Mechanics", definition: "The mechanical system that powers and controls robot movement." },
        { term: "Bumper", category: "Robot Mechanics", definition: "A protective structure around the robot used for safety and identification." },
        { term: "roboRIO", category: "Robot Mechanics", definition: "The main onboard controller of an FRC robot." },
        { term: "Motor Controller", category: "Robot Mechanics", definition: "An electronic device that controls motor power and direction." },
        { term: "Pneumatics", category: "Robot Mechanics", definition: "A system that uses compressed air to power mechanisms." },
        { term: "Sensor", category: "Robot Mechanics", definition: "A device that helps the robot detect conditions or environment changes." },
        { term: "Drive Team", category: "Team Roles", definition: "The students and coach who operate and guide the robot during matches." },
        { term: "Pit", category: "Team Roles", definition: "The designated workspace where teams service and improve their robot." },
        { term: "Scouting", category: "Team Roles", definition: "Collecting and analyzing performance data from teams and matches." },
        { term: "Mentor", category: "Team Roles", definition: "An experienced volunteer who guides students in technical and soft skills." },
        { term: "Kickoff", category: "Events and Awards", definition: "The official event where the new season game and rules are announced." },
        { term: "Regional / District", category: "Events and Awards", definition: "Official competition formats teams attend during the season." },
        { term: "Championship", category: "Events and Awards", definition: "The season-ending world-level event featuring top teams." },
        { term: "FIRST Impact Award", category: "Events and Awards", definition: "A major award recognizing teams with outstanding community impact." },
        { term: "Engineering Inspiration Award", category: "Events and Awards", definition: "Recognizes teams that strongly promote engineering and STEM inspiration." }
    ];

    const RAW = LANG === 'en' ? RAW_EN : RAW_TR;
    const CARDS = RAW.map((c)=> ({...c, id:`${c.category}-${c.term}`}));

    // ======= DURUM & STORAGE =======
    const store = {
        read(key, fallback){ try{ return JSON.parse(localStorage.getItem(key)) ?? fallback }catch{ return fallback }},
        write(key, val){ try{ localStorage.setItem(key, JSON.stringify(val)) }catch{} }
    };
    let seed = store.read('frcanki:seed', Date.now());
    let learnedIds = store.read('frcanki:learned', []);
    let againIds = store.read('frcanki:againQueue', []);
    let idx = store.read('frcanki:index', 0);
    let showBack = store.read('frcanki:showBack', false);

    // RNG & helpers
    function mulberry32(a){return function(){let t=(a+=0x6D2B79F5);t=Math.imul(t^(t>>>15),t|1);t^=t+Math.imul(t^(t>>>7),61|t);return ((t^(t>>>14))>>>0)/4294967296;}}
    function shuffleStable(arr, seed){ const r = mulberry32(seed); return [...arr].sort(()=> r()-0.5) }
    function clamp(n,a,b){return Math.max(a,Math.min(b,n))}
    function setSeed(s){ seed=s; store.write('frcanki:seed', seed) }

    // ======= KUYRUK =======
    function filtered(){ return shuffleStable(CARDS, seed); } // filtre yok
    function queue(){
        const base = filtered();
        const learned = new Set(learnedIds);
        const againSet = new Set(againIds);
        const fresh = base.filter(c=> !learned.has(c.id) && !againSet.has(c.id));
        const againCards = againIds.map(id=> base.find(c=> c.id===id)).filter(Boolean);
        return [...againCards, ...fresh];
    }

    // ======= DOM =======
    const el = (id)=> document.getElementById(id);
    const flipBox = el('flip');
    const termEl = el('term');
    const defEl = el('def');
    const catFront = el('catFront');
    const catBack = el('catBack');
    const idxFront = el('idxFront');
    const idxBack = el('idxBack');
    const flipBtn = el('flipBtn');
    const prevBtn = el('prevBtn');
    const againBtn = el('againBtn');
    const goodBtn = el('goodBtn');
    const navPrev = el('navPrev');
    const navNext = el('navNext');
    const shuffleBtn = el('shuffleBtn');
    const resetBtn = el('resetBtn');
    const metaLearned = el('metaLearned');
    const metaLeft = el('metaLeft');
    const bar = el('bar');
    const stTotal = el('stTotal');
    const stLearned = el('stLearned');
    const stLeft = el('stLeft');
    const stQueue = el('stQueue');

    // ======= LOGIC =======
    function current(){
        const q = queue();
        if(q.length===0) return null;
        return q[clamp(idx,0,q.length-1)];
    }

    function updateProgress(total, learned){
        metaLearned.textContent = `${learned}/${total} <?= __('fc.learned') ?>`;
        metaLeft.textContent = `${Math.max(total-learned,0)} <?= __('fc.remaining') ?>`;
        bar.style.width = `${total? (learned/total)*100 : 0}%`;
        stTotal.textContent = total;
        stLearned.textContent = learned;
        stLeft.textContent = Math.max(total-learned,0);
        stQueue.textContent = queue().length;
    }

    function update(){
        const base = filtered();
        const learnedCount = base.filter(c=> learnedIds.includes(c.id)).length;
        updateProgress(base.length, learnedCount);

        const cur = current();
        if(!cur){
            termEl.textContent = '<?= __('fc.done') ?>';
            defEl.textContent = '<?= __('fc.done_desc') ?>';
            catFront.textContent = '';
            catBack.textContent = '';
            idxFront.textContent = '';
            idxBack.textContent = '';
            flipBox.classList.remove('showback');
            [flipBtn, prevBtn, againBtn, goodBtn, navPrev, navNext].forEach(b=> b.disabled = true);
            return;
        }

        [flipBtn, prevBtn, againBtn, goodBtn, navPrev, navNext].forEach(b=> b.disabled = false);

        termEl.textContent = cur.term;
        defEl.textContent = cur.definition;
        catFront.textContent = cur.category;
        catBack.textContent = cur.category;

        const q = queue();
        const pos = clamp(idx,0,q.length-1);
        idxFront.textContent = `<?= __('fc.card') ?> ${pos+1} / ${q.length}`;
        idxBack.textContent = `<?= __('fc.card') ?> ${pos+1} / ${q.length}`;

        if(showBack) flipBox.classList.add('showback'); else flipBox.classList.remove('showback');
    }

    function flip(){ showBack = !showBack; store.write('frcanki:showBack', showBack); update(); }
    function next(){ showBack=false; store.write('frcanki:showBack', false); idx = clamp(idx+1,0,Math.max(queue().length-1,0)); store.write('frcanki:index', idx); update(); }
    function prev(){ showBack=false; store.write('frcanki:showBack', false); idx = clamp(idx-1,0,Math.max(queue().length-1,0)); store.write('frcanki:index', idx); update(); }

    function gradeGood(){
        const cur = current(); if(!cur) return;
        if(!learnedIds.includes(cur.id)) learnedIds=[...learnedIds, cur.id];
        againIds = againIds.filter(id=> id!==cur.id);
        store.write('frcanki:learned', learnedIds);
        store.write('frcanki:againQueue', againIds);
        next();
    }
    function gradeAgain(){
        const cur = current(); if(!cur) return;
        if(!againIds.includes(cur.id)) againIds=[...againIds, cur.id];
        store.write('frcanki:againQueue', againIds);
        next();
    }

    // keyboard
    window.addEventListener('keydown', (e)=>{
        const tag = (e.target && (e.target.tagName||'')).toLowerCase();
        if(tag==='input' || tag==='textarea') return;
        if(e.code==='Space'){ e.preventDefault(); flip(); }
        if(e.key==='ArrowRight'){ next(); }
        if(e.key==='ArrowLeft'){ prev(); }
        if(e.key.toLowerCase()==='g'){ gradeGood(); }
        if(e.key.toLowerCase()==='a'){ gradeAgain(); }
    });

    // buttons
    flipBtn.addEventListener('click', flip);
    prevBtn.addEventListener('click', prev);
    navPrev.addEventListener('click', prev);
    navNext.addEventListener('click', next);
    againBtn.addEventListener('click', gradeAgain);
    goodBtn.addEventListener('click', gradeGood);
    shuffleBtn.addEventListener('click', ()=>{ setSeed(Date.now()); idx=0; store.write('frcanki:index', idx); update(); });
    resetBtn.addEventListener('click', ()=>{
        learnedIds=[]; againIds=[]; idx=0; showBack=false; setSeed(Date.now());
        store.write('frcanki:learned', learnedIds);
        store.write('frcanki:againQueue', againIds);
        store.write('frcanki:index', idx);
        store.write('frcanki:showBack', showBack);
        update();
    });

    // import/export


    // init
    update();
</script>
</body>
</html>
