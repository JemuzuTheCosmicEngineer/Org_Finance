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