<?php require_once 'config.php';
//a
$index = false; // Anasayfa olmadığını belirtmek için false yapıldı
session_start();
$isEn = CURRENT_LANG === 'en';
?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEn ? 'FRC 2026 Season: REBUILT - FRC Rookieverse' : 'FRC 2026 Sezonu: REBUILT - FRC Rookieverse' ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-EDSVL8LRCY');
    </script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { 'custom-yellow': '#E5AE32' } } }
    }
  </script>

</head>
<body class="bg-gray-50">

  <?php require_once 'navbar.php'; ?>

    <section id="rebuilt" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center justify-between p-8 bg-gray-100 rounded-lg shadow-inner mb-12">
                <div class="lg:w-1/2 mb-6 lg:mb-0 lg:pr-8">
                    <h1 class="text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight mb-4"><?= $isEn ? 'REBUILT™: Rebuild and Discover' : 'REBUILT™: Yeniden İnşa Et ve Keşfet' ?></h1>
                    <p class="text-lg text-gray-700">
                        <?= $isEn
                            ? 'Presented by Qualcomm, the <b>FIRST® AGE℠</b> season brings an archaeology-inspired challenge to robotics. In <b>REBUILT™</b>, teams reimagine the past using engineering design, strategy, and collaboration skills.'
                            : 'Qualcomm tarafından sunulan <b>FIRST® AGE℠</b> sezonu, arkeoloji temasıyla robotik dünyasına ilham veriyor. Takımlar, <b>REBUILT™</b> adlı bu yeni mücadelede mühendislik becerilerini kullanarak geçmişi yeniden hayal edecek. Her bir buluntu, her bir araç ve her bir sanat eseri, bizden önce gelen insanların ve fikirlerin hikayesini barındırıyor. Bu sezon, STEM becerilerini kullanarak geçmişin derinliklerine inecek ve bilime ışık tutacaksınız.' ?>
                    </p>
                </div>
                <div class="lg:w-1/2 flex justify-center">
                    <img src="first_age.png" alt="FRC REBUILT 2026 Season Logo" class="w-full max-w-sm">
                </div>
            </div>
        </div>
    </section>

    <section id="events" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4"><?= $isEn ? 'Upcoming Events' : 'Yaklaşan Etkinlikler' ?></h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto"><?= $isEn ? 'Follow the upcoming FRC timeline and off-season opportunities.' : 'Tüm takımları ilgilendiren global FRC takvimini takip edin.' ?></p>
            </div>
            <div class="space-y-6 sm:space-y-8 max-w-4xl mx-auto">
                <div class="relative flex flex-col sm:flex-row items-start sm:items-center bg-gray-50 p-4 sm:p-6 rounded-lg border-l-4 border-custom-yellow shadow-sm gap-4 sm:gap-6">

                    <!-- logo -->
                    <div class="flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-custom-yellow/10 rounded-full flex-shrink-0 self-center sm:self-auto">
                        <img src="cezeri.png" alt="Cezeri Logo" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover">
                    </div>

                    <!-- içerik -->
                    <div class="flex-1 w-full">
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-800">Ümraniye Off Season</h3>

                        <p class="text-gray-600 mt-2 text-sm sm:text-base leading-relaxed">
                            Teknotürk, Cezeri Robot Ligi altında genç mühendislerin ve robotik tutkunlarının katılımını teşvik etmek amacıyla, yıl içinde belirlenen farklı tarihlerde FRC formatında yarışmalar düzenleyecektir. Her bir yarışma, katılımcı takımların belirli bir oyun senaryosu içinde robotlarını tasarlamaları, inşa etmeleri ve programlamaları üzerine yoğunlaşacaktır. Takımlar, bu süreçte robotik mühendisliği, yazılım geliştirme ve stratejik düşünme konularında deneyim kazanacaklardır.
                        </p>

                        <!-- meta -->
                        <div class="mt-3 sm:mt-4 flex flex-col sm:flex-row sm:items-center text-gray-600 gap-2 sm:gap-6">
                            <div class="flex items-center text-sm sm:text-base">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-custom-yellow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M21 13V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"></path><path d="M3 10h18"></path><path d="M17 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path><path d="M17 21v-4h-4"></path></svg>
                                <span><b><?= $isEn ? 'Date:' : 'Tarih :' ?></b> <b><?= $isEn ? '17-19 October 2025' : '17–19 Ekim 2025' ?></b></span>
                            </div>

                            <div class="flex items-center text-sm sm:text-base">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-custom-yellow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span><b><?= $isEn ? 'Location:' : 'Yer : ' ?></b>
            <a href="https://maps.app.goo.gl/yg5Kwjd9GbyhzrVX9" target="_blank" class="text-gray-700 hover:text-custom-yellow transition-colors font-semibold">
              Tev Anadolu Lisesi Kapalı Spor Salonu, Ümraniye
            </a>
          </span>
                            </div>
                        </div>

                        <span class="mt-3 inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Off Season</span>
                    </div>

                    <!-- sağ üst aksiyonlar: mobilde altta, md'de sağ üst -->
                    <div class="flex items-center gap-3 self-end sm:self-auto sm:absolute sm:top-4 sm:right-4">
                        <a href="https://www.instagram.com/cezerirobotligi" target="_blank" class="text-custom-yellow hover:text-custom-yellow/80 transition-colors" aria-label="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
                        </a>
                        <a href="https://www.teknoturk.tech/cezeri" target="_blank" class="text-xs sm:text-sm font-semibold text-gray-500 hover:text-custom-yellow transition-colors">
                            Teknotürk - Cezeri Robot Ligi
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section id="regionals" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4"><?= $isEn ? 'Regional Events Calendar' : 'Türkiye Regional Turnuvaları' ?></h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto"><?= $isEn ? 'Official regional events where teams compete toward the Championship journey.' : 'Takımlarımızın Dünya Şampiyonası\'na giden yolda rekabet edeceği yerel turnuvalar.' ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">İstanbul Regional</h3>
                                <p class="text-gray-500 font-semibold">Başakşehir Spor Salonu - Başakşehir, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">3 - 5 Mart 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=İstanbul+Regional&dates=20260303T090000/20260305T180000&location=Başakşehir+Spor+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/tHyc7K9Qgdc1sh396" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Bosphorus Regional</h3>
                                <p class="text-gray-500 font-semibold">Başakşehir Spor Salonu - Başakşehir, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">6 - 8 Mart 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Bosphorus+Regional&dates=20260306T090000/20260308T180000&location=Başakşehir+Spor+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/tHyc7K9Qgdc1sh396" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Yeditepe Regional</h3>
                                <p class="text-gray-500 font-semibold">Başakşehir Spor Salonu - Başakşehir, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">9 - 11 Mart 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Yeditepe+Regional&dates=20260309T090000/20260311T180000&location=Başakşehir+Spor+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/tHyc7K9Qgdc1sh396" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Haliç Regional</h3>
                                <p class="text-gray-500 font-semibold">Türkiye Atletizm Federasyonu Atletizm Salonu - Ataköy, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">25 - 27 Mart 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Haliç+Regional&dates=20260325T090000/20260327T180000&location=Türkiye+Atletizm+Federasyonu+Atletizm+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/fXByaVNFSYrKqHdr9" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Marmara Regional</h3>
                                <p class="text-gray-500 font-semibold">Türkiye Atletizm Federasyonu Atletizm Salonu - Ataköy, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">28 - 30 Mart 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Marmara+Regional&dates=20260328T090000/20260330T180000&location=Türkiye+Atletizm+Federasyonu+Atletizm+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/fXByaVNFSYrKqHdr9" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Avrasya Regional</h3>
                                <p class="text-gray-500 font-semibold">Türkiye Atletizm Federasyonu Atletizm Salonu - Ataköy, İstanbul</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">31 Mart - 2 Nisan 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Avrasya+Regional&dates=20260331T090000/20260402T180000&location=Türkiye+Atletizm+Federasyonu+Atletizm+Salonu" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/fXByaVNFSYrKqHdr9" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Ankara Regional</h3>
                                <p class="text-gray-500 font-semibold">Devlet Bahçeli Eğitim Yaşam ve Spor Yerleşkesi - Ankara</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">7 - 9 Nisan 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Ankara+Regional&dates=20260407T090000/20260409T180000&location=Devlet+Bahçeli+Eğitim+Yaşam+ve+Spor+Yerleşkesi" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/W5L5voC4BmTbgATR7" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-custom-yellow/10 rounded-full mr-4"><i data-lucide="map-pin" class="h-6 w-6 text-custom-yellow"></i></div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800">Başkent Regional</h3>
                                <p class="text-gray-500 font-semibold">Devlet Bahçeli Eğitim Yaşam ve Spor Yerleşkesi - Ankara</p>
                            </div>
                        </div>
                        <div class="border-t pt-4 flex justify-between items-center">
                            <p class="text-lg font-bold text-custom-yellow">10 - 12 Nisan 2026</p>
                            <div class="flex items-center space-x-2">
                                <a href="https://www.google.com/calendar/render?action=TEMPLATE&text=Başkent+Regional&dates=20260410T090000/20260412T180000&location=Devlet+Bahçeli+Eğitim+Yaşam+ve+Spor+Yerleşkesi" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Takvime Ekle">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </a>
                                <a href="https://maps.app.goo.gl/W5L5voC4BmTbgATR7" target="_blank" class="text-gray-500 hover:text-custom-yellow transition-colors" title="Yol Tarifi Al">
                                    <i data-lucide="map" class="w-6 h-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

  <?php require_once 'footer.php'?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
  </script>
 
</body>
</html>