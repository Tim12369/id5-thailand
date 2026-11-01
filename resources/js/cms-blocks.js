/**
 * Ready-made components built in WordPress's page builder.
 *
 * WordPress stores them as ordinary blocks with a class name (a group with
 * "id5-tabs", a list with "id5-social" …). Without this script each one still
 * reads well — tabs show one after the other, a slider is a row of pictures
 * that scrolls sideways, a map or video is its address as text. This script
 * turns them into the real thing:
 *
 *   .id5-tabs          tab buttons, one panel shown at a time
 *   .id5-slider        one picture at a time, with arrows and dots
 *   .id5-carousel      several at a time, with arrows and dots
 *   .id5-autoplay      … moving on by itself every 5 seconds
 *   .id5-social        an icon for each link (Facebook, LINE, YouTube …)
 *   p.id5-map          a Google map of the place written in it
 *   .wp-block-embed    a YouTube video
 *   .id5-dropdown      closes when clicking elsewhere
 *
 * The site editor's preview calls window.ID5Blocks.enhance() again each time
 * it redraws a section, with { editing: true }: nothing then moves by itself,
 * and the tab being edited stays open.
 */

const remembered = new Map();

const SOCIAL = [
    [/facebook\.com|fb\.me/, 'brand-facebook'],
    [/line\.me|lin\.ee/, 'brand-line'],
    [/youtube\.com|youtu\.be/, 'brand-youtube'],
    [/(^|\.)x\.com|twitter\.com/, 'brand-x'],
    [/instagram\.com/, 'brand-instagram'],
    [/tiktok\.com/, 'brand-tiktok'],
    [/linkedin\.com/, 'brand-linkedin'],
];

function directChildren(el, selector) {
    // A group's blocks may sit in an inner wrapper (older WordPress markup).
    const box = el.querySelector(':scope > .wp-block-group__inner-container') || el;
    return Array.from(box.children).filter((child) => child.matches(selector));
}

function tabs(group, editing) {
    const panels = directChildren(group, '.id5-tab');
    if (!panels.length) return;

    const key = group.getAttribute('data-id5-b');
    const nav = document.createElement('div');
    nav.className = 'id5-tabs__nav';
    nav.setAttribute('role', 'tablist');

    const show = (index) => {
        panels.forEach((panel, i) => {
            panel.classList.toggle('is-active', i === index);
            panel.hidden = i !== index;
            nav.children[i].setAttribute('aria-selected', String(i === index));
            nav.children[i].tabIndex = i === index ? 0 : -1;
        });
        if (key) remembered.set(key, index);
    };

    panels.forEach((panel, i) => {
        const title = panel.querySelector(':scope > :is(h1, h2, h3, h4, h5, h6), :scope > .wp-block-group__inner-container > :is(h1, h2, h3, h4, h5, h6)');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'id5-tabs__button';
        button.setAttribute('role', 'tab');
        button.textContent = (title && title.textContent.trim()) || `${i + 1}`;
        if (title) title.classList.add('id5-tab__title');
        panel.setAttribute('role', 'tabpanel');
        button.addEventListener('click', () => show(i));
        button.addEventListener('keydown', (event) => {
            const step = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
            if (!step) return;
            const next = (i + step + panels.length) % panels.length;
            show(next);
            nav.children[next].focus();
        });
        nav.appendChild(button);
    });

    group.insertBefore(nav, group.firstChild);
    group.classList.add('is-ready');
    if (editing) group.classList.add('is-editing');
    show(Math.min(remembered.get(key) || 0, panels.length - 1));
}

function slider(track, editing) {
    const slides = directChildren(track, '*');
    if (slides.length < 2) return;

    const nav = document.createElement('div');
    nav.className = 'id5-slides__nav';

    const button = (label, text, step) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'id5-slides__arrow';
        b.setAttribute('aria-label', label);
        b.innerHTML = `<i class="ti ti-chevron-${text}" aria-hidden="true"></i>`;
        b.addEventListener('click', () => go(current() + step));
        return b;
    };

    const dots = document.createElement('div');
    dots.className = 'id5-slides__dots';

    const current = () => {
        const left = track.scrollLeft;
        let best = 0;
        slides.forEach((slide, i) => {
            if (Math.abs(slide.offsetLeft - track.offsetLeft - left) < Math.abs(slides[best].offsetLeft - track.offsetLeft - left)) best = i;
        });
        return best;
    };

    const go = (index) => {
        const last = slides.length - 1;
        const to = index > last ? 0 : index < 0 ? last : index;
        track.scrollTo({ left: slides[to].offsetLeft - track.offsetLeft, behavior: 'smooth' });
    };

    slides.forEach((slide, i) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'id5-slides__dot';
        dot.setAttribute('aria-label', `${i + 1} / ${slides.length}`);
        dot.addEventListener('click', () => go(i));
        dots.appendChild(dot);
    });

    const mark = () => {
        const at = current();
        Array.from(dots.children).forEach((dot, i) => dot.classList.toggle('is-active', i === at));
    };

    track.addEventListener('scroll', () => window.requestAnimationFrame(mark), { passive: true });

    nav.append(button('ก่อนหน้า / Previous', 'left', -1), dots, button('ถัดไป / Next', 'right', 1));
    track.insertAdjacentElement('afterend', nav);
    track.classList.add('is-ready');
    mark();

    const still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (track.classList.contains('id5-autoplay') && !editing && !still) {
        let paused = false;
        track.addEventListener('mouseenter', () => { paused = true; });
        track.addEventListener('mouseleave', () => { paused = false; });
        track.addEventListener('focusin', () => { paused = true; });
        window.setInterval(() => { if (!paused && !document.hidden) go(current() + 1); }, 5000);
    }
}

function social(list) {
    list.querySelectorAll('a[href]').forEach((link) => {
        if (link.querySelector('i.ti')) return;
        const href = link.getAttribute('href') || '';
        let name = href.startsWith('mailto:') ? 'mail' : href.startsWith('tel:') ? 'phone' : '';
        try {
            const host = new URL(href, window.location.href).hostname;
            SOCIAL.forEach(([pattern, icon]) => { if (!name && pattern.test(host)) name = icon; });
        } catch (e) { /* not an address */ }
        const label = link.textContent.trim();
        link.innerHTML = `<i class="ti ti-${name || 'world'}" aria-hidden="true"></i><span class="id5-social__text"></span>`;
        link.querySelector('span').textContent = label;
        link.title = label;
        link.classList.add('has-icon');
        if (/^https?:/.test(href)) { link.target = '_blank'; link.rel = 'noopener'; }
    });
}

function mapOf(p) {
    const place = p.textContent.trim();
    if (!place || p.querySelector('iframe')) return;
    const frame = document.createElement('iframe');
    frame.src = `https://www.google.com/maps?q=${encodeURIComponent(place)}&output=embed`;
    frame.loading = 'lazy';
    frame.title = place;
    frame.referrerPolicy = 'no-referrer-when-downgrade';
    frame.setAttribute('allowfullscreen', '');
    p.textContent = '';
    p.appendChild(frame);
    p.setAttribute('aria-label', place);
}

function videoOf(figure) {
    const wrapper = figure.querySelector('.wp-block-embed__wrapper');
    if (!wrapper || wrapper.querySelector('iframe')) return;
    const url = wrapper.textContent.trim();
    const m = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (!m) return;
    const frame = document.createElement('iframe');
    frame.src = `https://www.youtube-nocookie.com/embed/${m[1]}`;
    frame.loading = 'lazy';
    frame.title = 'YouTube';
    frame.allow = 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    frame.setAttribute('allowfullscreen', '');
    wrapper.textContent = '';
    wrapper.appendChild(frame);
}

export function enhance(root, options = {}) {
    if (!root) return;
    const editing = !!options.editing;
    root.querySelectorAll('.id5-tabs').forEach((el) => tabs(el, editing));
    root.querySelectorAll('.id5-slider, .id5-carousel').forEach((el) => slider(el, editing));
    root.querySelectorAll('.id5-social').forEach(social);
    root.querySelectorAll('p.id5-map').forEach(mapOf);
    root.querySelectorAll('.wp-block-embed.is-provider-youtube').forEach(videoOf);
}

window.ID5Blocks = { enhance };

function start() {
    // Inside the WordPress site editor the builder draws each section itself,
    // then calls enhance().
    const framed = window.parent !== window;
    document.querySelectorAll(framed ? '.cms-blocks:not([data-id5-builder])' : '.cms-blocks').forEach((root) => enhance(root));

    document.addEventListener('click', (event) => {
        document.querySelectorAll('details.id5-dropdown[open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.open = false;
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
