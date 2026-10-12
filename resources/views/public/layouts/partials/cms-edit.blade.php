{{-- ════════════════════════════════════════════════════════════════
     Edit mode — shown ONLY to a website editor who arrived from WordPress
     (see App\Support\CmsEdit). An ordinary visitor receives none of this
     file: the @if below is false and nothing is output.

     Three things are drawn for an editor:
       1. A WordPress-style black bar across the top, with the button that
          opens the site editor on the page being viewed.
       2. A pencil on every editable item of the page (a news story, an
          office, the banner). Each @cmsEdit(...) in the views leaves an
          invisible marker; the script below attaches the pencil to the
          element right after it.
       3. A pencil on every piece of fixed wording — menu labels, headings,
          buttons. In edit mode each line the translator prints carries an
          invisible tag (see App\Support\CmsText); the script reads the tag,
          removes it, and remembers which element showed the line.

     Inside the WordPress site editor this page is shown in a frame. There
     the bar is not drawn (the editor has its own panel) and the pencils
     report clicks to the editor instead of navigating.
     ════════════════════════════════════════════════════════════════ --}}
@if (\App\Support\CmsEdit::active())
@php
    $cmsWp = \App\Support\CmsEdit::wordpressUrl();
    $cmsOrigin = (function (string $url) {
        $p = parse_url($url);
        return ($p['scheme'] ?? 'http').'://'.($p['host'] ?? '').(isset($p['port']) ? ':'.$p['port'] : '');
    })($cmsWp);
    $cmsNew = [
        'post'             => 'ข่าว',
        'id5_announcement' => 'ประกาศ',
        'id5_alert'        => 'แจ้งเตือน',
        'id5_knowledge'    => 'บทความคลังความรู้',
        'id5_faq'          => 'คำถามที่พบบ่อย',
        'id5_form'         => 'แบบฟอร์มดาวน์โหลด',
        'id5_office'       => 'สำนักงาน',
        'id5_person'       => 'บุคลากร',
        'id5_ita_card'     => 'หัวข้อ ITA',
    ];
    $cmsSiteName = \App\Support\CmsText::untag((string) \App\Models\Setting::get('general.site_name', 'ID5 @ Thailand 5.0'));
    // Handed to the script below. Built here rather than with @json(), which
    // cannot parse a multi-line array. `texts` must be read last: it lists the
    // wording used by everything rendered above this point.
    $cmsCfg = [
        'wp'     => $cmsWp,
        'origin' => $cmsOrigin,
        'token'  => \App\Support\CmsEdit::token(),
        'param'  => \App\Support\CmsEdit::PARAM,
        'texts'  => array_column(\App\Support\CmsText::used(), 'key'),
    ];
@endphp

<style>
    #id5-bar { position: fixed; top: 0; left: 0; right: 0; height: 32px; z-index: 2147483000; display: none;
        align-items: stretch; background: #1d2327; color: #f0f0f1; font: 400 13px/32px -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif; }
    html.id5-standalone #id5-bar { display: flex; }
    html.id5-standalone { margin-top: 32px !important; }
    html.id5-standalone header.sticky { top: 32px !important; }
    #id5-bar a, #id5-bar button { display: flex; align-items: center; gap: 6px; padding: 0 10px; color: #f0f0f1; text-decoration: none;
        background: none; border: 0; font: inherit; cursor: pointer; white-space: nowrap; }
    #id5-bar a:hover, #id5-bar button:hover, #id5-bar .id5-bar__menu:hover > button { background: #2c3338; color: #72aee6; }
    #id5-bar i { font-size: 17px; }
    #id5-bar .id5-bar__primary { background: #2271b1; color: #fff; font-weight: 600; }
    #id5-bar .id5-bar__primary:hover { background: #135e96; color: #fff; }
    #id5-bar .id5-bar__spacer { flex: 1; }
    #id5-bar .id5-bar__menu { position: relative; }
    #id5-bar .id5-bar__menu ul { display: none; position: absolute; top: 32px; left: 0; min-width: 190px; margin: 0; padding: 6px 0;
        list-style: none; background: #2c3338; box-shadow: 0 3px 5px rgba(0,0,0,.2); }
    #id5-bar .id5-bar__menu:hover ul, #id5-bar .id5-bar__menu:focus-within ul { display: block; }
    #id5-bar .id5-bar__menu ul a { line-height: 28px; padding: 0 12px; }
    #id5-bar .id5-bar__who { padding: 0 10px; color: #c3c4c7; }
    @media (max-width: 640px) { #id5-bar .id5-bar__hide-sm { display: none; } }

    .id5-hot { outline: 2px solid #2271b1 !important; outline-offset: 2px; }
    [data-cms-text].id5-hot { outline: 2px dashed #2271b1 !important; }
    .id5-flash { outline: 3px solid #f0b849 !important; outline-offset: 3px; transition: outline-color .3s; }
    #id5-pencil { position: fixed; z-index: 2147482000; display: none; align-items: center; gap: 5px; max-width: 280px; padding: 4px 10px 4px 8px;
        border: 0; border-radius: 3px; background: #2271b1; color: #fff; font: 600 12px/20px -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif;
        box-shadow: 0 2px 6px rgba(0,0,0,.3); cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #id5-pencil:hover { background: #135e96; }
    /* "+" tile: add a new item right where it will appear */
    .id5-add { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; width: 6rem; min-height: 7.5rem;
        padding: 8px; border: 2px dashed #72aee6; border-radius: 12px; background: rgba(255,255,255,.92); color: #135e96; cursor: pointer;
        font: 600 11px/1.35 -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", sans-serif; text-align: center; }
    .id5-add:hover, .id5-add:focus-visible { background: #f0f6fc; border-color: #2271b1; border-style: solid; outline: none; }
    .id5-add i { font-size: 26px; }
    @media (min-width: 640px) { .id5-add { width: 7rem; min-height: 8.5rem; } }
    .id5-add.id5-add--wide { display: flex; flex-direction: row; gap: 8px; width: 100%; min-height: 0; margin-top: 12px; padding: 12px; box-sizing: border-box; font-size: 13px; }
    .id5-add.id5-add--wide i { font-size: 18px; }
    #id5-pencil i { font-size: 15px; }
</style>

<div id="id5-bar" role="navigation" aria-label="แถบเครื่องมือผู้ดูแลเว็บไซต์">
    <a href="{{ $cmsWp }}/wp-admin/" title="ไปที่แผงควบคุม WordPress"><i class="ti ti-brand-wordpress"></i></a>
    <a href="{{ $cmsWp }}/wp-admin/"><i class="ti ti-home"></i><span class="id5-bar__hide-sm">{{ $cmsSiteName }}</span></a>
    <a href="#" class="id5-bar__primary" data-id5-open-editor><i class="ti ti-brush"></i>แก้ไขเว็บไซต์</a>
    <div class="id5-bar__menu">
        <button type="button" aria-haspopup="true"><i class="ti ti-plus"></i><span class="id5-bar__hide-sm">สร้างใหม่</span></button>
        <ul>
            @foreach ($cmsNew as $cmsType => $cmsLabel)
                <li><a href="#" data-id5-open-editor="new:{{ $cmsType }}">{{ $cmsLabel }}</a></li>
            @endforeach
        </ul>
    </div>
    <span class="id5-bar__spacer"></span>
    <span class="id5-bar__who id5-bar__hide-sm">สวัสดี {{ \App\Support\CmsEdit::name() }}</span>
    <a href="{{ route('public.cms-edit.leave') }}" title="ซ่อนแถบนี้และดูเว็บไซต์แบบผู้เข้าชมทั่วไป"><i class="ti ti-eye"></i><span class="id5-bar__hide-sm">ดูแบบผู้เข้าชม</span></a>
</div>

<button type="button" id="id5-pencil"><i class="ti ti-pencil"></i><span></span></button>

<script>
(function () {
    'use strict';

    var CFG = {!! json_encode($cmsCfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!};

    var framed = window.parent !== window;
    var root   = document.documentElement;
    var pencil = document.getElementById('id5-pencil');

    // The page's own address, without the edit-mode token.
    function cleanPath() {
        var url = new URL(window.location.href);
        url.searchParams.delete(CFG.param);
        var query = url.searchParams.toString();
        return url.pathname + (query ? '?' + query : '');
    }

    // Where a pencil leads: the WordPress site editor, on this page, with the
    // chosen item already open.
    function editorUrl(open) {
        var url = new URL(CFG.wp + '/wp-admin/admin.php');
        url.searchParams.set('page', 'id5-site-editor');
        url.searchParams.set('path', cleanPath());
        if (open) { url.searchParams.set('open', open); }
        return url.toString();
    }

    function tellEditor(message) {
        message.source = 'id5-cms';
        window.parent.postMessage(message, CFG.origin);
    }

    // ── Editable items: a news story, an office, the banner ─────────────
    var regions = [];

    document.querySelectorAll('span[data-cms-edit]').forEach(function (marker) {
        var el = marker.nextElementSibling;
        while (el && el.matches('span[data-cms-edit]')) { el = el.nextElementSibling; }
        if (!el || el.hasAttribute('data-cms-region')) { return; }

        el.setAttribute('data-cms-region', regions.length);
        regions.push({ el: el, target: marker.dataset.cmsEdit, kind: marker.dataset.cmsKind || '', title: marker.dataset.cmsTitle || '' });
    });

    // ── Fixed wording: find each tagged line, and take the tag off ──────
    // A tag is a position in CFG.texts, written in zero-width characters
    // between two invisible separators.
    var FENCE = '⁣';
    var TAG   = /⁣([​‌‍⁠]+)⁣/g;
    var DIGIT = { '​': 0, '‌': 1, '‍': 2, '⁠': 3 };
    // Attributes whose value a visitor reads (or hears), as opposed to ones
    // that only make the page work.
    var SHOWN = ['placeholder', 'title', 'alt', 'aria-label', 'value', 'content', 'label'];
    var firstSeen = {};

    function keysIn(value) {
        var keys = [], match;
        TAG.lastIndex = 0;

        while ((match = TAG.exec(value))) {
            var position = 0;
            for (var i = 0; i < match[1].length; i++) { position = position * 4 + DIGIT[match[1].charAt(i)]; }
            var key = CFG.texts[position];
            if (key && keys.indexOf(key) < 0) { keys.push(key); }
        }

        return keys;
    }

    function claim(el, keys) {
        if (!el || !keys.length || el === pencil || (el.closest && el.closest('#id5-bar, #id5-pencil'))) { return; }

        var have = (el.getAttribute('data-cms-text') || '').split(' ').filter(Boolean);

        keys.forEach(function (key) {
            if (have.indexOf(key) < 0) { have.push(key); }
            if (!firstSeen[key]) { firstSeen[key] = el; }
        });

        el.setAttribute('data-cms-text', have.join(' '));
    }

    function readText(node) {
        var value = node.nodeValue;
        if (!value || value.indexOf(FENCE) < 0) { return; }

        var parent = node.parentElement;
        // Script and style text is code, not wording on the page: left alone.
        if (!parent || /^(SCRIPT|STYLE|NOSCRIPT)$/.test(parent.tagName)) { return; }

        var keys = keysIn(value);
        node.nodeValue = value.replace(TAG, '');
        claim(parent, keys);
    }

    function readAttributes(el) {
        if (!el.attributes) { return; }

        for (var i = 0; i < el.attributes.length; i++) {
            var attribute = el.attributes[i];
            if (attribute.value.indexOf(FENCE) < 0) { continue; }

            // Alpine evaluates these; whatever they print is read when it
            // reaches the page.
            if (/^(x-|:|@)/.test(attribute.name)) { continue; }

            var keys = keysIn(attribute.value);
            el.setAttribute(attribute.name, attribute.value.replace(TAG, ''));

            if (SHOWN.indexOf(attribute.name) >= 0 && !el.hasAttribute('data-cms-edit')) { claim(el, keys); }
        }

        if ((el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') && typeof el.value === 'string' && el.value.indexOf(FENCE) >= 0) {
            el.value = el.value.replace(TAG, '');
        }
    }

    function read(node) {
        if (node.nodeType === 3) { readText(node); return; }
        if (node.nodeType !== 1) { return; }

        var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT);
        var current = node;

        while (current) {
            if (current.nodeType === 3) { readText(current); } else { readAttributes(current); }
            current = walker.nextNode();
        }
    }

    read(root);

    // Menus, tabs and the assessment wizard draw themselves later; read what
    // they add as it appears.
    new MutationObserver(function (records) {
        records.forEach(function (record) {
            if (record.type === 'characterData') {
                readText(record.target);
            } else if (record.type === 'attributes') {
                readAttributes(record.target);
            } else {
                record.addedNodes.forEach(read);
            }
        });
    }).observe(root, { subtree: true, childList: true, characterData: true, attributes: true });

    // ── What a pencil can point at ──────────────────────────────────────
    // Either an item (opens its WordPress form) or wording (opens the text
    // form for the lines that element shows).
    function targetOf(el) {
        if (!el) { return null; }

        if (el.hasAttribute('data-cms-text')) {
            var keys = el.getAttribute('data-cms-text').split(' ').filter(Boolean);
            return { el: el, target: 'text:' + keys.join(','), kind: 'ข้อความ', title: (el.textContent || '').trim().slice(0, 90), text: true };
        }

        return regions[Number(el.getAttribute('data-cms-region'))] || null;
    }

    function open(target) {
        if (framed) {
            tellEditor({ type: 'edit', target: target.target, kind: target.kind, title: target.title });
        } else {
            window.location.href = editorUrl(target.target);
        }
    }

    // ── The pencil that follows the mouse ───────────────────────────────
    var label  = pencil.querySelector('span');
    var current = null;
    var hideTimer = null;

    function place() {
        if (!current) { return; }
        var rect = current.el.getBoundingClientRect();
        var floor = framed ? 6 : 38;
        var top;

        if (current.text) {
            // Wording is often a small label or a link; the pencil sits just
            // above it so the label itself stays readable and clickable.
            top = rect.top - 30 >= floor ? rect.top - 30 : rect.bottom + 4;
        } else {
            top = Math.max(rect.top + 6, floor);
        }

        pencil.style.top  = Math.max(floor, Math.min(top, window.innerHeight - 34)) + 'px';
        pencil.style.left = Math.max(Math.min(rect.left + (current.text ? 0 : 6), window.innerWidth - 150), 6) + 'px';
    }

    function show(target) {
        clearTimeout(hideTimer);
        if (current && current.el !== target.el) { current.el.classList.remove('id5-hot'); }
        current = target;
        target.el.classList.add('id5-hot');
        label.textContent = 'แก้ไข' + (target.kind ? ' ' + target.kind : '');
        pencil.title = target.title;
        pencil.style.display = 'flex';
        place();
    }

    function hideSoon() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            if (current) { current.el.classList.remove('id5-hot'); }
            current = null;
            pencil.style.display = 'none';
        }, 400);
    }

    document.addEventListener('mouseover', function (event) {
        if (event.target === pencil || pencil.contains(event.target)) { clearTimeout(hideTimer); return; }
        var el = event.target.closest ? event.target.closest('[data-cms-region], [data-cms-text]') : null;
        var target = targetOf(el);
        if (target) { show(target); } else if (current) { hideSoon(); }
    });
    document.addEventListener('mouseleave', hideSoon);
    window.addEventListener('scroll', place, { passive: true });
    window.addEventListener('resize', place);

    pencil.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (current) { open(current); }
    });

    // ── "+" tiles: add a new item in this spot ──────────────────────────
    var adders = Array.prototype.map.call(document.querySelectorAll('[data-cms-add]'), function (el) {
        return { el: el, target: el.getAttribute('data-cms-add'), kind: el.getAttribute('data-cms-kind') || '', title: el.getAttribute('data-cms-title') || '' };
    });

    document.addEventListener('click', function (event) {
        var tile = event.target.closest ? event.target.closest('[data-cms-add]') : null;
        if (!tile) { return; }

        event.preventDefault();
        event.stopPropagation();
        open({ target: tile.getAttribute('data-cms-add'), kind: tile.getAttribute('data-cms-kind') || '', title: tile.getAttribute('data-cms-title') || '' });
    }, true);

    if (!framed) {
        // ── Standalone: the black bar ───────────────────────────────────
        root.classList.add('id5-standalone');

        document.querySelectorAll('[data-id5-open-editor]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                window.location.href = editorUrl(link.getAttribute('data-id5-open-editor') || null);
            });
        });

        // The token has done its job (edit mode is remembered); keep it out
        // of the address bar so it is not copied into a shared link.
        if (new URL(window.location.href).searchParams.has(CFG.param)) {
            window.history.replaceState(null, '', cleanPath() + window.location.hash);
        }

        return;
    }

    // ── Framed inside the WordPress site editor ─────────────────────────
    tellEditor({
        type: 'page',
        path: cleanPath(),
        title: document.title,
        lang: root.getAttribute('lang') || '',
        items: regions.map(function (r) { return { target: r.target, kind: r.kind, title: r.title }; })
            .concat(adders.map(function (a) { return { target: a.target, kind: a.kind, title: a.title }; })),
        texts: CFG.texts
    });

    function find(data) {
        if (String(data.target).indexOf('text:') === 0) {
            var el = firstSeen[String(data.target).slice(5).split(',')[0]];
            return el && document.contains(el) ? { el: el } : null;
        }

        var matching = regions.concat(adders).filter(function (r) { return r.target === data.target; });
        return matching[data.index || 0] || matching[0] || null;
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== CFG.origin || !event.data || event.data.source !== 'id5-cms') { return; }

        var found = find(event.data);
        if (!found) { return; }

        if (event.data.type === 'focus') {
            found.el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            found.el.classList.add('id5-flash');
            setTimeout(function () { found.el.classList.remove('id5-flash'); }, 1600);
        } else if (event.data.type === 'hover') {
            found.el.classList.add('id5-hot');
        } else if (event.data.type === 'unhover') {
            found.el.classList.remove('id5-hot');
        }
    });

    // Keep edit mode while browsing inside the frame even if the browser
    // withholds this site's cookie there, and never let the frame wander off
    // to another website.
    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) { return; }

        var url;
        try { url = new URL(link.href, window.location.href); } catch (e) { return; }
        if (url.protocol !== 'http:' && url.protocol !== 'https:') { return; }

        if (url.origin !== window.location.origin) {
            link.target = '_blank';
            link.rel = 'noopener';
        } else if (!url.searchParams.has(CFG.param)) {
            url.searchParams.set(CFG.param, CFG.token);
            link.href = url.toString();
        }
    }, true);
})();
</script>
@endif
