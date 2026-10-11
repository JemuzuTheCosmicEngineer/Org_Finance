(function () {
    /* ---------- AUTHENTICATION / ROLES (taken from the logged-in Laravel user) ---------- */
    var USER = window.ICS_USER || 'Administrator';
    var ROLE = String(window.ICS_ROLE || 'Admin').toLowerCase();
    var isAdmin = ROLE === 'admin';
    var canW = isAdmin || ROLE === 'treasurer';          // create / update / delete records
    var VIEWS = ['dashboard', 'information', 'reports', 'users', 'activity'];

    /* ---------- DATA (loaded from the MySQL database through Laravel) ---------- */
    var R = [];   // financial records
    var U = [];   // users (only sent to admins)
    var L = [];   // activity logs

    function pad2(n) { return n < 10 ? '0' + n : '' + n; }
    var today = function () { var d = new Date(); return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); };

    /* ---------- API ---------- */
    function xsrf() {
        var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }
    function api(method, url, body) {
        var opt = {
            method: method,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf() }
        };
        if (body !== undefined) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
        return fetch(url, opt).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) {
                    var msg = data.message || ('Request failed (error ' + res.status + ').');
                    if (res.status === 401 || res.status === 419) msg = 'Your session expired. Please refresh the page and log in again.';
                    if (data.errors) { var k = Object.keys(data.errors)[0]; if (k) msg = data.errors[k][0]; }
                    var err = new Error(msg); err.status = res.status; throw err;
                }
                return data;
            });
        });
    }
    function load() {
        return api('GET', '/ics/data').then(function (d) {
            R = d.records || []; U = d.users || []; L = d.logs || [];
        });
    }

    /* ---------- HELPERS ---------- */
    function $(id) { return document.getElementById(id); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function money(n) { return '\u20B1' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function toast(m) { var t = $('icsToast'); t.textContent = m; t.classList.add('on'); setTimeout(function () { t.classList.remove('on'); }, 2800); }

    /* ---------- AUDIT TRAIL ----------
       Create / update / delete are logged by Laravel itself.
       The browser only reports LOGIN and filtered report views. */
    function log(action, module, details) {
        return api('POST', '/ics/logs', { action: action, module: module, details: details }).catch(function () {});
    }

    /* ---------- VALIDATION (fast feedback; Laravel validates again on the server) ---------- */
    function vRec(f) {
        var e = {}, a = parseFloat(f.amount);
        if (!/^.{3,60}$/.test(f.title.trim())) e.title = 'Title must be 3-60 characters.';
        if (!(a > 0) || a > 10000000) e.amount = 'Enter an amount above 0 (max 10,000,000).';
        if (!f.date) e.date = 'Date is required.';
        else if (f.date > today()) e.date = 'Date cannot be in the future.';
        return e;
    }
    function vUser(f, id) {
        var e = {};
        if (!/^[A-Za-z][A-Za-z .'-]{2,49}$/.test(f.name.trim())) e.name = 'Name must be 3-50 letters.';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) e.email = 'Enter a valid email address.';
        else if (U.some(function (u) { return u.id !== id && u.email.toLowerCase() === f.email.trim().toLowerCase(); })) e.email = 'Email is already in use.';
        if ((!id || f.password) && !/^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(f.password)) e.password = 'Min 8 characters with a letter and a number.';
        return e;
    }

    /* ---------- VIEWS ---------- */
    var infoType = '';
    function ini(n) { return String(n || '?').trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase(); }
    function sign(r) { return (r.type === 'Income' ? '+' : '\u2212') + money(r.amount); }
    function head(t, s) { return '<header class="ph"><span class="ph-hex"></span><div><h2>' + t + '</h2><p class="ics-sub">' + s + '</p></div><span class="ics-who">' + esc(USER) + ' <em>' + esc(ROLE) + '</em></span></header>'; }
    function totals() {
        var i = 0, x = 0;
        R.forEach(function (r) { if (r.type === 'Income') i += +r.amount; else x += +r.amount; });
        return { i: i, x: x, b: i - x };
    }
    function acts(kind, id) { return '<div class="ld-act"><button class="ics-btn sm" onclick="ICS.form(\'' + kind + '\',' + id + ')">Edit</button><button class="ics-btn sm danger" onclick="ICS.ask(\'' + kind + '\',' + id + ')">Delete</button></div>'; }

    /* timeline used by the dashboard and the activity logs, grouped by day */
    function tl(list) {
        var out = '', day = '';
        list.forEach(function (l) {
            var p = String(l.time).split(','), d = p[0];
            if (d !== day) { day = d; out += '<div class="tl-day">' + esc(d) + '</div>'; }
            out += '<div class="tl-it a-' + esc(String(l.action).toLowerCase()) + '"><span class="tl-n"></span><div class="tl-b"><p><b>' + esc(l.user) + '</b><u>' + esc(l.action) + '</u>' + esc(l.details) + '</p><small>' + esc((p[1] || '').trim()) + '</small>' + (l.module ? '<em>' + esc(l.module) + '</em>' : '') + '</div></div>';
        });
        return out;
    }

    function hx(label, val, cls) { return '<div class="hx ' + (cls || '') + '"><div><small>' + label + '</small><b>' + val + '</b></div></div>'; }

    function dashboard() {
        var t = totals(), act = U.filter(function (u) { return u.status === 'Active'; }).length;
        $('v-dashboard').innerHTML = head('Dashboard', 'Read-only overview of the system.') +
            '<div class="vault"><div class="hx-row">' + hx('Income', money(t.i), 'in') + hx('Balance', money(t.b), 'bal') + hx('Expenses', money(t.x), 'ex') + '</div>' +
            '<div class="hx-row low">' + hx('Records', R.length) + hx('Active users', isAdmin ? act : '-') + '</div></div>' +
            '<div class="ics-panel"><h3>Recent activity</h3><div class="tl">' + (tl(L.slice(0, 6)) || '<p class="ics-empty">No activity yet.</p>') + '</div></div>';
    }

    function information() {
        var q = ($('iq') ? $('iq').value : '').toLowerCase(), ty = infoType;
        var list = R.filter(function (r) { return (!ty || r.type === ty) && (r.title + r.ref + r.category).toLowerCase().indexOf(q) > -1; });
        var rows = list.map(function (r) {
            return '<article class="ld ' + (r.type === 'Income' ? 'inc' : 'exp') + '"><div class="ld-ref">' + esc(r.ref) + '</div><div class="ld-main"><b>' + esc(r.title) + '</b><small>' + esc(r.category) + ' / ' + esc(r.date) + '</small></div><div class="ld-amt">' + sign(r) + '</div><span class="stamp ' + (r.status === 'Approved' ? 'ok' : '') + '">' + esc(r.status) + '</span>' + (canW ? acts('r', r.id) : '') + '</article>';
        }).join('');
        var seg = ['', 'Income', 'Expense'].map(function (v) { return '<button class="seg' + (ty === v ? ' on' : '') + '" onclick="ICS.type(\'' + v + '\')">' + (v || 'All') + '</button>'; }).join('');
        $('v-information').innerHTML = '<div class="ics-panel">' + head('Information Management', 'Create, view, update and delete financial records.') +
            '<div class="ics-bar"><div class="segs">' + seg + '</div><input class="grow" id="iq" placeholder="Search records..." value="' + esc(q) + '" oninput="ICS.refresh()">' +
            (canW ? '<button class="ics-btn" onclick="ICS.form(\'r\')">+ Add Record</button>' : '') + '</div>' +
            '<div class="ld-list">' + (rows || '<p class="ics-empty">No records found.</p>') + '</div></div>';
        var s = $('iq'); if (s && q) { s.focus(); s.setSelectionRange(q.length, q.length); }
    }

    function reports() {
        var f = $('rf') ? $('rf').value : '', to = $('rt') ? $('rt').value : '';
        var list = R.filter(function (r) { return (!f || r.date >= f) && (!to || r.date <= to); });
        var i = 0, x = 0, cat = {};
        list.forEach(function (r) { var a = +r.amount; if (r.type === 'Income') i += a; else x += a; cat[r.category] = (cat[r.category] || 0) + a; });
        var max = Math.max.apply(null, Object.keys(cat).map(function (k) { return cat[k]; }).concat([1]));
        var ip = (i + x) ? i / (i + x) * 100 : 50;
        var cats = Object.keys(cat).map(function (k) { return '<div class="lead"><div><span>' + esc(k) + '</span><i></i><b>' + money(cat[k]) + '</b></div><div class="ics-meter"><i style="width:' + (cat[k] / max * 100) + '%"></i></div></div>'; }).join('');
        var rows = list.map(function (r) { return '<div class="st ' + (r.type === 'Income' ? 'inc' : 'exp') + '"><span>' + esc(r.date) + '</span><span>' + esc(r.ref) + '</span><span>' + esc(r.title) + '</span><b>' + sign(r) + '</b></div>'; }).join('');
        $('v-reports').innerHTML = '<div class="ics-panel">' + head('Reports', 'Read-only financial summary. Filter by date range or print.') +
            '<div class="ics-bar"><label>From <input type="text" id="rf" readonly data-ics-date placeholder="YYYY-MM-DD" value="' + esc(f) + '" onchange="ICS.refresh()"></label><label>To <input type="text" id="rt" readonly data-ics-date placeholder="YYYY-MM-DD" value="' + esc(to) + '" onchange="ICS.refresh()"></label><button class="ics-btn ghost" onclick="window.print()">Print</button></div>' +
            '<div class="net"><small>Net balance</small><b>' + money(i - x) + '</b><div class="split"><i style="width:' + ip + '%"></i></div><div class="split-l"><span>Income ' + money(i) + '</span><span>Expenses ' + money(x) + '</span></div></div>' +
            '<h3>By category</h3>' + (cats || '<p class="ics-empty">No data for this range.</p>') +
            '<h3>Transactions</h3><div class="st-list">' + (rows || '<p class="ics-empty">No transactions.</p>') + '</div></div>';
        if (f || to) log('VIEW', 'Reports', 'Filtered report ' + (f || '...') + ' to ' + (to || '...'));
    }

    function users() {
        if (!isAdmin) { $('v-users').innerHTML = '<div class="ics-panel">' + head('User Management', 'Access denied.') + '<p class="ics-empty">Only administrators can manage users.</p></div>'; return; }
        var rows = U.map(function (u) {
            return '<article class="bd r-' + esc(String(u.role).toLowerCase()) + (u.status === 'Active' ? '' : ' off') + '"><span class="av">' + esc(ini(u.name)) + '</span><div class="bd-main"><b>' + esc(u.name) + '</b><small>' + esc(u.email) + '</small><span class="role">' + esc(u.role) + '</span><span class="dot">' + esc(u.status) + '</span></div>' + acts('u', u.id) + '</article>';
        }).join('');
        $('v-users').innerHTML = '<div class="ics-panel">' + head('User Management', 'Create, view, update and delete system users.') +
            '<div class="ics-bar"><button class="ics-btn" onclick="ICS.form(\'u\')">+ Add User</button></div><div class="bd-grid">' + rows + '</div></div>';
    }

    function activity() {
        var q = ($('aq') ? $('aq').value : '').toLowerCase();
        var list = L.filter(function (l) { return (l.user + l.action + l.module + l.details).toLowerCase().indexOf(q) > -1; });
        $('v-activity').innerHTML = '<div class="ics-panel">' + head('Activity Logs', 'Read-only audit trail. Entries cannot be edited or deleted.') +
            '<div class="ics-bar"><input class="grow" id="aq" placeholder="Search logs..." value="' + esc(q) + '" oninput="ICS.refresh()"></div>' +
            '<div class="tl">' + (tl(list) || '<p class="ics-empty">No log entries.</p>') + '</div></div>';
        var s = $('aq'); if (s && q) { s.focus(); s.setSelectionRange(q.length, q.length); }
    }

    var RENDER = { dashboard: dashboard, information: information, reports: reports, users: users, activity: activity };
    var current = 'dashboard';
    var seq = 0;

    /* Fetches fresh data from the database, then shows the view for the current #hash */
    function go() {
        var mine = ++seq;
        return load().then(function () {
            if (mine !== seq) return;                      // a newer navigation took over
            var h = (location.hash || '#dashboard').slice(1);
            current = VIEWS.indexOf(h) > -1 ? h : 'dashboard';
            VIEWS.forEach(function (v) { $('v-' + v).classList.toggle('on', v === current); });
            var mc = document.querySelector('.main-card');
            if (mc) mc.style.display = current === 'dashboard' ? '' : 'none';   // welcome card only on dashboard
            RENDER[current]();
            window.scrollTo(0, 0);
        }).catch(function (err) { toast(err.message); });
    }

    /* ---------- CRUD FORMS ---------- */
    function field(label, id, input, err) { return '<label for="' + id + '">' + label + '</label>' + input + '<div class="ics-err" id="e_' + id + '">' + (err ? esc(err) : '') + '</div>'; }
    function opts(arr, sel) { return arr.map(function (o) { return '<option' + (o === sel ? ' selected' : '') + '>' + o + '</option>'; }).join(''); }
    function modal(html) { var m = $('icsModal'); m.innerHTML = '<div class="ics-box">' + html + '</div>'; m.classList.add('on'); }
    function close() { $('icsModal').classList.remove('on'); }

    function form(kind, id) {
        if (kind === 'r' && !canW) return;
        if (kind === 'u' && !isAdmin) return;
        var d = kind === 'r' ? R.filter(function (x) { return x.id === id; })[0] : U.filter(function (x) { return x.id === id; })[0];
        d = d || {};
        var h;
        if (kind === 'r') {
            h = '<h3>' + (id ? 'Edit Record' : 'Add Record') + '</h3><div class="ics-form" id="frm">' +
                field('Title', 'f_title', '<input id="f_title" maxlength="60" value="' + esc(d.title) + '">') +
                field('Type', 'f_type', '<select id="f_type">' + opts(['Income', 'Expense'], d.type) + '</select>') +
                field('Category', 'f_category', '<select id="f_category">' + opts(['Membership Fees', 'Events', 'Donations', 'Supplies', 'Operations', 'Other'], d.category) + '</select>') +
                field('Amount (PHP)', 'f_amount', '<input id="f_amount" type="number" step="0.01" min="0" value="' + esc(d.amount) + '">') +
                field('Date', 'f_date', '<input id="f_date" type="date" max="' + today() + '" value="' + esc(d.date || today()) + '">') +
                field('Status', 'f_status', '<select id="f_status">' + opts(['Pending', 'Approved'], d.status) + '</select>');
        } else {
            h = '<h3>' + (id ? 'Edit User' : 'Add User') + '</h3><div class="ics-form" id="frm">' +
                field('Full name', 'f_name', '<input id="f_name" maxlength="50" value="' + esc(d.name) + '">') +
                field('Email', 'f_email', '<input id="f_email" type="email" value="' + esc(d.email) + '">') +
                field('Role', 'f_role', '<select id="f_role">' + opts(['Admin', 'Treasurer', 'Viewer'], d.role) + '</select>') +
                field('Status', 'f_status', '<select id="f_status">' + opts(['Active', 'Inactive'], d.status) + '</select>') +
                field('Password' + (id ? ' (leave blank to keep)' : ''), 'f_password', '<input id="f_password" type="password" autocomplete="new-password">');
        }
        modal(h + '<div class="ics-acts"><button class="ics-btn ghost" onclick="ICS.close()">Cancel</button><button class="ics-btn" onclick="ICS.submit(\'' + kind + '\',' + (id || 0) + ')">Save</button></div></div>');
    }

    function val(id) { var e = $(id); return e ? e.value : ''; }
    function showErr(e, ids) {
        ids.forEach(function (k) { var inp = $('f_' + k), m = $('e_f_' + k); if (m) m.textContent = e[k] || ''; if (inp) inp.classList.toggle('bad', !!e[k]); });
    }

    var saving = false;

    function submit(kind, id) {
        if (saving) return;
        var req;
        if (kind === 'r' && canW) {
            var f = { title: val('f_title'), type: val('f_type'), category: val('f_category'), amount: val('f_amount'), date: val('f_date'), status: val('f_status') };
            var e = vRec(f); showErr(e, ['title', 'amount', 'date']);
            if (Object.keys(e).length) return;
            f.title = f.title.trim(); f.amount = parseFloat(f.amount);
            req = id ? api('PUT', '/ics/records/' + id, f) : api('POST', '/ics/records', f);
        } else if (kind === 'u' && isAdmin) {
            var g = { name: val('f_name'), email: val('f_email'), role: val('f_role'), status: val('f_status'), password: val('f_password') };
            var e2 = vUser(g, id); showErr(e2, ['name', 'email', 'password']);
            if (Object.keys(e2).length) return;
            g.name = g.name.trim(); g.email = g.email.trim();
            if (!g.password) delete g.password;          // blank = keep the current password; Laravel hashes new ones
            req = id ? api('PUT', '/ics/users/' + id, g) : api('POST', '/ics/users', g);
        } else return;

        saving = true;
        req.then(function () {
            saving = false; close(); toast('Saved successfully.'); go();
        }).catch(function (err) {
            saving = false; toast(err.message);
        });
    }

    function ask(kind, id) {
        var item = (kind === 'r' ? R : U).filter(function (x) { return x.id === id; })[0];
        if (!item) return;
        modal('<h3>Confirm Delete</h3><p>Delete <b>' + esc(kind === 'r' ? item.title : item.name) + '</b>? This cannot be undone.</p><div class="ics-acts"><button class="ics-btn ghost" onclick="ICS.close()">Cancel</button><button class="ics-btn danger" onclick="ICS.del(\'' + kind + '\',' + id + ')">Delete</button></div>');
    }

    function del(kind, id) {
        var req;
        if (kind === 'r' && canW) req = api('DELETE', '/ics/records/' + id);
        else if (kind === 'u' && isAdmin) req = api('DELETE', '/ics/users/' + id);
        else return;

        req.then(function () {
            close(); toast('Deleted.'); go();
        }).catch(function (err) {
            close(); toast(err.message);
        });
    }

    /* ---------- INIT ---------- */
    window.ICS = { type: function (v) { infoType = v; information(); }, form: form, submit: submit, ask: ask, del: del, close: close, refresh: function () { RENDER[current](); }, dates: function () { return R.map(function (r) { return r.date; }); } };
    $('icsModal').addEventListener('click', function (e) { if (e.target === this) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    document.querySelectorAll('#sideMenu a').forEach(function (a) {
        a.addEventListener('click', function () { if ($('sideMenu').classList.contains('open')) toggleMenu(); });
    });
    window.addEventListener('hashchange', go);

    var firstVisit = false;
    try { if (!sessionStorage.getItem('icsSessionLogged')) { sessionStorage.setItem('icsSessionLogged', '1'); firstVisit = true; } } catch (e) {}
    (firstVisit ? log('LOGIN', 'Authentication', 'Session started') : Promise.resolve()).then(go);
})();
/* ICS popup calendar
 * Turns any <input data-ics-date> into a styled date picker (dark maroon/pink theme).
 * Sets the input's value as YYYY-MM-DD and fires a normal "change" event,
 * so existing onchange="..." handlers keep working.
 * Load AFTER dashboard.js:  <script src="/js/ics-calendar.js"></script>
 */
(function () {
    'use strict';

    var CSS = `
    .ics-bar input[data-ics-date]{cursor:pointer;width:160px;padding-right:34px;background-repeat:no-repeat;background-position:right 10px center;background-size:16px;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ff8a8a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E")}
    .icsc-pop{position:absolute;z-index:1050;width:292px;background:#120000;border:1px solid #ff3470;border-radius:12px;padding:14px;color:#fff;
        box-shadow:0 10px 40px rgba(0,0,0,.7);font-family:Arial,sans-serif;animation:icscIn .15s ease}
    @keyframes icscIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
    .icsc-pop *{box-sizing:border-box}
    .icsc-top{display:flex;align-items:center;gap:6px;margin-bottom:10px}
    .icsc-top select{flex:1;min-width:0;background:rgba(255,255,255,.08);border:1px solid rgba(255,138,138,.5);color:#fff;border-radius:6px;padding:6px;font-size:13px;font-family:'Orbitron',Arial,sans-serif}
    .icsc-nav{background:#640303;color:#fff;border:1px solid #ff3470;border-radius:6px;width:30px;height:30px;cursor:pointer;font-size:16px;line-height:1;font-weight:bold;flex:none}
    .icsc-nav:hover{background:#ff3470}
    .icsc-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
    .icsc-dow{text-align:center;font-size:11px;font-weight:bold;color:#ff8a8a;padding:4px 0;text-transform:uppercase}
    .icsc-day{position:relative;height:34px;border:1px solid transparent;background:transparent;color:#f3dede;border-radius:6px;cursor:pointer;font-size:13px}
    .icsc-day:hover:not(:disabled){background:rgba(255,52,112,.3)}
    .icsc-day.out{color:#7a5555}
    .icsc-day.today{border-color:#ff8a8a}
    .icsc-day.inr{background:rgba(255,52,112,.18);border-radius:0}
    .icsc-day.sel{background:#ff3470;color:#fff;font-weight:bold;border-radius:6px}
    .icsc-day:disabled{opacity:.25;cursor:not-allowed}
    .icsc-day.mark::after{content:"";position:absolute;bottom:3px;left:50%;width:5px;height:5px;margin-left:-2.5px;border-radius:50%;background:#ffd666}
    .icsc-day:focus-visible,.icsc-nav:focus-visible,.icsc-link:focus-visible{outline:2px solid #ffd666;outline-offset:1px}
    .icsc-foot{display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:8px}
    .icsc-link{background:transparent;border:1px solid #ff3470;color:#fff;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:bold;cursor:pointer}
    .icsc-link:hover{background:#ff3470}
    .icsc-legend{font-size:11px;color:#e6b3b3}
    .icsc-legend i{display:inline-block;width:7px;height:7px;border-radius:50%;background:#ffd666;margin-right:5px}
    @media print{.icsc-pop{display:none!important}}
    `;

    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var DOW = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

    var pop = null, anchor = null, view = { y: 0, m: 0 };

    function injectCss() {
        if (document.getElementById('icscStyle')) return;
        var s = document.createElement('style');
        s.id = 'icscStyle';
        s.textContent = CSS;
        document.head.appendChild(s);
    }
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parse(s) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s || '')) return null;
        var p = s.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }
    function val(id) { var e = document.getElementById(id); return e ? e.value : ''; }

    // Disallow a "From" after "To" and a "To" before "From"
    function limits() {
        var min = parse(anchor.getAttribute('data-min')), max = parse(anchor.getAttribute('data-max'));
        if (anchor.id === 'rf' && parse(val('rt'))) max = parse(val('rt'));
        if (anchor.id === 'rt' && parse(val('rf'))) min = parse(val('rf'));
        return { min: min ? iso(min) : null, max: max ? iso(max) : null };
    }

    function marks() {
        var m = {};
        try { (window.ICS && window.ICS.dates ? window.ICS.dates() : []).forEach(function (d) { m[d] = true; }); } catch (e) {}
        return m;
    }

    function render() {
        if (!pop) return;
        var lim = limits(), mk = marks();
        var from = val('rf'), to = val('rt'), todayK = iso(new Date());
        var first = new Date(view.y, view.m, 1);
        var start = new Date(view.y, view.m, 1 - first.getDay());
        var thisYear = new Date().getFullYear();
        var y0 = Math.min(view.y, thisYear - 10), y1 = Math.max(view.y, thisYear + 1);

        var h = '<div class="icsc-top"><button type="button" class="icsc-nav" data-act="prev" aria-label="Previous month">&#8249;</button>';
        h += '<select data-sel="m" aria-label="Month">';
        for (var i = 0; i < 12; i++) h += '<option value="' + i + '"' + (i === view.m ? ' selected' : '') + '>' + MONTHS[i] + '</option>';
        h += '</select><select data-sel="y" aria-label="Year">';
        for (var y = y0; y <= y1; y++) h += '<option' + (y === view.y ? ' selected' : '') + '>' + y + '</option>';
        h += '</select><button type="button" class="icsc-nav" data-act="next" aria-label="Next month">&#8250;</button></div>';

        h += '<div class="icsc-grid">';
        for (var d = 0; d < 7; d++) h += '<div class="icsc-dow">' + DOW[d] + '</div>';
        for (var n = 0; n < 42; n++) {
            var dt = new Date(start.getFullYear(), start.getMonth(), start.getDate() + n);
            var k = iso(dt), c = 'icsc-day';
            if (dt.getMonth() !== view.m) c += ' out';
            if (k === todayK) c += ' today';
            if (mk[k]) c += ' mark';
            if (k === from || k === to) c += ' sel';
            else if (from && to && k > from && k < to) c += ' inr';
            var off = (lim.min && k < lim.min) || (lim.max && k > lim.max);
            h += '<button type="button" class="' + c + '" data-date="' + k + '"' + (off ? ' disabled' : '') + '>' + dt.getDate() + '</button>';
        }
        h += '</div>';

        h += '<div class="icsc-foot"><button type="button" class="icsc-link" data-act="today">Today</button>';
        h += '<span class="icsc-legend"><i></i>Has records</span>';
        h += '<button type="button" class="icsc-link" data-act="clear">Clear</button></div>';
        pop.innerHTML = h;
    }

    function place() {
        if (!pop || !anchor) return;
        var r = anchor.getBoundingClientRect();
        var left = r.left + window.pageXOffset;
        var maxLeft = window.pageXOffset + document.documentElement.clientWidth - pop.offsetWidth - 10;
        pop.style.top = (r.bottom + window.pageYOffset + 6) + 'px';
        pop.style.left = Math.max(10, Math.min(left, maxLeft)) + 'px';
    }

    function close() {
        if (pop && pop.parentNode) pop.parentNode.removeChild(pop);
        pop = null; anchor = null;
    }

    function setValue(v) {
        var inp = anchor;
        close();
        if (!inp) return;
        inp.value = v;
        inp.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function open(inp) {
        injectCss();
        close();
        anchor = inp;
        var base = parse(inp.value) || new Date();
        view.y = base.getFullYear();
        view.m = base.getMonth();
        pop = document.createElement('div');
        pop.className = 'icsc-pop';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Choose a date');
        document.body.appendChild(pop);

        pop.addEventListener('click', function (e) {
            e.stopPropagation();   // render() replaces buttons, so don't let the outside-click closer misfire
            var t = e.target.closest('button');
            if (!t) return;
            if (t.hasAttribute('data-date')) { setValue(t.getAttribute('data-date')); return; }
            var act = t.getAttribute('data-act');
            if (act === 'prev') { view.m--; if (view.m < 0) { view.m = 11; view.y--; } render(); }
            else if (act === 'next') { view.m++; if (view.m > 11) { view.m = 0; view.y++; } render(); }
            else if (act === 'today') {
                var lim = limits(), k = iso(new Date());
                if ((lim.min && k < lim.min) || (lim.max && k > lim.max)) { view.y = new Date().getFullYear(); view.m = new Date().getMonth(); render(); }
                else setValue(k);
            }
            else if (act === 'clear') setValue('');
        });
        pop.addEventListener('change', function (e) {
            var s = e.target.getAttribute('data-sel');
            if (s === 'm') view.m = +e.target.value;
            else if (s === 'y') view.y = +e.target.value;
            else return;
            e.stopPropagation();
            render();
        });

        render();
        place();
    }

    document.addEventListener('click', function (e) {
        var inp = e.target.closest ? e.target.closest('input[data-ics-date]') : null;
        if (inp) { open(inp); return; }
        if (pop && !pop.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { close(); return; }
        var t = e.target;
        if ((e.key === 'Enter' || e.key === ' ') && t && t.matches && t.matches('input[data-ics-date]')) {
            e.preventDefault();
            open(t);
        }
    });
    window.addEventListener('resize', place);

    window.ICSCalendar = { open: open, close: close };
})();