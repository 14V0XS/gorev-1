<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 - Sayfa Bulunamadı</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/index.css">
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-EDSVL8LRCY');
    </script>
  <script>
    tailwind.config = { theme:{ extend:{ colors:{ 'custom-yellow':'#E5AE32' }}}}
  </script>
  <style>

  /* Syntax highlighting colors */
    .token-variable    { color: #c084fc; }   /* mor (değişkenler) */
    .token-keyword     { color: #f59e0b; }   /* turuncu (php anahtar kelimeleri, SQL komutları) */
    .token-string      { color: #22c55e; }   /* yeşil (stringler) */
    .token-comment     { color: #9ca3af; font-style: italic; } /* gri italik */
    .token-function    { color: #38bdf8; }   /* açık mavi (fonksiyonlar) */
    .token-number      { color: #f87171; }   /* kırmızımsı (sayılar) */

    :root{
      --bg:#ffffff;            /* beyaz arka plan */
      --panel:#0f172a;         /* koyu panel */
      --line:#e5e7eb;          /* açık satır çizgisi */
      --green:#22c55e;
      --red:#ef4444;
      --accent:#E5AE32;        /* site ana rengi */
    }
    body { background:#fff; color:#0f172a; }

    /* Editör (kod yazımı) */
    .editor {
      background: linear-gradient(180deg, #0b1020 0%, #0c1428 100%);
      color: #d1d5db;
      border-radius: 16px;
      border: 2px solid var(--accent);      /* ana renkle çerçeve */
      box-shadow: 0 18px 50px rgba(2,6,23,.25);
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      overflow: hidden;
      position: relative;
    }
    .editor .toolbar {
      background: #0a0f1f;
      border-bottom: 1px solid #1e293b;
    }
    .dot { width: 12px;height: 12px;border-radius:9999px; }
    .dot.red{background:#f43f5e}.dot.yellow{background:#f59e0b}.dot.green{background:#10b981}

    .code {
      position: relative;
      padding: 18px 20px 28px 56px;
      counter-reset: line;
      min-height: 260px;
      line-height: 1.6;
      font-size: 14px;
      white-space: pre-wrap;
      word-break: break-word;

    }
    .code .line {
      min-height: 1.6em;   /* line-height ile aynı oranda tut */
      position: relative;
      display: block;
      padding-left: 6px;
    }
  .code .line:empty::after {
      content: '\00a0';
  }

    .code .line::before{
      counter-increment: line;
      content: counter(line);
      position: absolute;
      left: -36px;
      width: 28px;
      text-align: right;
      color: #64748b;
    }
    .caret {
      display:inline-block;
      width: 8px;
      height: 1.1em;
      background: #e5e7eb;
      margin-left: 2px;
      animation: blink 1s step-start infinite;
      vertical-align: -0.1em;
    }
    @keyframes blink { 50% { opacity: 0; } }

    /* Hata bandı (editör kaybolmuyor) */
    .error-banner{
      background: rgba(239, 68, 68, .08);
      border: 1px solid rgba(239,68,68,.35);
      color: #991b1b;
      backdrop-filter: blur(4px);
    }

    /* 404 paneli (editörün altında görünür) */
    .panel-404{
      background: linear-gradient(180deg, #ffffff, #fff);
      border: 1px solid #e5e7eb;
      border-left: 4px solid var(--accent);
      color: #0f172a;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(15,23,42,.08);
    }

    /* üst şerit (marka vurgusu) */
    .accent-bar {
      height: 4px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      border-radius: 9999px;
    }
  </style>
</head>
<body>

  <?php require_once 'navbar.php'; ?>

  <main class="max-w-5xl mx-auto px-4 py-12 space-y-8">
    <div class="accent-bar"></div>

    <!-- 1) KOD YAZILIYOR EDITÖRÜ (ekranda kalır) -->
    <section id="editorStage" class="editor">
      <div class="toolbar flex items-center gap-2 px-4 py-3">
        <span class="dot red"></span><span class="dot yellow"></span><span class="dot green"></span>
        <span class="ml-3 text-slate-400 text-sm">/var/www/html/<b>rookieverse</b>/index.php</span>
        <span class="ml-auto text-xs text-slate-500">PHP 8.2 • Tailwind • Rookieverse</span>
      </div>

      <div id="codeArea" class="code"></div>

      <!-- Hata bandı (editörün İÇİNDE, editör kaybolmuyor) -->
      <div id="errorBanner" class="error-banner hidden mx-4 mb-4 mt-2 rounded-lg p-3 text-sm">
        <div class="flex items-start gap-2">
          <i data-lucide="circle-alert" class="w-4 h-4 mt-0.5 text-red-500"></i>
          <div>
            <div class="font-semibold text-red-600">Fatal error</div>
            <div class="text-red-700/90">
              Uncaught <span class="font-mono">PDOException</span>: SQLSTATE[42S02]: Base table or view not found:
              <span class="font-mono">1146</span> Table '<span class="font-mono">rookieverse</span>.<span class="font-mono">cousres</span>' doesn't exist
              in <span class="font-mono">/var/www/html/rookieverse/index.php</span> on line <span class="font-mono">12</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2) 404 PANELİ (editörün ALTINDA) -->
    <section id="panel404" class="panel-404 p-8 hidden">
      <div class="flex items-center gap-3">
        <i data-lucide="bug" class="w-6 h-6 text-custom-yellow"></i>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
          Sayfa Bulunamadı <span class="text-custom-yellow">/ 404</span>
        </h1>
      </div>
      <p class="mt-3 text-lg text-slate-700">
        Kod derlenirken bir şeyler ters gitti. Aradığın sayfa şu an mevcut değil.
      </p>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        <a style="color: white" href="<?php echo BASE_URL; ?>/"
           class="inline-flex items-center gap-2 rounded-lg bg-custom-yellow text-slate-900 px-5 py-2.5 font-semibold hover:brightness-95">
          <i data-lucide="home" style="color: white" class="w-5 h-5"></i> Ana Sayfaya Dön
        </a>
        <a href="<?php echo BASE_URL; ?>/courses.php"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold hover:border-custom-yellow/60">
          <i data-lucide="book-open" class="w-5 h-5"></i> Kursları Keşfet
        </a>
        <a href="<?php echo BASE_URL; ?>/games"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold hover:border-custom-yellow/60">
          <i data-lucide="gamepad-2" class="w-5 h-5"></i> Oyun Oyna
        </a>
      </div>
    </section>
  </main>

  <script>
      function highlightCode(line) {
      let html = line
        // HTML escape
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

      // Yorumlar
      html = html.replace(/(\/\/.*)/g, '<span class="token-comment">$1</span>');

      // Stringler
      html = html.replace(/('[^']*'|"[^"]*")/g, '<span class="token-string">$1</span>');

      // Değişkenler ($...)
      html = html.replace(/(\$[a-zA-Z_][a-zA-Z0-9_]*)/g, '<span class="token-variable">$1</span>');

      // Sayılar
      html = html.replace(/\b(\d+)\b/g, '<span class="token-number">$1</span>');

      // PHP keywords
      const phpKeywords = ['foreach','echo','as','require','include','return','if','else','new','function'];
      const sqlKeywords = ['SELECT','FROM','WHERE','LIMIT','INSERT','UPDATE','DELETE','ORDER','BY'];
      const keywords = [...phpKeywords, ...sqlKeywords];

      keywords.forEach(kw => {
        const regex = new RegExp('\\b(' + kw + ')\\b', 'gi');
        html = html.replace(regex, '<span class="token-keyword">$1</span>');
      });

      // Fonksiyon isimleri
      html = html.replace(/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*(?=\()/g, '<span class="token-function">$1</span>');

      return html;
    }

    // Daha hızlı canlı yazım: kısa gecikmeler
    const lines = [
      "<\u003fphp",
      "$pdo = get_db_connection();",
      "$stmt = $pdo->prepare('SELECT id, title, level FROM courses ORDER BY created_at DESC LIMIT 6');",
      "$stmt->execute();",
      "$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);",
      "",
      "foreach ($courses as $course) {",
      "    echo '<li>' . htmlspecialchars($course['title']) . '</li>';",
      "}",
      "",
      "",
      "$stmt2 = $pdo->query('SELECT count(*) FROM cousres');",
      "echo $stmt2->fetchColumn();",
      "?>"
    ];

    const codeArea = document.getElementById('codeArea');
    const errorBanner = document.getElementById('errorBanner');
    const panel404 = document.getElementById('panel404');

    let lineIndex = 0, colIndex = 0;

    function appendLine() {
      const span = document.createElement('span');
      span.className = 'line';
      span.setAttribute('data-line', String(lineIndex + 1));
      span.innerHTML = '';
      codeArea.appendChild(span);
      return span;
    }
    let currentLineEl = appendLine();

    function typeNextChar() {
      const currentLine = lines[lineIndex] ?? '';
      if (colIndex <= currentLine.length) {
        const partial = currentLine.slice(0, colIndex);
        currentLineEl.innerHTML = `<span>${highlightCode(partial)}</span><span class="caret"></span>`;

        colIndex++;
        const delay = 6 + Math.random()*18;   // ⚡ daha hızlı
        setTimeout(typeNextChar, delay);
      } else {
        currentLineEl.innerHTML = `<span>${highlightCode(currentLine)}</span>`;
        lineIndex++; colIndex = 0;

        if (lineIndex < lines.length) {
          currentLineEl = appendLine();
          setTimeout(typeNextChar, 35);       // satır arası da hızlı
        } else {
          setTimeout(showErrorThenReveal404, 380);
        }
      }
    }

    function showErrorThenReveal404(){
      // Editör KALIR, sadece hata bandı görünür
      errorBanner.classList.remove('hidden');
      errorBanner.animate([{opacity:0, transform:'translateY(6px)'},{opacity:1, transform:'translateY(0)'}], {duration:260, easing:'ease-out'});

      // Kısa bekleme sonrası editörü bırakıp 404 panelini da ALTA ekrana getir
      setTimeout(() => {
        panel404.classList.remove('hidden');
        panel404.animate([{opacity:0, transform:'translateY(8px)'},{opacity:1, transform:'translateY(0)'}], {duration:360, easing:'ease-out'});
        // 404 görünürken sayfayı hafif aşağı kaydır
        panel404.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 650);
    }

    function escapeHtml(str){
      return str
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;');
    }

    document.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();
      typeNextChar();
    });
  </script>
</body>
</html>
