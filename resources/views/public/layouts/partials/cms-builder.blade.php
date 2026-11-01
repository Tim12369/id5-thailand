{{-- ════════════════════════════════════════════════════════════════
     The page builder's on-page tools — edit mode inside the WordPress
     site editor only (included by cms-edit.blade.php, which is itself
     only output for an editor; the script below also stops at once
     unless the page is shown in the site editor's frame).

     Sections and pages built from blocks carry data-id5-builder in edit
     mode. The site editor draws their blocks into them (every block's
     element tagged data-id5-b) and this script adds what an editor works
     with on the page:

       • toolbars for the section, each row ("+ ≡ −" and its menu), each
         column and each component, plus outlines on hover;
       • typing straight into text, with a small formatting bar;
       • dragging components and rows to another place, dropping things
         dragged from the panel, and dragging between columns to resize.

     Everything is reported to the site editor, which changes the blocks
     and draws them again. Nothing here stores anything.
     ════════════════════════════════════════════════════════════════ --}}
<style>
    html.id5-building [data-id5-root] { position: relative; min-height: 48px; }
    html.id5-building [data-id5-root] :is(iframe, object) { pointer-events: none; }
    html.id5-building [data-id5-root] a { cursor: default; }
    .id5b-hover-root { outline: 2px dashed #1d2327 !important; outline-offset: 12px; }
    .id5b-hover-row { outline: 2px solid #2271b1 !important; outline-offset: 5px; }
    .id5b-hover-col { outline: 1px dashed #d97706 !important; outline-offset: -1px; }
    .id5b-hover-comp { outline: 2px solid #7c3aed !important; outline-offset: 2px; }
    .id5b-sel { outline: 2px solid #f97316 !important; outline-offset: 3px; }
    .id5b-sel-root { outline: 2px solid #f97316 !important; outline-offset: 12px; }
    .id5b-editing { outline: 2px solid #16a34a !important; outline-offset: 3px; cursor: text !important; }
    .id5b-ui, .id5b-ui * { box-sizing: border-box; font-family: -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif; }
    .id5b-bar { position: fixed; z-index: 2147482500; display: none; align-items: stretch; height: 26px; border-radius: 3px; overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,.28); color: #fff; font-size: 11px; font-weight: 600; line-height: 26px; white-space: nowrap; }
    .id5b-bar button { display: flex; align-items: center; justify-content: center; gap: 3px; min-width: 26px; height: 26px; margin: 0; padding: 0 6px;
        border: 0; background: transparent; color: inherit; cursor: pointer; font: inherit; }
    .id5b-bar button:hover { background: rgba(255,255,255,.2); }
    .id5b-bar button i { font-size: 15px; }
    .id5b-bar .id5b-label { padding: 0 8px; }
    .id5b-bar .id5b-drag { cursor: grab; }
    .id5b-bar--sec { background: #1d2327; }
    .id5b-bar--row { background: #2271b1; }
    .id5b-bar--col { background: #f59e0b; color: #1d2327; }
    .id5b-bar--comp { background: #7c3aed; }
    .id5b-menu { position: fixed; z-index: 2147482600; display: none; min-width: 200px; padding: 4px 0; border: 1px solid #dcdcde; border-radius: 4px;
        background: #fff; box-shadow: 0 6px 18px rgba(0,0,0,.18); font-size: 13px; }
    .id5b-menu button { display: flex; align-items: center; gap: 8px; width: 100%; margin: 0; padding: 7px 14px; border: 0; background: none; color: #1d2327;
        cursor: pointer; font: inherit; text-align: left; }
    .id5b-menu button:hover { background: #f0f6fc; color: #2271b1; }
    .id5b-menu button:disabled { opacity: .4; cursor: default; background: none; color: #1d2327; }
    .id5b-menu button.is-danger { color: #d63638; }
    .id5b-menu hr { margin: 4px 0; border: 0; border-top: 1px solid #f0f0f1; }
    .id5b-menu i { font-size: 16px; width: 16px; }
    .id5b-empty { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; min-height: 76px; margin: 0; padding: 10px;
        border: 2px dashed #9ec2e6; border-radius: 10px; background: rgba(240,246,252,.75); color: #2271b1; cursor: pointer; font-size: 12px; font-weight: 600; }
    .id5b-empty:hover { border-color: #2271b1; background: #f0f6fc; }
    .id5b-root-empty { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px; padding: 28px 16px; border: 2px dashed #9ec2e6;
        border-radius: 12px; background: rgba(240,246,252,.75); color: #50575e; font-size: 13px; }
    .id5b-addrow { display: flex; justify-content: center; gap: 8px; margin: 14px 0 0; }
    .id5b-pill { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border: 1px dashed #2271b1; border-radius: 999px; background: #fff;
        color: #2271b1; cursor: pointer; font-size: 12px; font-weight: 600; }
    .id5b-pill:hover { border-style: solid; background: #f0f6fc; }
    .id5b-drop { position: fixed; z-index: 2147482700; display: none; height: 4px; border-radius: 2px; background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.25); pointer-events: none; }
    .id5b-drop.is-vertical { width: 4px; }
    .id5b-drop-into { outline: 2px dashed #16a34a !important; outline-offset: -2px; }
    .id5b-ghost { position: fixed; z-index: 2147482800; display: none; padding: 4px 10px; border-radius: 3px; background: #7c3aed; color: #fff; font-size: 12px; font-weight: 600;
        pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,.25); }
    .id5b-handle { position: fixed; z-index: 2147482400; display: none; width: 14px; margin-left: -7px; cursor: col-resize; }
    .id5b-handle::after { content: ''; position: absolute; top: 0; bottom: 0; left: 6px; width: 2px; background: #2271b1; opacity: .45; }
    .id5b-handle:hover::after, .id5b-handle.is-active::after { opacity: 1; }
    .id5b-tip { position: fixed; z-index: 2147482800; display: none; padding: 3px 8px; border-radius: 3px; background: #1d2327; color: #fff; font-size: 11px; pointer-events: none; }
    .id5b-textbar { position: fixed; z-index: 2147482900; display: none; height: 30px; border-radius: 4px; overflow: hidden; background: #1d2327; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
    .id5b-textbar button { width: 32px; height: 30px; margin: 0; padding: 0; border: 0; background: none; color: #fff; cursor: pointer; font-size: 16px; }
    .id5b-textbar button:hover { background: #2c3338; color: #72aee6; }
    html.id5b-dragging, html.id5b-dragging * { cursor: grabbing !important; user-select: none !important; }
    html.id5b-resizing, html.id5b-resizing * { cursor: col-resize !important; user-select: none !important; }
</style>

<script>
(function () {
    'use strict';

    if (window.parent === window) { return; }

    var ORIGIN = {!! json_encode($cmsOrigin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!};
    var doc = document.documentElement;
    var roots = {};
    var meta = {};
    var selected = null;
    var selectedRoot = null;
    var hover = { root: null, row: null, col: null, comp: null };
    var frozen = false;   // a menu is open: keep the toolbars where they are
    var editing = null;   // { el, id, a, sec, r, c }
    var drag = null;      // moving a block: { id, el, row }
    var tile = null;      // something dragged in from the panel: { row }
    var resizing = null;

    function tell(message) {
        message.source = 'id5-cms';
        window.parent.postMessage(message, ORIGIN);
    }

    function make(tag, cls, html) {
        var el = document.createElement(tag);
        if (cls) { el.className = cls; }
        if (html) { el.innerHTML = html; }
        return el;
    }

    function ti(name) { return '<i class="ti ti-' + name + '" aria-hidden="true"></i>'; }

    // ── The sections on this page ────────────────────────────────────────
    var sections = [];

    document.querySelectorAll('[data-id5-builder]').forEach(function (el) {
        var m = /^(id5_section|id5_page):(\d+)$/.exec(el.getAttribute('data-id5-builder') || '');
        if (!m) { return; }
        var id = Number(m[2]);
        roots[id] = el;
        el.setAttribute('data-id5-root', id);
        sections.push({ type: m[1], id: id, title: el.getAttribute('data-id5-title') || '' });
    });

    // Where a new section can be added: the "+ เพิ่มส่วนเนื้อหา" tiles.
    var slots = [];
    document.querySelectorAll('[data-cms-add^="new:id5_section?"]').forEach(function (el) {
        var query = new URLSearchParams(el.getAttribute('data-cms-add').split('?')[1] || '');
        slots.push({ page: query.get('id5_page') || '/', position: query.get('id5_position') || 'bottom', label: el.getAttribute('data-cms-title') || '' });
    });

    if (sections.length || slots.length) { doc.classList.add('id5-building'); }

    // ── Floating tools ───────────────────────────────────────────────────
    function bar(kind, buttons) {
        var el = make('div', 'id5b-ui id5b-bar id5b-bar--' + kind);
        buttons.forEach(function (b) {
            var button = make('button', b.cls || '', (b.icon ? ti(b.icon) : '') + (b.text !== undefined ? '<span>' + b.text + '</span>' : ''));
            button.type = 'button';
            button.title = b.title || '';
            button.setAttribute('data-do', b.act);
            el.appendChild(button);
        });
        document.body.appendChild(el);
        return el;
    }

    var secBar = bar('sec', [
        { act: 'section', icon: 'layout-rows', text: 'ส่วนเนื้อหา', cls: 'id5b-label', title: 'เลือกส่วนนี้' },
        { act: 'add-row', icon: 'row-insert-bottom', title: 'เพิ่มแถว' },
        { act: 'section-up', icon: 'arrow-up', title: 'เลื่อนส่วนนี้ขึ้น' },
        { act: 'section-down', icon: 'arrow-down', title: 'เลื่อนส่วนนี้ลง' },
        { act: 'section-settings', icon: 'settings', title: 'ชื่อ ภาษา และตำแหน่งในหน้า' },
        { act: 'section-delete', icon: 'trash', title: 'ลบส่วนนี้ทั้งส่วน' }
    ]);
    var rowBar = bar('row', [
        { act: 'drag', icon: 'grip-vertical', cls: 'id5b-drag', title: 'ลากเพื่อย้ายแถว' },
        { act: 'select', text: 'แถว', cls: 'id5b-label', title: 'เลือกแถวนี้' },
        { act: 'add-column', icon: 'plus', title: 'เพิ่มคอลัมน์' },
        { act: 'menu-row', icon: 'menu-2', title: 'ตัวเลือกของแถว' },
        { act: 'remove-column', icon: 'minus', title: 'ลดคอลัมน์ (ลบคอลัมน์สุดท้าย)' }
    ]);
    var colBar = bar('col', [
        { act: 'select', text: 'คอลัมน์', cls: 'id5b-label', title: 'เลือกคอลัมน์นี้' },
        { act: 'add-into', icon: 'plus', title: 'เพิ่มเนื้อหาในคอลัมน์นี้' },
        { act: 'menu-col', icon: 'menu-2', title: 'ตัวเลือกของคอลัมน์' }
    ]);
    var compBar = bar('comp', [
        { act: 'drag', icon: 'grip-vertical', cls: 'id5b-drag', title: 'ลากเพื่อย้าย' },
        { act: 'select', text: '', cls: 'id5b-label', title: 'เลือกและปรับแต่ง' },
        { act: 'settings', icon: 'adjustments-horizontal', title: 'ปรับแต่ง' },
        { act: 'add-after', icon: 'plus', title: 'เพิ่มเนื้อหาต่อจากนี้' },
        { act: 'duplicate', icon: 'copy', title: 'ทำสำเนา' },
        { act: 'up', icon: 'arrow-up', title: 'เลื่อนขึ้น' },
        { act: 'down', icon: 'arrow-down', title: 'เลื่อนลง' },
        { act: 'delete', icon: 'trash', title: 'ลบ' }
    ]);

    var MENUS = {
        'menu-row': [
            ['select', 'pointer', 'เลือก'], ['add-column', 'column-insert-right', 'เพิ่มคอลัมน์'], ['edit-row', 'adjustments-horizontal', 'แก้ไขแถว'],
            ['duplicate', 'copy', 'ทำสำเนา'], '-', ['row-above', 'row-insert-top', 'เพิ่มแถวด้านบน'], ['row-below', 'row-insert-bottom', 'เพิ่มแถวด้านล่าง'], '-',
            ['copy-style', 'brush', 'คัดลอกสไตล์'], ['paste-style', 'clipboard', 'วางสไตล์'], '-', ['delete', 'trash', 'ลบ', 'is-danger']
        ],
        'menu-col': [
            ['select', 'pointer', 'เลือก'], ['add-into', 'plus', 'เพิ่มเนื้อหา'], ['duplicate', 'copy', 'ทำสำเนาคอลัมน์'], '-',
            ['col-left', 'arrow-left', 'ย้ายไปซ้าย'], ['col-right', 'arrow-right', 'ย้ายไปขวา'], '-',
            ['copy-style', 'brush', 'คัดลอกสไตล์'], ['paste-style', 'clipboard', 'วางสไตล์'], '-', ['delete', 'trash', 'ลบคอลัมน์', 'is-danger']
        ]
    };

    var menu = make('div', 'id5b-ui id5b-menu');
    document.body.appendChild(menu);
    var menuFor = null;

    var drop = make('div', 'id5b-ui id5b-drop');
    var ghost = make('div', 'id5b-ui id5b-ghost');
    var tip = make('div', 'id5b-ui id5b-tip');
    var textBar = make('div', 'id5b-ui id5b-textbar');
    [['bold', 'bold', 'ตัวหนา (Ctrl+B)'], ['italic', 'italic', 'ตัวเอียง (Ctrl+I)'], ['link', 'link', 'ใส่ลิงก์'], ['unlink', 'unlink', 'เอาลิงก์ออก']].forEach(function (b) {
        var button = make('button', '', ti(b[1]));
        button.type = 'button';
        button.title = b[2];
        button.setAttribute('data-format', b[0]);
        textBar.appendChild(button);
    });
    [drop, ghost, tip, textBar].forEach(function (el) { document.body.appendChild(el); });
    var handles = [];

    // ── Reading the drawn blocks ─────────────────────────────────────────
    function blockEl(node) {
        while (node && node.nodeType !== 1) { node = node.parentNode; }
        return node && node.closest ? node.closest('[data-id5-b]') : null;
    }

    function idOf(el) { return el ? el.getAttribute('data-id5-b') : null; }
    function info(el) { return (el && meta[idOf(el)]) || {}; }
    function rootOf(el) { return el && el.closest ? el.closest('[data-id5-root]') : null; }
    function rootId(el) { var r = rootOf(el); return r ? Number(r.getAttribute('data-id5-root')) : null; }
    function up(el) { return el ? blockEl(el.parentElement) : null; }

    function nearest(el, kind) {
        while (el) {
            var k = info(el).k;
            if (k === kind) { return el; }
            if (kind === 'comp' && (k === 'row' || k === 'column')) { return null; }
            el = up(el);
        }
        return null;
    }

    function kids(container) {
        var box = container.querySelector(':scope > .wp-block-group__inner-container') || container;
        return Array.prototype.filter.call(box.children, function (c) { return c.hasAttribute('data-id5-b'); });
    }

    function byId(id) { return id ? document.querySelector('[data-id5-b="' + id + '"]') : null; }

    // ── Drawing what the site editor sends ───────────────────────────────
    function decorate(root) {
        root.querySelectorAll('[data-id5-b]').forEach(function (el) {
            var m = info(el);
            if (m.n === 'core/details') { el.open = true; }
            if (m.c && !kids(el).length) {
                var empty = make('button', 'id5b-ui id5b-empty', ti('plus') + 'เพิ่มเนื้อหา');
                empty.type = 'button';
                empty.setAttribute('data-id5b-act', 'add-into');
                empty.setAttribute('data-id5b-id', idOf(el));
                var box = el.querySelector(':scope > .wp-block-group__inner-container') || el;
                box.appendChild(empty);
            }
        });

        var id = root.getAttribute('data-id5-root');

        if (!kids(root).length) {
            var none = make('div', 'id5b-ui id5b-root-empty', '<span>ส่วนนี้ยังว่าง</span>');
            none.appendChild(pill('add-row', 'row-insert-bottom', 'เพิ่มแถว', id));
            none.appendChild(pill('add-content', 'plus', 'เพิ่มเนื้อหา', id));
            root.appendChild(none);
        } else {
            var more = make('div', 'id5b-ui id5b-addrow');
            more.appendChild(pill('add-row', 'row-insert-bottom', 'เพิ่มแถว', id));
            more.appendChild(pill('add-content', 'plus', 'เพิ่มเนื้อหา', id));
            root.appendChild(more);
        }
    }

    function pill(act, icon, text, id) {
        var b = make('button', 'id5b-pill', ti(icon) + text);
        b.type = 'button';
        b.setAttribute('data-id5b-act', act);
        b.setAttribute('data-id5b-root', id);
        return b;
    }

    function render(data) {
        var root = roots[data.id];
        if (!root) { return; }

        if (editing && root.contains(editing.el)) { stopEdit(true); }

        Object.keys(meta).forEach(function (k) { if (meta[k].root === data.id) { delete meta[k]; } });
        Object.keys(data.meta || {}).forEach(function (k) { meta[k] = data.meta[k]; meta[k].root = data.id; });

        root.innerHTML = data.html || '';
        if (data.title) { root.setAttribute('data-id5-title', data.title); }
        decorate(root);
        if (window.ID5Blocks) { window.ID5Blocks.enhance(root, { editing: true }); }

        var section = root.closest('.cms-section');
        if (section && data.bg) { section.className = section.className.replace(/\bcms-section--\S+/, 'cms-section--' + data.bg); }

        if (data.sel !== undefined) { selected = data.sel; }
        paintSelection();
        hover = { root: null, row: null, col: null, comp: null };
        place();
    }

    function paintSelection() {
        document.querySelectorAll('.id5b-sel, .id5b-sel-root').forEach(function (el) { el.classList.remove('id5b-sel', 'id5b-sel-root'); });
        var el = byId(selected);
        if (el) { el.classList.add('id5b-sel'); }
        else if (selectedRoot && roots[selectedRoot]) { roots[selectedRoot].classList.add('id5b-sel-root'); }
    }

    // ── Hover: outlines and toolbars ─────────────────────────────────────
    function setHover(next) {
        ['root', 'row', 'col', 'comp'].forEach(function (key) {
            var cls = 'id5b-hover-' + key;
            if (hover[key] !== next[key]) {
                if (hover[key]) { hover[key].classList.remove(cls); }
                if (next[key]) { next[key].classList.add(cls); }
            }
        });
        hover = next;
        place();
    }

    function at(x, y, rect, el, width) {
        el.style.display = 'flex';
        var w = width || el.offsetWidth;
        el.style.left = Math.max(2, Math.min(x, window.innerWidth - w - 2)) + 'px';
        el.style.top = Math.max(2, Math.min(y, window.innerHeight - 28)) + 'px';
    }

    function place() {
        [secBar, rowBar, colBar, compBar].forEach(function (b) { b.style.display = 'none'; });
        handles.forEach(function (h) { h.style.display = 'none'; });
        if (drag || tile || resizing) { return; }

        var r;
        if (hover.root) {
            r = hover.root.getBoundingClientRect();
            var label = secBar.querySelector('.id5b-label span');
            label.textContent = (hover.root.getAttribute('data-id5-title') || 'ส่วนเนื้อหา').slice(0, 40);
            // A page built from blocks is moved and deleted in its own form,
            // not from inside itself.
            var page = /^id5_page:/.test(hover.root.getAttribute('data-id5-builder') || '');
            secBar.querySelectorAll('[data-do^="section-up"], [data-do^="section-down"], [data-do="section-delete"]').forEach(function (b) { b.style.display = page ? 'none' : ''; });
            secBar.style.display = 'flex';
            at(r.right - secBar.offsetWidth + 10, r.top - 40, r, secBar);
        }
        var rowRect = null;
        if (hover.row) {
            r = hover.row.getBoundingClientRect();
            at(r.left - 5, r.top - 33 >= 2 ? r.top - 33 : r.top + 2, r, rowBar);
            rowRect = rowBar.getBoundingClientRect();
        }
        if (hover.comp) {
            r = hover.comp.getBoundingClientRect();
            compBar.querySelector('.id5b-label span').textContent = info(hover.comp).l || 'เนื้อหา';
            at(r.left - 2, r.top - 30 >= 2 ? r.top - 30 : r.bottom + 4, r, compBar);
            // The first thing in a row would sit under the row's toolbar:
            // put its own toolbar alongside instead.
            var cr = compBar.getBoundingClientRect();
            if (rowRect && cr.left < rowRect.right && cr.right > rowRect.left && cr.top < rowRect.bottom && cr.bottom > rowRect.top) {
                at(rowRect.right + 6, rowRect.top, r, compBar);
            }
        }
        if (hover.col) {
            r = hover.col.getBoundingClientRect();
            colBar.style.display = 'flex';
            at(r.right - colBar.offsetWidth - 1, r.top + 1, r, colBar);
        }
        if (hover.row) { placeHandles(hover.row); }
    }

    // Between side-by-side columns: a line to drag for a different width.
    function placeHandles(row) {
        var cols = kids(row);
        if (cols.length < 2) { return; }
        var rects = cols.map(function (c) { return c.getBoundingClientRect(); });
        if (rects.some(function (rc) { return Math.abs(rc.top - rects[0].top) > 4; })) { return; } // stacked (phone width)
        var rr = row.getBoundingClientRect();

        for (var i = 0; i < cols.length - 1; i++) {
            var h = handles[i];
            if (!h) {
                h = make('div', 'id5b-ui id5b-handle');
                h.title = 'ลากเพื่อปรับความกว้างคอลัมน์';
                h.setAttribute('data-i', i);
                h.addEventListener('mousedown', startResize);
                document.body.appendChild(h);
                handles.push(h);
            }
            h.style.display = 'block';
            h.style.left = ((rects[i].right + rects[i + 1].left) / 2) + 'px';
            h.style.top = rr.top + 'px';
            h.style.height = rr.height + 'px';
            h._row = row;
        }
    }

    function hoverFrom(node) {
        var b = blockEl(node);
        var root = rootOf(node);
        if (!root) { return { root: null, row: null, col: null, comp: null }; }
        return { root: root, row: nearest(b, 'row'), col: nearest(b, 'column'), comp: nearest(b, 'comp') };
    }

    var hideTimer = null;
    var pending = null;

    document.addEventListener('mousemove', function (event) {
        if (frozen || drag || resizing) { return; }
        var t = event.target;
        if (t.closest && t.closest('.id5b-bar, .id5b-handle, .id5b-menu, .id5b-textbar')) { clearTimeout(hideTimer); return; }
        pending = t;
        window.requestAnimationFrame(function () {
            if (!pending) { return; }
            var next = hoverFrom(pending);
            pending = null;
            if (!next.root) {
                clearTimeout(hideTimer);
                hideTimer = setTimeout(function () { if (!frozen) { setHover({ root: null, row: null, col: null, comp: null }); } }, 350);
                return;
            }
            clearTimeout(hideTimer);
            if (next.root !== hover.root || next.row !== hover.row || next.col !== hover.col || next.comp !== hover.comp) { setHover(next); }
        });
    }, true);

    window.addEventListener('scroll', function () { place(); if (editing) { placeTextBar(); } closeMenu(); }, { passive: true });
    window.addEventListener('resize', place);

    // ── Toolbar buttons and menus ────────────────────────────────────────
    function targetOfBar(barEl) {
        if (barEl === rowBar) { return hover.row; }
        if (barEl === colBar) { return hover.col; }
        if (barEl === compBar) { return hover.comp; }
        return null;
    }

    function act(name, el, extra) {
        var message = { type: 'builder-action', act: name, id: el ? idOf(el) : null, root: el ? rootId(el) : null };
        Object.keys(extra || {}).forEach(function (k) { message[k] = extra[k]; });
        tell(message);
    }

    [secBar, rowBar, colBar, compBar].forEach(function (barEl) {
        barEl.addEventListener('mousedown', function (event) {
            var button = event.target.closest('button');
            if (button && button.getAttribute('data-do') === 'drag') {
                event.preventDefault();
                var el = targetOfBar(barEl);
                if (el) { startDrag(el, event); }
            }
        });

        barEl.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) { return; }
            event.preventDefault();
            event.stopPropagation();
            var name = button.getAttribute('data-do');
            if (name === 'drag') { return; }

            if (barEl === secBar) {
                var root = hover.root;
                if (root) { tell({ type: 'builder-action', act: name, root: Number(root.getAttribute('data-id5-root')) }); }
                return;
            }

            var el = targetOfBar(barEl);
            if (!el) { return; }
            if (MENUS[name]) { openMenu(name, el, button); return; }
            if (name === 'add-column' || name === 'remove-column') { act(name, el); return; }
            act(name, el);
        });
    });

    function openMenu(name, el, button) {
        menu.textContent = '';
        menuFor = el;
        MENUS[name].forEach(function (item) {
            if (item === '-') { menu.appendChild(make('hr')); return; }
            var b = make('button', item[3] || '', ti(item[1]) + '<span>' + item[2] + '</span>');
            b.type = 'button';
            b.setAttribute('data-do', item[0]);
            if (name === 'menu-col' && (item[0] === 'col-left' || item[0] === 'col-right')) {
                var cols = kids(up(el) || el);
                var i = cols.indexOf(el);
                b.disabled = (item[0] === 'col-left' && i === 0) || (item[0] === 'col-right' && i === cols.length - 1);
            }
            menu.appendChild(b);
        });
        var r = button.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.left = Math.max(4, Math.min(r.left, window.innerWidth - menu.offsetWidth - 4)) + 'px';
        menu.style.top = (r.bottom + menu.offsetHeight + 4 > window.innerHeight ? Math.max(4, r.top - menu.offsetHeight - 4) : r.bottom + 4) + 'px';
        frozen = true;
    }

    function closeMenu() {
        menu.style.display = 'none';
        menuFor = null;
        frozen = false;
    }

    menu.addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button || button.disabled) { return; }
        event.preventDefault();
        event.stopPropagation();
        var el = menuFor;
        closeMenu();
        if (el) { act(button.getAttribute('data-do'), el); }
    });

    document.addEventListener('mousedown', function (event) {
        if (menu.style.display === 'block' && !menu.contains(event.target) && !event.target.closest('.id5b-bar')) { closeMenu(); }
    }, true);

    // ── Clicking on the page: select, and type into text ─────────────────
    function editableAt(node) {
        var b = blockEl(node);
        if (!b) { return null; }
        var found = null;
        (info(b).e || []).some(function (d) {
            var el = d.s ? (node.closest ? node.closest(d.s) : null) : b;
            if (!el || !b.contains(el) || (d.s && blockEl(el) !== b)) { return false; }
            found = { el: el, id: idOf(b), a: d.a };
            if (d.a === '@cell') {
                var part = el.parentElement && el.parentElement.parentElement;
                found.sec = part && part.tagName === 'THEAD' ? 'head' : part && part.tagName === 'TFOOT' ? 'foot' : 'body';
                found.r = Array.prototype.indexOf.call(part.children, el.parentElement);
                found.c = Array.prototype.indexOf.call(el.parentElement.children, el);
            }
            return true;
        });
        return found;
    }

    document.addEventListener('mousedown', function (event) {
        var t = event.target;
        if (!rootOf(t) || (t.closest && t.closest('.id5b-ui'))) { return; }
        var spot = editableAt(t);
        if (spot && (!editing || editing.el !== spot.el)) {
            // Editable before the click lands, so the caret goes where clicked.
            startEdit(spot, false);
        }
    }, true);

    document.addEventListener('click', function (event) {
        var t = event.target;
        if (!t.closest) { return; }

        var ui = t.closest('[data-id5b-act]');
        if (ui) {
            event.preventDefault();
            event.stopPropagation();
            var into = byId(ui.getAttribute('data-id5b-id'));
            if (into) { act(ui.getAttribute('data-id5b-act'), into); }
            else { tell({ type: 'builder-action', act: ui.getAttribute('data-id5b-act'), root: Number(ui.getAttribute('data-id5b-root')) }); }
            return;
        }
        if (t.closest('.id5b-ui')) { return; }

        var root = rootOf(t);
        if (!root) { return; }

        // Tabs and slider arrows keep working, so every part can be reached.
        if (t.closest('.id5-tabs__nav, .id5-slides__nav')) { return; }

        event.preventDefault();
        event.stopPropagation();
        if (editing && editing.el.contains(t) && editing.reported) { return; }

        var b = blockEl(t);
        var pick = nearest(b, 'comp') || nearest(b, 'column') || nearest(b, 'row');
        if (pick) {
            selected = idOf(pick);
            selectedRoot = null;
            tell({ type: 'builder-select', id: selected });
        } else {
            selected = null;
            selectedRoot = rootId(root);
            tell({ type: 'builder-select', section: selectedRoot });
        }
        if (editing) { editing.reported = true; }
        paintSelection();
    }, true);

    // ── Typing ───────────────────────────────────────────────────────────
    function startEdit(spot, selectAll) {
        stopEdit(false);
        editing = spot;
        var el = spot.el;
        el.setAttribute('contenteditable', 'true');
        el.classList.add('id5b-editing');
        el.addEventListener('input', onInput);
        el.addEventListener('blur', onBlur);
        el.addEventListener('keydown', onKey);
        el.addEventListener('paste', onPaste);
        if (selectAll) {
            el.focus();
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
        placeTextBar();
    }

    function stopEdit(silent) {
        if (!editing) { return; }
        var el = editing.el;
        el.removeAttribute('contenteditable');
        el.classList.remove('id5b-editing');
        el.removeEventListener('input', onInput);
        el.removeEventListener('blur', onBlur);
        el.removeEventListener('keydown', onKey);
        el.removeEventListener('paste', onPaste);
        textBar.style.display = 'none';
        var was = editing;
        editing = null;
        // A tab's title is also its button.
        var tabs = el.classList.contains('id5-tab__title') ? el.closest('.id5-tabs') : null;
        if (tabs) {
            var panels = Array.prototype.filter.call(tabs.querySelectorAll('.id5-tab'), function (p) { return p.closest('.id5-tabs') === tabs; });
            var button = tabs.querySelectorAll('.id5-tabs__button')[panels.indexOf(el.closest('.id5-tab'))];
            if (button) { button.textContent = el.textContent.trim(); }
        }
        if (!silent && was.changed) { tell({ type: 'builder-text-end', id: was.id }); }
    }

    function report() {
        if (!editing) { return; }
        editing.changed = true;
        tell({ type: editing.a === '@cell' ? 'builder-cell' : 'builder-text', id: editing.id, a: editing.a, html: editing.el.innerHTML, sec: editing.sec, r: editing.r, c: editing.c });
    }

    function onInput() { report(); }
    function onBlur() { setTimeout(function () { if (editing && document.activeElement !== editing.el) { stopEdit(false); } }, 150); }

    function onKey(event) {
        if (event.key === 'Escape') { event.preventDefault(); editing.el.blur(); stopEdit(false); return; }
        if (event.key !== 'Enter' || event.shiftKey) { return; }

        var name = info(byId(editing.id)).n;
        event.preventDefault();
        if (name === 'core/list-item') {
            var id = editing.id;
            stopEdit(false);
            tell({ type: 'builder-action', act: 'li-enter', id: id });
        } else if (name === 'core/paragraph' || name === 'core/quote') {
            document.execCommand('insertLineBreak');
        } else {
            editing.el.blur();
            stopEdit(false);
        }
    }

    function onPaste(event) {
        event.preventDefault();
        var text = (event.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
    }

    function placeTextBar() {
        if (!editing) { return; }
        var r = editing.el.getBoundingClientRect();
        textBar.style.display = 'flex';
        textBar.style.left = Math.max(4, Math.min(r.left, window.innerWidth - textBar.offsetWidth - 4)) + 'px';
        textBar.style.top = (r.top - 38 >= 2 ? r.top - 38 : r.bottom + 6) + 'px';
    }

    textBar.addEventListener('mousedown', function (event) {
        event.preventDefault(); // keep the caret in the text
        var button = event.target.closest('button');
        if (!button || !editing) { return; }
        var what = button.getAttribute('data-format');
        if (what === 'link') {
            var url = window.prompt('ลิงก์ไปที่ (เช่น https://… หรือ /contact)', 'https://');
            if (url && !/^\s*javascript:/i.test(url)) { document.execCommand('createLink', false, url.trim()); }
        } else {
            document.execCommand(what);
        }
        report();
    });

    // ── Where something would land ───────────────────────────────────────
    function containerFor(node, root, moving) {
        var el = blockEl(node);
        while (el && root.contains(el)) {
            if (info(el).c && !(moving && (moving === el || moving.contains(el)))) { return el; }
            el = up(el);
        }
        return null;
    }

    function dropAt(x, y, moving) {
        var node = document.elementFromPoint(x, y);
        if (!node) { return null; }
        var root = rootOf(node);
        if (!root) { return null; }

        var container = containerFor(node, root, moving);
        var box = container || root;
        var list = kids(box);
        var sideways = container && /\bid5-(slider|carousel)\b/.test(container.className);
        var index = list.length;

        for (var i = 0; i < list.length; i++) {
            var r = list[i].getBoundingClientRect();
            if (sideways ? x < r.left + r.width / 2 : y < r.top + r.height / 2) { index = i; break; }
        }

        var line;
        var br = box.getBoundingClientRect();
        if (!list.length) {
            line = { left: br.left + 8, top: br.top + 8, width: br.width - 16, into: box };
        } else if (sideways) {
            var ref = list[Math.min(index, list.length - 1)].getBoundingClientRect();
            line = { left: index < list.length ? ref.left - 3 : ref.right + 1, top: ref.top, height: ref.height, vertical: true };
        } else {
            var rr = index < list.length ? list[index].getBoundingClientRect() : list[list.length - 1].getBoundingClientRect();
            line = { left: br.left, top: index < list.length ? rr.top - 4 : rr.bottom + 2, width: br.width };
        }

        return { root: Number(root.getAttribute('data-id5-root')), into: container ? idOf(container) : null, index: index, line: line };
    }

    var intoEl = null;

    function showDrop(spot) {
        if (intoEl) { intoEl.classList.remove('id5b-drop-into'); intoEl = null; }
        if (!spot) { drop.style.display = 'none'; return; }
        var l = spot.line;
        drop.classList.toggle('is-vertical', !!l.vertical);
        drop.style.display = 'block';
        drop.style.left = l.left + 'px';
        drop.style.top = l.top + 'px';
        drop.style.width = l.vertical ? '' : Math.max(20, l.width) + 'px';
        drop.style.height = l.vertical ? l.height + 'px' : '';
        if (l.into && l.into !== roots[spot.root]) { intoEl = l.into; intoEl.classList.add('id5b-drop-into'); }
    }

    // Moving a block with its grip.
    function startDrag(el, event) {
        drag = { id: idOf(el), el: el };
        doc.classList.add('id5b-dragging');
        ghost.textContent = 'ย้าย' + (info(el).l || '');
        ghost.style.display = 'block';
        place();
        moveDrag(event);
        document.addEventListener('mousemove', moveDrag, true);
        document.addEventListener('mouseup', endDrag, true);
    }

    function moveDrag(event) {
        ghost.style.left = (event.clientX + 12) + 'px';
        ghost.style.top = (event.clientY + 12) + 'px';
        drag.spot = dropAt(event.clientX, event.clientY, drag.el);
        showDrop(drag.spot);
        // Near the edge of the frame: scroll.
        if (event.clientY < 40) { window.scrollBy(0, -18); } else if (event.clientY > window.innerHeight - 40) { window.scrollBy(0, 18); }
    }

    function endDrag() {
        document.removeEventListener('mousemove', moveDrag, true);
        document.removeEventListener('mouseup', endDrag, true);
        var d = drag;
        drag = null;
        doc.classList.remove('id5b-dragging');
        ghost.style.display = 'none';
        showDrop(null);
        if (d && d.spot) { tell({ type: 'builder-move', id: d.id, root: d.spot.root, into: d.spot.into, index: d.spot.index }); }
    }

    // Something dragged in from the panel.
    document.addEventListener('dragover', function (event) {
        if (!tile) { return; }
        var spot = dropAt(event.clientX, event.clientY, null);
        if (!spot) { showDrop(null); return; }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        tile.spot = spot;
        showDrop(spot);
    });

    document.addEventListener('drop', function (event) {
        if (!tile) { return; }
        event.preventDefault();
        var spot = dropAt(event.clientX, event.clientY, null) || tile.spot;
        endTile();
        if (spot) { tell({ type: 'builder-drop-tile', root: spot.root, into: spot.into, index: spot.index }); }
    });

    function endTile() {
        tile = null;
        showDrop(null);
        place();
    }

    // Dragging between two columns: their widths.
    function startResize(event) {
        event.preventDefault();
        var h = event.currentTarget;
        var row = h._row;
        var cols = kids(row);
        var widths = cols.map(function (c) { return c.getBoundingClientRect().width; });
        var total = widths.reduce(function (a, b) { return a + b; }, 0);
        resizing = { row: row, cols: cols, i: Number(h.getAttribute('data-i')), x: event.clientX, start: widths, now: widths.slice(), total: total, handle: h };
        h.classList.add('is-active');
        doc.classList.add('id5b-resizing');
        document.addEventListener('mousemove', moveResize, true);
        document.addEventListener('mouseup', endResize, true);
    }

    function moveResize(event) {
        var s = resizing;
        var i = s.i;
        var pair = s.start[i] + s.start[i + 1];
        var min = s.total * 0.08;
        var left = Math.max(min, Math.min(pair - min, s.start[i] + event.clientX - s.x));
        s.now[i] = left;
        s.now[i + 1] = pair - left;
        s.cols.forEach(function (c, j) {
            c.style.flexBasis = (s.now[j] / s.total * 100) + '%';
            c.style.flexGrow = '0';
        });
        s.handle.style.left = event.clientX + 'px';
        tip.textContent = s.now.map(function (w) { return Math.round(w / s.total * 100) + '%'; }).join('  |  ');
        tip.style.display = 'block';
        tip.style.left = (event.clientX + 10) + 'px';
        tip.style.top = (event.clientY - 28) + 'px';
    }

    function endResize() {
        document.removeEventListener('mousemove', moveResize, true);
        document.removeEventListener('mouseup', endResize, true);
        var s = resizing;
        resizing = null;
        s.handle.classList.remove('is-active');
        doc.classList.remove('id5b-resizing');
        tip.style.display = 'none';
        if (s.now.some(function (w, j) { return Math.abs(w - s.start[j]) > 2; })) {
            tell({ type: 'builder-widths', id: idOf(s.row), widths: s.now.map(function (w) { return w / s.total * 100; }) });
        } else {
            place();
        }
    }

    // ── Keys ─────────────────────────────────────────────────────────────
    document.addEventListener('keydown', function (event) {
        if (editing || /^(INPUT|TEXTAREA|SELECT)$/.test(event.target.tagName || '') || event.target.isContentEditable) { return; }
        var key = (event.key || '').toLowerCase();
        if ((event.ctrlKey || event.metaKey) && key === 'z') { event.preventDefault(); tell({ type: 'builder-key', key: event.shiftKey ? 'redo' : 'undo' }); }
        else if ((event.ctrlKey || event.metaKey) && key === 'y') { event.preventDefault(); tell({ type: 'builder-key', key: 'redo' }); }
        else if ((key === 'delete' || key === 'backspace') && selected) { event.preventDefault(); tell({ type: 'builder-key', key: 'delete' }); }
        else if (key === 'escape') { tell({ type: 'builder-key', key: 'escape' }); }
    });

    // ── Messages from the site editor ────────────────────────────────────
    window.addEventListener('message', function (event) {
        if (event.origin !== ORIGIN || !event.data || event.data.source !== 'id5-cms') { return; }
        var data = event.data;

        switch (data.type) {
            case 'builder-render':
                render(data);
                break;
            case 'builder-select':
                selected = data.id || null;
                selectedRoot = data.id ? null : (data.section || null);
                paintSelection();
                break;
            case 'builder-scroll':
                var el = data.id ? byId(data.id) : roots[data.section];
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                break;
            case 'builder-edit-text':
                setTimeout(function () {
                    var block = byId(data.id);
                    var d = block && (info(block).e || [])[0];
                    if (!d) { return; }
                    var target = d.s ? block.querySelector(d.s) : block;
                    if (target) { startEdit({ el: target, id: data.id, a: d.a }, true); }
                }, 60);
                break;
            case 'builder-drag-start':
                tile = { row: !!data.row };
                place();
                break;
            case 'builder-drag-end':
                endTile();
                break;
        }
    });

    tell({ type: 'builder-sections', sections: sections, slots: slots });
})();
</script>
