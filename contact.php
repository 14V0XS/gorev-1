<?php
require_once 'config.php';
session_start();

// Google reCAPTCHA Secret Key'ini buraya ekle
define('RECAPTCHA_SECRET_KEY', '6LcmgucrAAAAAO39Mj17DgqOSXWx-K6_i5sFMHHN');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- RECAPTCHA DOĞRULAMASI BAŞLANGIÇ ---
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $recaptcha_response = $_POST['g-recaptcha-response'];

        // Google'ın doğrulama API'sine istek gönder
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $responseKeys = json_decode($result, true);

        // Doğrulama başarılıysa devam et
        if ($responseKeys["success"]) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];

            try {
                $pdo = get_db_connection();
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $subject, $message]);

                // Başarı durumunda formun kendisini değil, sadece başarı mesajını döndürelim.
                // Bu, JavaScript ile popup göstermek için daha uygun.
                // HTTP 200 OK (başarılı) kodu döner.
                exit(); // Script'i burada bitiriyoruz.

            } catch (Throwable $e) {
                http_response_code(500);
                echo 'DB error: ' . htmlspecialchars($e->getMessage());
                exit();
            }

        } else {
            // reCAPTCHA doğrulaması başarısız oldu
            http_response_code(400); // Bad Request
            echo 'reCAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.';
            exit();
        }
    } else {
        // reCAPTCHA yanıtı boş geldi
        http_response_code(400);
        echo 'Lütfen "Ben robot değilim" kutusunu işaretleyin.';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('contact.page_title') ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/contact.css">
  
  <script>
    tailwind.config = {
      theme: { extend: { colors: { 'custom-yellow': '#E5AE32' } } }
    }
  </script>
</head>
<body class="bg-gray-50">

  <?php require_once 'navbar.php'; ?>

  <div class="min-h-screen py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?= __('contact.title') ?></h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto"><?= __('contact.description') ?></p>
      </div>
      
      <div class="bg-white p-8 rounded-2xl shadow-lg border-2 border-transparent hover:border-custom-yellow/50 transition-all duration-300">
          <h2 class="text-2xl font-bold text-gray-900 flex items-center mb-1"><i data-lucide="send" class="mr-3 text-custom-yellow h-7 w-7"></i> <?= __('contact.form_title') ?></h2>
          <p class="text-base text-gray-600 mb-6"><?= __('contact.form_desc') ?></p>
          <form method="post" id="contact-form" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2"><label for="name" class="font-medium text-gray-700"><?= __('contact.name') ?></label><input id="name" name="name" type="text" required placeholder="<?= __('contact.name_placeholder') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-custom-yellow focus:ring-custom-yellow"></div>
              <div class="space-y-2"><label for="email" class="font-medium text-gray-700"><?= __('contact.email') ?></label><input id="email" name="email" type="email" required placeholder="<?= __('contact.email_placeholder') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-custom-yellow focus:ring-custom-yellow"></div>
            </div>
            <div class="space-y-2"><label for="subject" class="font-medium text-gray-700"><?= __('contact.subject') ?></label><input id="subject" name="subject" type="text" required placeholder="<?= __('contact.subject_placeholder') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-custom-yellow focus:ring-custom-yellow"></div>
            <div class="space-y-2"><label for="message" class="font-medium text-gray-700"><?= __('contact.message') ?></label><textarea id="message" name="message" required rows="6" placeholder="<?= __('contact.message_placeholder') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-custom-yellow focus:ring-custom-yellow resize-none"></textarea></div>
            
            <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="6LcmgucrAAAAAKG54g57tXOGDPxO3tMdk9odOYfP"></div>
            </div>

            <button type="submit" class="w-full bg-custom-yellow hover:bg-opacity-90 text-white font-semibold py-3 text-lg rounded-lg flex items-center justify-center">
              <span class="button-content flex items-center"><i data-lucide="send" class="mr-2 h-5 w-5"></i> <?= __('contact.send') ?></span>
            </button>
            <div id="form-error" class="text-red-600 text-center mt-4"></div>
          </form>
      </div>
      
      <div id="sss" class="mt-16"><h2 class="text-3xl font-bold text-gray-900 mb-8 text-center"><?= __('contact.faq_title') ?></h2><div class="space-y-4 max-w-4xl mx-auto"><div class="border-2 hover:border-custom-yellow/50 transition-all duration-200 rounded-lg"><button class="faq-trigger w-full flex justify-between items-center text-left p-6"><span class="text-lg font-bold text-gray-900"><?= __('contact.faq1_q') ?></span><i data-lucide="chevron-down" class="faq-icon text-custom-yellow"></i></button><div class="faq-answer"><p class="text-base text-gray-600 px-6 pb-6">Evet! Rookieverse'deki tüm kurslar ve oyunlar tamamen ücretsizdir. Türk FIRST topluluğuna destek olmak için bu platformu geliştirdik.</p></div></div><div class="border-2 hover:border-custom-yellow/50 transition-all duration-200 rounded-lg"><button class="faq-trigger w-full flex justify-between items-center text-left p-6"><span class="text-lg font-bold text-gray-900"><?= __('contact.faq2_q') ?></span><i data-lucide="chevron-down" class="faq-icon text-custom-yellow"></i></button><div class="faq-answer"><p class="text-base text-gray-600 px-6 pb-6">Yeni takım kurmak için önce FIRST'e kayıt olmanız gerekir. Size bu süreçte yardımcı olmak için detaylı rehberlerimiz mevcuttur.</p></div></div><div class="border-2 hover:border-custom-yellow/50 transition-all duration-200 rounded-lg"><button class="faq-trigger w-full flex justify-between items-center text-left p-6"><span class="text-lg font-bold text-gray-900"><?= __('contact.faq3_q') ?></span><i data-lucide="chevron-down" class="faq-icon text-custom-yellow"></i></button><div class="faq-answer"><p class="text-base text-gray-600 px-6 pb-6">Şu anda sertifika sistemi geliştirme aşamasındadır. Yakında kurs tamamlama sertifikaları sunmayı planlıyoruz.</p></div></div><div class="border-2 hover:border-custom-yellow/50 transition-all duration-200 rounded-lg"><button class="faq-trigger w-full flex justify-between items-center text-left p-6"><span class="text-lg font-bold text-gray-900"><?= __('contact.faq4_q') ?></span><i data-lucide="chevron-down" class="faq-icon text-custom-yellow"></i></button><div class="faq-answer"><p class="text-base text-gray-600 px-6 pb-6">Kurslarımızın PDF versiyonları ve videoları indirip offline olarak kullanabilmeniz mümkündür.</p></div></div></div></div>
    </div>
  </div>

  <div id="success-popup" class="popup-overlay"><div class="popup-content"><svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg><h3 class="text-2xl font-bold text-gray-800"><?= __('contact.success_title') ?></h3><p class="text-gray-600 mt-2"><?= __('contact.success_desc') ?></p></div></div>

  <?php require_once 'footer.php'?>

  <script>
    lucide.createIcons();

    // SSS script'i
    const faqTriggers = document.querySelectorAll('.faq-trigger');
    faqTriggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        const answer = trigger.nextElementSibling;
        const isActive = trigger.classList.contains('active');

        if (!isActive) {
          trigger.classList.add('active');
          answer.style.maxHeight = answer.scrollHeight + 'px';
        } else {
          trigger.classList.remove('active');
          answer.style.maxHeight = null;
        }
      });
    });

    // Form gönderimini yöneten JavaScript
    const contactForm = document.getElementById('contact-form');
    const successPopup = document.getElementById('success-popup');
    const formError = document.getElementById('form-error');

    contactForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Sayfanın yeniden yüklenmesini engelle
        formError.textContent = ''; // Hata mesajını temizle

        const formData = new FormData(this);

        fetch('', { // Mevcut sayfaya post et
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok && response.status !== 500) {
                // Başarılı (HTTP 200)
                successPopup.style.display = 'flex';
                contactForm.reset(); // Formu sıfırla
                grecaptcha.reset(); // reCAPTCHA'yı sıfırla
                setTimeout(() => {
                    successPopup.style.display = 'none';
                }, 4000); // 4 saniye sonra popup'ı gizle
            } else {
                // Hata durumu (HTTP 400 veya 500)
                return response.text().then(text => { throw new Error(text) });
            }
        })
        .catch(error => {
            // Hata mesajını ekranda göster
            formError.textContent = error.message || 'Bir hata oluştu, lütfen tekrar deneyin.';
            grecaptcha.reset(); // Hata durumunda reCAPTCHA'yı sıfırla
        });
    });
  </script>

</body>
</html>