{{-- Progressive-enhancement markdown editor: toolbar, @mention autocomplete,
     server-rendered preview. Enhances every <textarea data-editor>. No deps. --}}
@once
@push('head')
<style>
    .mdw { border: 1px solid var(--border); border-radius: 6px; background: #fff; }
    .mdw-bar { display: flex; gap: 0.2rem; padding: 0.3rem; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
    .mdw-bar button { background: #f3f4f6; color: #374151; border: 1px solid var(--border); padding: 0.15rem 0.5rem; font-size: 0.8rem; border-radius: 4px; cursor: pointer; }
    .mdw-bar button.on { background: var(--accent); color: #fff; }
    .mdw textarea { border: 0 !important; width: 100%; display: block; resize: vertical; }
    .mdw-preview { padding: 0.6rem; font-size: 0.9rem; }
    .mdw-menu { position: absolute; z-index: 50; background: #fff; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 8px 20px rgba(0,0,0,0.12); min-width: 200px; max-height: 200px; overflow: auto; }
    .mdw-menu div { padding: 0.35rem 0.6rem; cursor: pointer; font-size: 0.85rem; }
    .mdw-menu div.sel, .mdw-menu div:hover { background: #eef2ff; }
</style>
@endpush
@push('foot')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

    function wrap(ta, before, after) {
        const s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0, s) + before + v.slice(s, e) + (after ?? before) + v.slice(e);
        ta.focus();
        ta.selectionStart = s + before.length;
        ta.selectionEnd = e + before.length;
        ta.dispatchEvent(new Event('input'));
    }
    function linePrefix(ta, prefix) {
        const s = ta.selectionStart, v = ta.value;
        const ls = v.lastIndexOf('\n', s - 1) + 1;
        ta.value = v.slice(0, ls) + prefix + v.slice(ls);
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    document.querySelectorAll('textarea[data-editor]').forEach(function (ta) {
        if (ta.dataset.mdwReady) return;
        ta.dataset.mdwReady = '1';

        const box = document.createElement('div');
        box.className = 'mdw';
        ta.parentNode.insertBefore(box, ta);

        const bar = document.createElement('div');
        bar.className = 'mdw-bar';
        bar.innerHTML =
            '<button type="button" data-a="b" title="Bold"><b>B</b></button>' +
            '<button type="button" data-a="i" title="Italic"><i>I</i></button>' +
            '<button type="button" data-a="code" title="Code">&lt;/&gt;</button>' +
            '<button type="button" data-a="ul" title="List">&bull; List</button>' +
            '<button type="button" data-a="link" title="Link">Link</button>' +
            '<button type="button" data-a="preview" title="Toggle preview">Preview</button>';
        box.appendChild(bar);
        box.appendChild(ta);

        const preview = document.createElement('div');
        preview.className = 'mdw-preview';
        preview.hidden = true;
        box.appendChild(preview);

        bar.addEventListener('click', function (ev) {
            const btn = ev.target.closest('button'); if (!btn) return;
            const a = btn.dataset.a;
            if (a === 'b') wrap(ta, '**');
            else if (a === 'i') wrap(ta, '_');
            else if (a === 'code') wrap(ta, '`');
            else if (a === 'ul') linePrefix(ta, '- ');
            else if (a === 'link') wrap(ta, '[', '](https://)');
            else if (a === 'preview') {
                const show = preview.hidden;
                btn.classList.toggle('on', show);
                preview.hidden = !show;
                if (show) refreshPreview();
            }
        });

        let pvTimer;
        function refreshPreview() {
            if (preview.hidden) return;
            clearTimeout(pvTimer);
            pvTimer = setTimeout(function () {
                fetch('{{ route('comments.preview') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ body: ta.value }),
                }).then(r => r.json()).then(d => { preview.innerHTML = d.html || ''; });
            }, 250);
        }
        ta.addEventListener('input', refreshPreview);

        // --- @mention autocomplete ---
        let menu, items = [], sel = 0, atPos = -1;
        function closeMenu() { if (menu) { menu.remove(); menu = null; } atPos = -1; }
        function openMenu() {
            closeMenu();
            menu = document.createElement('div');
            menu.className = 'mdw-menu';
            const r = ta.getBoundingClientRect();
            menu.style.left = (window.scrollX + r.left + 8) + 'px';
            menu.style.top = (window.scrollY + r.bottom - 4) + 'px';
            document.body.appendChild(menu);
        }
        function renderMenu() {
            if (!menu) return;
            menu.innerHTML = items.map((u, i) =>
                '<div class="' + (i === sel ? 'sel' : '') + '" data-h="' + u.handle + '">' +
                u.name + ' <span class="muted">@' + u.handle + '</span></div>').join('');
            menu.querySelectorAll('div').forEach((d, i) => {
                d.addEventListener('mousedown', e => { e.preventDefault(); choose(i); });
            });
        }
        function choose(i) {
            const u = items[i]; if (!u) return;
            const v = ta.value;
            ta.value = v.slice(0, atPos) + '@' + u.handle + ' ' + v.slice(ta.selectionStart);
            const caret = atPos + u.handle.length + 2;
            closeMenu();
            ta.focus();
            ta.selectionStart = ta.selectionEnd = caret;
            ta.dispatchEvent(new Event('input'));
        }
        ta.addEventListener('keyup', function (ev) {
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(ev.key) && menu) return;
            const s = ta.selectionStart;
            const m = /(?:^|\s)@([\w.\-]*)$/.exec(ta.value.slice(0, s));
            if (!m) { closeMenu(); return; }
            atPos = s - m[1].length - 1;
            fetch('{{ route('mentions.index') }}?q=' + encodeURIComponent(m[1]))
                .then(r => r.json()).then(list => {
                    items = list; sel = 0;
                    if (!items.length) { closeMenu(); return; }
                    if (!menu) openMenu();
                    renderMenu();
                });
        });
        ta.addEventListener('keydown', function (ev) {
            if (!menu) return;
            if (ev.key === 'ArrowDown') { ev.preventDefault(); sel = (sel + 1) % items.length; renderMenu(); }
            else if (ev.key === 'ArrowUp') { ev.preventDefault(); sel = (sel - 1 + items.length) % items.length; renderMenu(); }
            else if (ev.key === 'Enter') { ev.preventDefault(); choose(sel); }
            else if (ev.key === 'Escape') { closeMenu(); }
        });
        ta.addEventListener('blur', () => setTimeout(closeMenu, 150));
    });
})();
</script>
@endpush
@endonce
