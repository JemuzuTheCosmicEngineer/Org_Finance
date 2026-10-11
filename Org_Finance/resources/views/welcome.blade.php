<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Basta link ng font whahahahah-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">

    <title>
        ICS FINANCIAL SYSTEM
    </title>
    <link rel="icon" type="image/png" href="/images/favicon.png">

    <body>
        <!-- Facebook-style layout: big logo + title on the left, log in card on the right -->
        <main class="fb-wrap">

            <section class="fb-brand">
                <img src="/images/ics-logo.png" alt="ICS Financial System logo" class="fb-logo">
                <h1>ICS FINANCIAL SYSTEM</h1>
                <p class="fb-tag">Manage the records, reports and users of the Integrated Computer Society in one place.</p>
            </section>

            <section class="fb-card">
                <h2 class="sr-only">Log in</h2>
                <!-- The login is sent to Laravel by the script at the bottom (no inline onsubmit anymore) -->
                <form id="loginForm">
                    <input type="text" id="username" placeholder="Email or username" required>
                    <input type="password" id="password" placeholder="Password" required>
                    <button type="submit">Log in</button>
                </form>
                <hr class="fb-sep">
                <p class="fb-note">Need an account? Ask your ICS administrator.</p>
            </section>

        </main>

        <footer class="fb-foot">
            <img src="/images/ics-society-logo.png" alt="Integrated Computer Society logo" class="fb-mini">
            <img src="/images/npc-logo.png" alt="Navotas Polytechnic College logo" class="fb-mini">
            <span>Integrated Computer Society<br>Navotas Polytechnic College</span>
        </footer>

{{-- ===== LOGIN ANIMATION VIDEO + STYLE ===== --}}
<video id="icsTransition" src="/animations/login.webm" muted playsinline preload="auto"></video>

<style>
    #icsTransition {
        position: fixed;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        width: 100vw;          /* full width */
        height: 80vh;          /* <<< change height here */
        object-fit: fill;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
    }
    #icsTransition.show {
        opacity: 0.7;          /* <<< change opacity here (keep the same on the dashboard) */
        pointer-events: auto;
    }
</style>
{{-- ===== END LOGIN ANIMATION VIDEO + STYLE ===== --}}

{{-- ===== WRONG PASSWORD POPUP ===== --}}
<img id="icsWrong" src="/images/wrongpass.png" alt="Invalid Username or Password!">

<style>
    /* ================= WRONG PASSWORD POPUP (fade in, fade out) ================= */
    #icsWrong {
        position: fixed;
        top: 50%;
        left: 50%;
        width: min(260px, 70vw);   /* <<< popup size */
        height: auto;
        transform: translate(-50%, -50%) scale(0.92);
        opacity: 0;
        visibility: hidden;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
        z-index: 10000;
        pointer-events: none;
        transition: opacity 0.35s ease, transform 0.35s ease, visibility 0s linear 0.35s;
    }
    #icsWrong.show {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
        pointer-events: auto;
        cursor: pointer;
        transition: opacity 0.35s ease, transform 0.35s ease, visibility 0s;
    }
</style>
{{-- ===== END WRONG PASSWORD POPUP ===== --}}


{{-- ===== TRANSITION (first half) - sits BEHIND the loading animation ===== --}}
<video id="icsTransA" src="/animations/transition_a.webm" muted playsinline preload="auto"></video>

<style>
    #icsTransA {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        object-fit: fill;
        z-index: 9998;         /* one layer BEHIND your loading animation (9999) */
        opacity: 0;
        pointer-events: none;
    }
    #icsTransA.show {
        opacity: 1;
    }
</style>

<script>
    (function () {
        var main = document.getElementById('icsTransition');   // your loading animation video
        var t = document.getElementById('icsTransA');
        // Starts this many ms after the loading animation starts. The half is about 0.8 s long
        // and the dashboard starts loading at 3400 ms, so 2500 makes it finish right at that moment.
        var TRANS_AT = 2500;   // <<< ms; change this to move the transition earlier/later

        if (!main || !t) return;

        main.addEventListener('playing', function () {
            setTimeout(function () {
                t.classList.add('show');
                t.currentTime = 0;
                try { sessionStorage.setItem('icsTransPlay', '1'); } catch (err) {}
                var pr = t.play();
                if (pr && pr.catch) pr.catch(function () {});
            }, TRANS_AT);
        }, { once: true });
    })();
</script>
{{-- ===== END TRANSITION (first half) ===== --}}

{{-- ===== LOGIN (real Laravel login + loading animation + wrong password popup) =====
     The email/username and password are sent to Laravel's /login route.
     Only when Laravel says "ok" does the loading animation play and the dashboard open. --}}
<script>
    (function () {
        var form  = document.getElementById('loginForm');
        var v     = document.getElementById('icsTransition');
        var wrong = document.getElementById('icsWrong');
        var busy  = false;
        var timer = null;

        var NAV_AT   = 3400;   // <<< ms after the animation starts playing, then the dashboard loads
        var WRONG_MS = 2000;   // <<< ms the wrong-password popup stays before fading out

        var LOGIN_URL  = "{{ route('login.post') }}";
        var DASH_URL   = "{{ route('dashboard') }}";
        var FORM_TOKEN = "{{ csrf_token() }}";

        /* ----- wrong password popup ----- */
        function hideWrong() {
            clearTimeout(timer);
            wrong.classList.remove('show');
        }
        function showWrong() {
            clearTimeout(timer);
            wrong.classList.add('show');
            timer = setTimeout(hideWrong, WRONG_MS);
        }
        wrong.addEventListener('click', hideWrong);          // click the popup to close it early

        /* ----- loading animation, then open the dashboard ----- */
        function playAnimationThenGo() {
            var navigated = false;
            function go() {
                if (navigated) return;
                navigated = true;
                window.location.href = DASH_URL;
            }

            v.classList.add('show');
            v.currentTime = 0;

            v.addEventListener('playing', function () {
                try { sessionStorage.setItem('icsAnimPlay', '1'); } catch (err) {}
                setTimeout(go, NAV_AT);
            }, { once: true });

            v.addEventListener('error', go, { once: true });
            setTimeout(go, 6000);                            // safety: never get stuck

            var pr = v.play();
            if (pr && pr.catch) pr.catch(go);
        }

        /* ----- the login itself ----- */
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;
            busy = true;

            var headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            // current token from Laravel's XSRF-TOKEN cookie; falls back to the one in this page
            var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            if (m) headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
            else headers['X-CSRF-TOKEN'] = FORM_TOKEN;

            fetch(LOGIN_URL, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: headers,
                body: JSON.stringify({
                    username: document.getElementById('username').value,
                    password: document.getElementById('password').value
                })
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) {
                    return { ok: res.ok && data.ok === true, status: res.status, data: data };
                });
            }).then(function (r) {
                if (r.ok) { playAnimationThenGo(); return; }   // busy stays true while the animation plays

                busy = false;
                if (r.status === 419) { alert('This page expired. It will reload now.'); location.reload(); return; }
                if (r.status >= 500)  { alert('Server error (' + r.status + '). Check storage/logs/laravel.log.'); return; }
                if (r.data.message && /inactive/i.test(r.data.message)) { alert(r.data.message); return; }
                showWrong();
            }, function () {
                busy = false;
                alert('Cannot reach the server. Is it running?');
            });
        });
    })();
</script>
{{-- ===== END LOGIN ===== --}}


    </body>
</html>

<!----CSS---->
<style>
    * { box-sizing: border-box; }

    body {
        background-image: url("/images/ICS-General-Assembly-2.pptx.png");
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: Arial, sans-serif;
    }

    /* text only for screen readers */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }

    /* ================= PAGE LAYOUT (left = brand, right = log in card) ================= */
    .fb-wrap {
        flex: 1;
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
        padding: 40px 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 64px;
    }

    /* ----- left side: big logo + title ----- */
    .fb-brand {
        flex: 1 1 480px;
        max-width: 520px;
    }

    .fb-logo {
        display: block;
        width: min(340px, 60vw);       /* <<< logo size */
        height: auto;
        margin: 0 0 18px -12px;
        /* same lift + glow the logo has on the dashboard logout screen, so the dark logo stays visible */
        filter: brightness(1.8) drop-shadow(0 0 18px rgba(255, 52, 112, 0.75));
        animation: icsFloat 3.6s ease-in-out infinite;   /* <<< speed: bigger number = slower */
    }

    /* gentle up and down, on repeat */
    @keyframes icsFloat {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-10px); }   /* <<< distance: bigger number = moves more */
    }

    .fb-brand h1 {
        font-family: 'Orbitron', sans-serif;
        color: #ff858a;
        font-size: 34px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: 1px;
        margin: 0 0 14px;
        text-shadow: 0 0 12px rgba(255, 80, 80, 0.45), 0 2px 10px rgba(0, 0, 0, 0.6);
    }

    .fb-tag {
        color: #f7dddd;
        font-size: 22px;
        line-height: 1.4;
        margin: 0;
        max-width: 440px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
    }

    /* ----- right side: the log in card ----- */
    .fb-card {
        flex: 0 0 396px;
        width: 396px;
        padding: 24px 20px 22px;
        border-radius: 8px;
        background-color: rgba(14, 3, 5, 0.78);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 133, 138, 0.35);
        box-shadow: 0 0 28px rgba(170, 20, 30, 0.35), 0 10px 25px rgba(0, 0, 0, 0.6);
    }

    .fb-card form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* username + password textboxes */
    .fb-card input[type="text"],
    .fb-card input[type="password"] {
        width: 100%;
        padding: 15px 16px;
        font-size: 17px;
        border-radius: 6px;
        outline: none;
        background-color: rgba(255, 255, 255, 0.07);
        color: #ffffff;
        border: 1px solid rgba(255, 133, 138, 0.45);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .fb-card input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }
    .fb-card input[type="text"]:focus,
    .fb-card input[type="password"]:focus {
        border-color: #ff858a;
        box-shadow: 0 0 8px rgba(255, 80, 80, 0.55);
    }
    /* stops the browser's autofill from turning the boxes white/yellow */
    .fb-card input:-webkit-autofill {
        -webkit-text-fill-color: #ffffff;
        box-shadow: 0 0 0 1000px #2e1416 inset;
        caret-color: #ffffff;
    }

    /* log in button */
    .fb-card button {
        width: 100%;
        padding: 14px 16px;
        font-size: 20px;
        font-weight: bold;
        color: #ffffff;
        border-radius: 6px;
        cursor: pointer;
        background: linear-gradient(135deg, #8b1a1f, #c0262d);
        border: 1px solid rgba(255, 133, 138, 0.6);
        transition: background 0.2s ease;
    }
    .fb-card button:hover {
        background: linear-gradient(135deg, #a8222a, #e0343c);
    }
    .fb-card button:focus-visible,
    .fb-card input:focus-visible {
        outline: 2px solid #ff858a;
        outline-offset: 2px;
    }

    .fb-sep {
        border: 0;
        height: 1px;
        margin: 22px 0 18px;
        background: linear-gradient(90deg, transparent, #ff858a, transparent);
    }

    .fb-note {
        margin: 0;
        text-align: center;
        font-size: 15px;
        color: #f7dddd;
    }

    /* ================= FOOTER (the two small logos) ================= */
    .fb-foot {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        padding: 14px 24px;
        color: #f7dddd;
        font-size: 13px;
        line-height: 1.4;
        background-color: rgba(14, 3, 5, 0.78);
        backdrop-filter: blur(6px);
        border-top: 1px solid rgba(255, 133, 138, 0.35);
    }
    .fb-mini {
        height: 44px;                  /* <<< small logo size */
        width: auto;
        display: block;
    }

    /* ================= SMALL SCREENS (one column, like Facebook on a phone) ================= */
    @media (max-width: 900px) {
        .fb-wrap {
            flex-direction: column;
            gap: 28px;
            padding: 28px 16px;
        }
        .fb-brand {
            flex: none;
            max-width: 460px;
            text-align: center;
        }
        .fb-logo {
            width: min(200px, 50vw);
            margin: 0 auto 12px;
        }
        .fb-brand h1 { font-size: 26px; }
        .fb-tag { font-size: 17px; margin: 0 auto; }
        .fb-card {
            flex: none;
            width: 100%;
            max-width: 396px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .fb-card input, .fb-card button { transition: none; }
        .fb-logo { animation: none; }
    }
</style>