<?php

/*

 *

 */




?>



<style>

    /* Varsayılan: desktop menü açık, hamburger gizli */

    .nav-desktop { display: flex; }

    .nav-mobile-btn { display: none; }



    /* 1076px ve altı: desktop menü gizli, hamburger görünür */

    @media (max-width: 1076px) {

        .nav-desktop { display: none; }

        .nav-mobile-btn { display: inline-flex; }

    }



    /* off-canvas için küçük yardımcılar */

    .drawer-hidden { transform: translateX(100%); }

    .drawer-shown  { transform: translateX(0%); }

    .fade-0        { opacity: 0; }

    .fade-100      { opacity: 1; }

    /* Language switcher */
    .lang-switcher { position: relative; }
    .lang-switcher-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600;
        border: 1px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer;
        transition: border-color .2s;
    }
    .lang-switcher-btn:hover { border-color: #E5AE32; }
    .lang-dropdown {
        display: none; position: absolute; top: 100%; right: 0; margin-top: 4px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,.1); z-index: 100; min-width: 120px; overflow: hidden;
    }
    .lang-dropdown.show { display: block; }
    .lang-dropdown a {
        display: flex; align-items: center; gap: 8px; padding: 8px 14px;
        font-size: 13px; color: #374151; text-decoration: none; transition: background .15s;
    }
    .lang-dropdown a:hover { background: #f9fafb; }
    .lang-dropdown a.active { background: #fffbeb; color: #E5AE32; font-weight: 600; }

</style>

<nav class="bg-white shadow-sm border-b sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex h-16 items-center justify-between">

            <!-- Logo -->

            <a class="flex items-center space-x-2" href="<?= BASE_URL ?>">

                <span class="rookieverse">ROOKIEVERSE</span>

            </a>



            <!-- Desktop menu (sadece >1076px) -->

            <div class="nav-desktop items-center space-x-8">

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/courses.php"><?= __('nav.courses') ?></a>

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/teams.php"><?= __('nav.teams') ?></a>

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/games.php"><?= __('nav.games') ?></a>

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/season.php"><?= __('nav.season') ?></a>

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/frc-terms.php"><?= __('nav.frc_glossary') ?></a>

                <a class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/contact.php"><?= __('nav.contact') ?></a>

                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button class="lang-switcher-btn" id="lang-toggle" type="button">
                        <?= CURRENT_LANG === 'tr' ? 'TR' : 'EN' ?>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="lang-dropdown" id="lang-dropdown">
                        <a href="<?= BASE_URL ?>/set_lang.php?lang=tr&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="<?= CURRENT_LANG === 'tr' ? 'active' : '' ?>">Türkçe</a>
                        <a href="<?= BASE_URL ?>/set_lang.php?lang=en&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="<?= CURRENT_LANG === 'en' ? 'active' : '' ?>">English</a>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/<?= isset($_SESSION['team_logged_in']) ? 'team/panel.php' : 'team-login.php' ?>"

                   class="inline-flex items-center justify-center gap-2 h-9 bg-[#E5AE32] hover:bg-[#E5AE32]/90 text-white font-semibold px-6 py-2 rounded-lg">

                    <?php if (isset($_SESSION['team_logged_in'])): ?>

                        # Team <?= (int)$_SESSION['team_number'] ?>

                    <?php else: ?>

                        <i data-lucide="log-in" class="w-4 h-4"></i> <?= __('nav.team_login') ?>

                    <?php endif; ?>

                </a>

            </div>



            <!-- Hamburger (sadece ≤1076px) -->

            <button id="mobile-menu-button"

                    class="nav-mobile-btn inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none"

                    aria-controls="mobile-drawer" aria-expanded="false" type="button">

                <span class="sr-only"><?= __('nav.toggle_menu') ?></span>

                <i data-lucide="menu" class="w-6 h-6" id="icon-open"></i>

                <i data-lucide="x" class="w-6 h-6 hidden" id="icon-close"></i>

            </button>

        </div>

    </div>



    <!-- Overlay -->

    <div id="mobile-overlay"

         class="fixed inset-0 bg-black/40 backdrop-blur-[1px] z-[60] hidden fade-0 transition-opacity duration-300"></div>



    <!-- Drawer -->

    <aside id="mobile-drawer"

           class="fixed inset-y-0 right-0 z-[70] w-[90vw] max-w-sm bg-white border-l shadow-xl
                drawer-hidden transition-transform duration-300 will-change-transform"

           role="dialog" aria-modal="true" aria-labelledby="mobile-drawer-title">

        <div class="h-16 px-4 flex items-center justify-between border-b">

            <div id="mobile-drawer-title" class="font-semibold"><?= __('nav.menu') ?></div>

            <button id="mobile-close" class="p-2 rounded-md hover:bg-gray-100" type="button" aria-label="<?= __('nav.close') ?>">

                <i data-lucide="x" class="w-6 h-6"></i>

            </button>

        </div>

        <nav class="px-4 py-3 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/courses.php"><?= __('nav.courses') ?></a>

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/teams.php"><?= __('nav.teams') ?></a>

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/games.php"><?= __('nav.games') ?></a>

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/season.php"><?= __('nav.season') ?></a>

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/frc-terms.php"><?= __('nav.frc_glossary') ?></a>

            <a class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-[#E5AE32] hover:bg-gray-50" href="<?= BASE_URL ?>/contact.php"><?= __('nav.contact') ?></a>

            <!-- Mobile Language Switcher -->
            <div class="flex gap-2 px-3 py-2">
                <a href="<?= BASE_URL ?>/set_lang.php?lang=tr&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="flex-1 text-center py-2 rounded-md text-sm font-semibold <?= CURRENT_LANG === 'tr' ? 'bg-[#E5AE32] text-white' : 'border border-gray-300 text-gray-700' ?>">🇹🇷 Türkçe</a>
                <a href="<?= BASE_URL ?>/set_lang.php?lang=en&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="flex-1 text-center py-2 rounded-md text-sm font-semibold <?= CURRENT_LANG === 'en' ? 'bg-[#E5AE32] text-white' : 'border border-gray-300 text-gray-700' ?>">🇬🇧 English</a>
            </div>

            <a href="<?= BASE_URL ?>/<?= isset($_SESSION['team_logged_in']) ? 'team/panel.php' : 'team-login.php' ?>"

               class="mt-2 inline-flex w-full items-center justify-center gap-2 h-10 bg-[#E5AE32] hover:bg-[#E5AE32]/90 text-white font-semibold px-4 rounded-lg">

                <?php if (isset($_SESSION['team_logged_in'])): ?>

                    # Team <?= (int)$_SESSION['team_number'] ?>

                <?php else: ?>

                    <i data-lucide="log-in" class="w-4 h-4"></i> <?= __('nav.team_login') ?>

                <?php endif; ?>

            </a>

        </nav>

    </aside>

</nav>

<!-- Cookie Info Toast (auto-accept on interaction) -->

<div id="cookie-toast" class="fixed right-4 bottom-4 z-[100] hidden">

    <div class="w-[min(92vw,420px)] rounded-xl border border-gray-200 bg-white shadow-lg p-4 sm:p-5">

        <div class="flex items-start gap-3">

      <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#E5AE32]/10">

        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#E5AE32]" viewBox="0 0 24 24" fill="none" stroke="currentColor">

          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"

                d="M20 12a8 8 0 10-16 0 8 8 0 0016 0Zm-9 4h2m-2-8h2l-1 6"/>

        </svg>

      </span>

            <div class="flex-1">

                <p class="text-sm text-gray-700 leading-5">

                    <?= __('cookie.message') ?>

                    <a href="<?= BASE_URL ?>/privacy.php" class="font-semibold text-[#E5AE32] hover:underline"><?= __('cookie.details') ?></a>

                </p>

                <div class="mt-3 flex items-center gap-2">

                    <button id="cookie-ok"

                            class="px-4 py-2 rounded-lg bg-[#E5AE32] text-white hover:bg-[#E5AE32]/90 text-sm font-semibold">

                        <?= __('cookie.ok') ?>

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>







<script>

    (function () {

        const btn     = document.getElementById('mobile-menu-button');

        const drawer  = document.getElementById('mobile-drawer');

        const overlay = document.getElementById('mobile-overlay');

        const close   = document.getElementById('mobile-close');

        const openI   = document.getElementById('icon-open');

        const closeI  = document.getElementById('icon-close');



        const openDrawer = () => {

            drawer.classList.remove('drawer-hidden'); drawer.classList.add('drawer-shown');

            overlay.classList.remove('hidden'); overlay.classList.remove('fade-0'); overlay.classList.add('fade-100');

            document.body.style.overflow = 'hidden';

            openI?.classList.add('hidden'); closeI?.classList.remove('hidden');

            btn?.setAttribute('aria-expanded','true');

            try { window.lucide?.createIcons?.(); } catch(e){}

        };

        const closeDrawer = () => {

            drawer.classList.remove('drawer-shown'); drawer.classList.add('drawer-hidden');

            overlay.classList.remove('fade-100'); overlay.classList.add('fade-0');

            setTimeout(()=> overlay.classList.add('hidden'), 300);

            document.body.style.overflow = '';

            openI?.classList.remove('hidden'); closeI?.classList.add('hidden');

            btn?.setAttribute('aria-expanded','false');

        };



        btn?.addEventListener('click', () => {

            const isOpen = drawer.classList.contains('drawer-shown');

            isOpen ? closeDrawer() : openDrawer();

        });

        overlay?.addEventListener('click', closeDrawer);

        close?.addEventListener('click', closeDrawer);



        document.addEventListener('keydown', (e) => {

            if (e.key === 'Escape' && drawer.classList.contains('drawer-shown')) closeDrawer();

        });



        drawer?.addEventListener('click', (e) => {

            const a = e.target.closest('a');

            if (a) closeDrawer();

        });



    })();

    // Language switcher dropdown
    (function(){
        const toggle = document.getElementById('lang-toggle');
        const dropdown = document.getElementById('lang-dropdown');
        if (!toggle || !dropdown) return;

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => {
            dropdown.classList.remove('show');
        });
    })();

    (function () {

        const KEY = 'rv_cookie_consent';       // values: accepted | rejected

        const ACK = 'rv_cookie_ack';           // values: 1 (toast acknowledged)

        const DAYS = 365;



        function setCookie(name, value, days) {

            const d = new Date();

            d.setTime(d.getTime() + (days*24*60*60*1000));

            const secure = location.protocol === 'https:' ? ';Secure' : '';

            document.cookie = name + '=' + encodeURIComponent(value)

                + ';expires=' + d.toUTCString()

                + ';path=/;SameSite=Lax' + secure;

        }

        function getCookie(name) {

            const v = document.cookie.split('; ').find(r => r.startsWith(name + '='));

            return v ? decodeURIComponent(v.split('=')[1]) : null;

        }



        function showToast() {

            document.getElementById('cookie-toast')?.classList.remove('hidden');

        }

        function hideToast() {

            document.getElementById('cookie-toast')?.classList.add('hidden');

        }







        // Auto-accept strategy:

        function autoAccept() {

            const consent = getCookie(KEY);

            if (!consent) {

                setCookie(KEY, 'accepted', DAYS);

            }

            if (!getCookie(ACK)) showToast();

        }



        function attachFirstInteractionAccept() {

            const once = () => {

                autoAccept();

                window.removeEventListener('pointerdown', once, {passive:true});

                window.removeEventListener('keydown', once, {passive:true});

                window.removeEventListener('scroll', once, {passive:true});

                clearTimeout(timer);

            };

            window.addEventListener('pointerdown', once, {passive:true});

            window.addEventListener('keydown', once, {passive:true});

            window.addEventListener('scroll', once, {passive:true});

            const timer = setTimeout(once, 10000);

        }



        document.addEventListener('DOMContentLoaded', () => {

            const consent = getCookie(KEY);

            const ack = getCookie(ACK);



            if (consent === 'rejected') {

                showToast();

            } else if (consent === 'accepted') {

                if (!ack) showToast();

            } else {

                attachFirstInteractionAccept();

            }



            document.getElementById('cookie-ok')?.addEventListener('click', () => {

                setCookie(KEY, 'accepted', DAYS);

                setCookie(ACK, '1', DAYS);

                hideToast();

            });





        });

    })();



</script>




