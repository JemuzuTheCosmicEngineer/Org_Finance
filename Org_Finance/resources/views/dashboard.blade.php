<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICS FINANCIAL SYSTEM - Dashboard</title>
    <link rel="icon" type="image/png" href="/images/favicon.png">

    <!-- Google Font for Headers -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- TOP BAR -->
    <header class="header-bar">
        <div class="brand">
            <img src="/images/ics-logo.png" alt="ICS logo" class="brand-logo">
            <h1>ICS FINANCIAL SYSTEM</h1>
        </div>
        <button class="burger-btn" onclick="toggleMenu()" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <!-- BACKDROP OVERLAY -->
    <div id="menuOverlay" class="menu-overlay" onclick="toggleMenu()"></div>

    <!-- SIDE DRAWER NAVIGATION MENU -->
    <nav id="sideMenu" class="side-menu">
        <h3>Menu</h3>
        <ul>
            <li><a href="#dashboard">Dashboard</a></li>
            <li><a href="#information">Information Management</a></li>
            <li><a href="#reports">Reports</a></li>
            <li><a href="#users">User Management</a></li>
            <li><a href="#activity">Activity Logs</a></li>
        </ul>

        <!-- LOG OUT -->
        <form method="POST" id="logoutForm" class="menu-logout"
              action="{{ Route::has('logout') ? route('logout') : url('/logout') }}"
              data-login-url="{{ Route::has('login') ? route('login') : url('/') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 4h3a2 2 0 0 1 2 2v14"/>
                    <path d="M2 20h3"/>
                    <path d="M13 20h9"/>
                    <path d="M10 12v.01"/>
                    <path d="M13 4.562v16.157a1 1 0 0 1-1.242.97L5 20V5.562a2 2 0 0 1 1.515-1.94l4-1A2 2 0 0 1 13 4.561Z"/>
                </svg>
                <span>Log Out</span>
            </button>
        </form>
    </nav>

    <!-- LOGOUT ANIMATION (logo fades in at the center, then out, then goes to the login page) -->
    <div id="icsLogout" class="logout-screen" aria-hidden="true">
        <img src="/images/ics-logo.png" alt="">
    </div>

    <!-- ===== MODULES (added) ===== -->
    <section id="icsModules" class="ics-wrap">
        <div id="v-dashboard" class="ics-view"></div>
        <div id="v-information" class="ics-view"></div>
        <div id="v-reports" class="ics-view"></div>
        <div id="v-users" class="ics-view"></div>
        <div id="v-activity" class="ics-view"></div>
    </section>

    <!-- FOOTER: the two small logos (ICS Society + Navotas Polytechnic College) -->
    <footer class="ics-footer">
        <div class="ics-footer-logos">
            <img src="/images/ics-society-logo.png" alt="Integrated Computer Society logo">
            <span class="ics-footer-divider" aria-hidden="true"></span>
            <img src="/images/npc-logo.png" alt="Navotas Polytechnic College logo">
        </div>
        <p class="ics-footer-text">
            <strong>Integrated Computer Society</strong>
            <span>Navotas Polytechnic College</span>
            <small>ICS Financial System &copy; {{ date('Y') }}</small>
        </p>
    </footer>
    <div id="icsModal" class="ics-modal" role="dialog" aria-modal="true"></div>
    <div id="icsToast" class="ics-toast" role="status"></div>
    <!-- ===== END MODULES ===== -->

{{-- ===== DASHBOARD ANIMATION (paste just before </body> in dashboard.blade.php) ===== --}}
<video id="icsIntro" src="/animations/dashboard.webm" muted playsinline preload="auto"></video>

<style>
    #icsIntro {
        position: fixed;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        width: 100vw;          /* full width */
        height: 80vh;          /* <<< keep the same as the login page */
        object-fit: fill;
        opacity: 0;            /* becomes visible once it is playing */
        z-index: 9999;
        pointer-events: none;
    }
</style>

<script>
    (function () {
        var v = document.getElementById('icsIntro');
        var SHOW_OPACITY = 0.7;                             // <<< keep the same as the login page
        var fromLogin = false;

        try {
            fromLogin = sessionStorage.getItem('icsAnimPlay') === '1';
            sessionStorage.removeItem('icsAnimPlay');
        } catch (err) {}

        function hide() { v.style.display = 'none'; }

        // Opened directly or refreshed (not coming from the login animation): no animation
        if (!fromLogin) { hide(); return; }

        v.addEventListener('playing', function () { v.style.opacity = SHOW_OPACITY; }, { once: true });
        v.addEventListener('ended', hide);
        v.addEventListener('error', hide);
        setTimeout(hide, 6000);                             // safety: never block the page

        var pr = v.play();
        if (pr && pr.catch) pr.catch(hide);
    })();
</script>
{{-- ===== END DASHBOARD ANIMATION ===== --}}

{{-- ===== TRANSITION (second half) - paste BELOW your other blocks, just above </body> in dashboard.blade.php =====
     Nothing from your loading animation is removed or changed. This sits BEHIND it. --}}
<video id="icsTransB" src="/animations/transition_b.webm" muted playsinline preload="auto"></video>

<style>
    #icsTransB {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        object-fit: fill;
        z-index: 9998;         /* one layer BEHIND your loading animation (9999) */
        pointer-events: none;
        /* same pink + black top strip as the first half, so there is no flash while this video loads */
        background: linear-gradient(#000 0, #000 18%, #ff3470 18%, #ff3470 100%);
    }
</style>

<script>
    (function () {
        var v = document.getElementById('icsTransB');
        var fromLogin = false;

        try {
            fromLogin = sessionStorage.getItem('icsTransPlay') === '1';
            sessionStorage.removeItem('icsTransPlay');
        } catch (err) {}

        function hide() { v.style.display = 'none'; }

        // Opened directly or refreshed (not coming from the login transition): nothing to show
        if (!fromLogin) { hide(); return; }

        // once the video is really playing, drop the placeholder so the wipe-down can reveal the page
        v.addEventListener('playing', function () { v.style.background = 'none'; }, { once: true });
        v.addEventListener('ended', hide);
        v.addEventListener('error', hide);
        setTimeout(hide, 4000);                              // safety: never block the page

        var pr = v.play();
        if (pr && pr.catch) pr.catch(hide);
    })();
</script>
{{-- ===== END TRANSITION (second half) ===== --}}

{{-- ===== MENU SWIPE TRANSITION (paste just above </body> in dashboard.blade.php) ===== --}}
<div id="icsSwipe" class="ics-swipe" aria-hidden="true">
    <img src="/images/ics-logo.png" alt="">
</div>

<style>
    .ics-swipe {
        position: fixed;
        inset: 0;
        z-index: 999;                    /* above the dark overlay (999, earlier in the DOM), BELOW the side menu (1000) */
        display: none;
        align-items: center;
        justify-content: center;
        transform: translateX(100%);     /* parked off-screen on the right, behind the menu */
        pointer-events: auto;            /* blocks clicks while the swipe is running */
        background: radial-gradient(circle at center, #3a0505 0%, #120000 70%);
        will-change: transform;
    }

    /* pink glowing edges: the left one leads on the way in, the right one trails on the way out */
    .ics-swipe::before,
    .ics-swipe::after {
        content: "";
        position: absolute;
        top: 0;
        bottom: 0;
        width: 6px;
        background: #ff3470;
        box-shadow: 0 0 24px 6px rgba(255, 52, 112, 0.75);
    }
    .ics-swipe::before { left: 0; }
    .ics-swipe::after  { right: 0; }

    .ics-swipe img {
        width: min(220px, 50vw);
        height: auto;
        opacity: 0;
        transform: scale(0.7);
        filter: brightness(1.8) drop-shadow(0 0 18px rgba(255, 52, 112, 0.75));
    }
</style>

<script>
    (function () {
        var panel = document.getElementById('icsSwipe');
        if (!panel) return;
        var logo = panel.querySelector('img');
        var busy = false, bypass = false;

        var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
        var T_IN   = reduce ? 1 : 800;    // <<< swipe across the screen (ms)
        var T_HOLD = reduce ? 1 : 450;    // <<< how long the logo stays fully covered (ms)
        var T_OUT  = reduce ? 1 : 800;    // <<< swipe off to the other side (ms)
        var EASE   = 'cubic-bezier(0.65, 0, 0.35, 1)';   // ease in + ease out

        function closeMenu() {
            var m = document.getElementById('sideMenu');
            if (m && m.classList.contains('open')) toggleMenu();
        }

        function run(link) {
            busy = true;
            panel.style.display = 'flex';

            // 1) swipe in: from behind the menu (right) to the left, logo fades in once it is covered
            panel.animate(
                [{ transform: 'translateX(100%)' }, { transform: 'translateX(0)' }],
                { duration: T_IN, easing: EASE, fill: 'forwards' }
            );
            logo.animate(
                [{ opacity: 0, transform: 'scale(0.7)' }, { opacity: 1, transform: 'scale(1)' }],
                { duration: T_IN * 0.6, delay: T_IN * 0.5, easing: EASE, fill: 'both' }
            );

            setTimeout(function () {
                // 2) screen fully covered: change the page and close the menu
                bypass = true; link.click(); bypass = false;
                closeMenu();

                // 3) continue the swipe to the left; the logo vanishes with it
                setTimeout(function () {
                    panel.animate(
                        [{ transform: 'translateX(0)' }, { transform: 'translateX(-100%)' }],
                        { duration: T_OUT, easing: EASE, fill: 'forwards' }
                    );
                    var lo = logo.animate(
                        [{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(1.12)' }],
                        { duration: T_OUT * 0.6, easing: EASE, fill: 'forwards' }
                    );
                    setTimeout(function () {
                        panel.getAnimations().forEach(function (a) { a.cancel(); });
                        logo.getAnimations().forEach(function (a) { a.cancel(); });
                        panel.style.display = 'none';
                        busy = false;
                    }, T_OUT + 30);
                }, T_HOLD);
            }, T_IN + 30);
        }

        // Capture phase: runs before your dashboard.js handlers, then re-clicks the link once covered
        document.addEventListener('click', function (e) {
            if (bypass) return;
            var a = e.target.closest && e.target.closest('#sideMenu ul a[href^="#"]');
            if (!a) return;
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;

            e.preventDefault();
            e.stopPropagation();
            if (busy) return;

            if (a.getAttribute('href') === (location.hash || '#dashboard')) { closeMenu(); return; }  // same page: no transition
            run(a);
        }, true);
    })();
</script>
{{-- ===== END MENU SWIPE TRANSITION ===== --}}

</body>
</html>

<!-- CSS -->
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Arial, sans-serif;
        background-image: url("/images/ICS-General-Assembly-2.pptx.png");
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        min-height: 100vh;
        color: #333;
    }

    /* TOP NAVIGATION BAR */
    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: rgba(100, 3, 3, 0.95);
        padding: 15px 30px;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    /* LOGO + TITLE (added) */
    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-logo {
        height: 42px;
        width: auto;
        /* no background box; a light glow keeps the dark logo visible on the maroon bar */
        filter: drop-shadow(0 0 2px rgba(255, 255, 255, 0.9))
                drop-shadow(0 0 6px rgba(255, 255, 255, 0.5));
    }

    .header-bar h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 22px;
        letter-spacing: 1px;
    }

    /* BURGER MENU BUTTON */
    .burger-btn {
        background: rgba(12, 0, 0, 0.55);
        border: 1px solid rgba(255, 52, 112, 0.7);
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 44px;
        height: 40px;
        padding: 11px 9px;
        z-index: 1001;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .burger-btn:hover {
        background-color: #ff3470;
        border-color: #ff3470;
    }

    .burger-btn span {
        width: 100%;
        height: 3px;
        background-color: #ff8a8a;
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .burger-btn:hover span {
        background-color: #ffffff;
    }

    /* bars turn into an X while the menu is open */
    body:has(.side-menu.open) .burger-btn span:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
    body:has(.side-menu.open) .burger-btn span:nth-child(2) { opacity: 0; }
    body:has(.side-menu.open) .burger-btn span:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

    /* SIDE DRAWER MENU */
    .side-menu {
        position: fixed;
        top: 0;
        right: -280px; /* Hidden off-screen by default */
        width: 280px;
        height: 100%;
        background-color: rgba(18, 0, 0, 0.96);
        backdrop-filter: blur(8px);
        border-left: 1px solid #ff3470;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.6);
        transition: right 0.4s ease;
        z-index: 1000;
        padding: 80px 25px 30px 25px;
        overflow-x: hidden;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .side-menu > * {
        flex-shrink: 0;
    }

    /* LOG OUT BUTTON (pinned to the bottom of the menu) */
    .menu-logout {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 52, 112, 0.35);
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 12px 14px;
        background: rgba(12, 0, 0, 0.6);
        border: 1px solid #ff3470;
        border-radius: 8px;
        color: #ffd0d0;
        font-family: Arial, sans-serif;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .logout-btn svg {
        width: 22px;
        height: 22px;
        flex: none;
    }

    .logout-btn:hover {
        background-color: #ff3470;
        color: #ffffff;
    }

    .logout-btn:focus-visible {
        outline: 2px solid #ffd666;
        outline-offset: 2px;
    }

    .side-menu.open {
        right: 0; /* Slides into view when toggled */
    }

    /* LOGOUT ANIMATION */
    .logout-screen {
        position: fixed;
        inset: 0;
        z-index: 10000;                /* above the menu and the intro videos */
        display: none;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at center, #3a0505 0%, #120000 70%);
        opacity: 0;
    }

    .logout-screen.on {
        display: flex;
        animation: icsScreenIn 0.5s ease-in-out forwards;
    }

    .logout-screen img {
        width: min(220px, 50vw);
        height: auto;
        opacity: 0;
        transform: scale(0.7);
        filter: brightness(1.8) drop-shadow(0 0 18px rgba(255, 52, 112, 0.75));
    }

    .logout-screen.on img {
        animation: icsLogoIn 0.9s ease-in-out 0.25s forwards;     /* <<< logo appears (ease in, ease out) */
    }

    .logout-screen.on.out img {
        animation: icsLogoOut 0.5s ease-in-out forwards;          /* <<< logo fades away */
    }

    @keyframes icsScreenIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes icsLogoIn   { from { opacity: 0; transform: scale(0.7); } to { opacity: 1; transform: scale(1); } }
    @keyframes icsLogoOut  { from { opacity: 1; transform: scale(1); }   to { opacity: 0; transform: scale(1.12); } }

    @media (prefers-reduced-motion: reduce) {
        .logout-screen.on, .logout-screen.on img { animation-duration: 0.01s; animation-delay: 0s; }
    }

    /* SCATTERED LOGO BACKGROUND (sits behind the menu content, slightly blurred) */
    .side-menu::before {
        content: "";
        position: absolute;
        inset: -12px;                 /* a little bigger than the menu so the blur never fades at the edges */
        z-index: -1;
        pointer-events: none;
        background-image:
            url("/images/ics-logo.png"), url("/images/ics-logo.png"), url("/images/ics-logo.png"),
            url("/images/ics-logo.png"), url("/images/ics-logo.png"), url("/images/ics-logo.png"),
            url("/images/ics-logo.png"), url("/images/ics-logo.png"), url("/images/ics-logo.png");
        background-repeat: no-repeat;
        /* one size + one position per logo, so they look scattered */
        background-size: 100px, 64px, 130px, 58px, 90px, 70px, 120px, 56px, 84px;
        background-position: 92% 3%, 6% 14%, 30% 27%, 88% 38%, 4% 50%, 70% 58%, 22% 72%, 92% 80%, 52% 96%;
        opacity: 0.4;                 /* <<< visibility of the logos: raise for stronger, lower for subtler */
        filter: blur(2.5px) brightness(2.4) saturate(1.3);   /* <<< blur amount; brightness lifts the dark logo */
    }

    .side-menu h3 {
        font-family: 'Orbitron', sans-serif;
        color: #ff8a8a;
        font-size: 18px;
        border-bottom: 2px solid #ff3470;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }

    .side-menu ul {
        list-style: none;
    }

    .side-menu li {
        margin-bottom: 15px;
    }

    .side-menu a {
        text-decoration: none;
        color: #f3dede;
        font-size: 16px;
        font-weight: bold;
        padding: 10px 12px;
        display: block;
        border-radius: 6px;
        border-left: 3px solid transparent;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .side-menu a:hover {
        background-color: rgba(255, 52, 112, 0.25);
        border-left-color: #ff3470;
        color: #ffffff;
    }

    /* OVERLAY BACKDROP WHEN MENU IS OPEN */
    .menu-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.4);
        z-index: 999;
    }

    .menu-overlay.active {
        display: block;
    }

    /* MAIN DASHBOARD CONTENT AREA */
    .content-container {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }

    .main-card {
        background-color: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(5px);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 900px;
        text-align: center;
    }

    .main-card h2 {
        font-family: 'Orbitron', sans-serif;
        color: #640303;
        margin-bottom: 15px;
    }

    .main-card p {
        font-size: 16px;
        color: #444;
    }

    /* ===== FOOTER LOGOS (added) ===== */

    /* the page becomes a column so the footer can sit at the bottom of the screen on short pages */
    body {
        display: flex;
        flex-direction: column;
    }

    /* dashboard.css centres .ics-wrap with "margin: 0 auto"; inside a flex column that would shrink it
       to its content width, so it is told to fill the width (it is still capped at 960px by dashboard.css) */
    .ics-wrap {
        width: 100%;
    }

    .ics-footer {
        margin-top: auto;                         /* pushes the footer to the bottom */
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 22px;
        padding: 18px 24px;
        background: rgba(18, 0, 0, 0.88);
        backdrop-filter: blur(6px);
        border-top: 1px solid rgba(255, 52, 112, 0.5);
        box-shadow: 0 -6px 24px rgba(0, 0, 0, 0.45);
    }

    .ics-footer-logos {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .ics-footer-logos img {
        height: 64px;                             /* <<< logo size */
        width: auto;
        /* soft light glow so the dark parts of the logos stay visible on the dark bar */
        filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.25));
    }

    .ics-footer-divider {
        width: 1px;
        height: 48px;
        background: linear-gradient(transparent, #ff3470, transparent);
    }

    .ics-footer-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1.35;
        color: #e6b3b3;
        font-size: 13px;
    }

    .ics-footer-text strong {
        font-family: 'Orbitron', sans-serif;
        font-size: 14px;
        letter-spacing: 1px;
        color: #ff8a8a;
    }

    .ics-footer-text small {
        margin-top: 4px;
        font-size: 11px;
        color: #b88486;
    }

    @media (max-width: 600px) {
        .ics-footer {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        .ics-footer-text { align-items: center; }
        .ics-footer-logos img { height: 52px; }
    }

    @media print {
        .ics-footer { display: none; }            /* keeps printed reports clean */
    }
</style>

<!-- MODULES CSS (added) -->
<link rel="stylesheet" href="/css/dashboard.css">

<!-- JAVASCRIPT -->
<script>
    function toggleMenu() {
        const sideMenu = document.getElementById("sideMenu");
        const menuOverlay = document.getElementById("menuOverlay");

        sideMenu.classList.toggle("open");
        menuOverlay.classList.toggle("active");
    }

    /* LOG OUT: play the logo animation, log out, then go to the login page */
    (function () {
        var form = document.getElementById('logoutForm');
        var screen = document.getElementById('icsLogout');
        if (!form || !screen) return;
        var busy = false;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;
            busy = true;

            var loginUrl = form.getAttribute('data-login-url');
            screen.classList.add('on');
            try { sessionStorage.removeItem('icsSessionLogged'); } catch (err) {}

            // Use the CURRENT token from Laravel's XSRF-TOKEN cookie (the one baked into the page can be stale after login)
            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
            var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            if (m) {
                headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
            } else {
                var t = form.querySelector('[name="_token"]');               // fallback: the token in the form
                if (t) headers['X-CSRF-TOKEN'] = t.value;
            }

            var wait = new Promise(function (r) { setTimeout(r, 1700); });   // fade in + short hold
            var req = fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: headers
            }).then(function (res) { return { ok: res.ok, status: res.status }; },
                    function () { return { ok: false, status: 0 }; });

            Promise.all([wait, req]).then(function (r) {
                if (!r[1].ok) {
                    screen.classList.remove('on', 'out');
                    busy = false;
                    alert('Log out failed (error ' + r[1].status + '). Please tell your developer this number.');
                    return;
                }
                screen.classList.add('out');                                  // logo fades away
                setTimeout(function () { window.location.replace(loginUrl); }, 550);
            });
        });
    })();
</script>

<!-- MODULES JAVASCRIPT (added) -->
<script>
    window.ICS_USER = @json(optional(auth()->user())->name ?? 'Administrator');
    window.ICS_ROLE = @json(optional(auth()->user())->role ?? 'Admin');
</script>
<script src="/js/dashboard.js"></script>