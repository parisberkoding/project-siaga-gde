{{--
    Autocomplete nama pemakai SPD (Master Pekerja) — dipakai di form Buat Surat
    (driver & shortcut admin).

    Dropdown saran ditempatkan di <body> dengan position:fixed supaya tidak
    pernah terpotong oleh card/overflow induknya.

    Markup yang dibutuhkan di form:
      <input class="form-control pemakai-input" data-index="1">
      <input type="hidden" id="userId1">
--}}
<script>
(function () {
    const lookupUrl = @json(route('spd.passenger.lookup'));
    const timers = {};

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    // Dropdown global di <body> — tidak bisa terpotong oleh ancestor mana pun.
    const box = document.createElement('div');
    box.className = 'list-group shadow pemakai-suggestions-global d-none';
    box.style.cssText = 'position:fixed;z-index:2000;max-height:240px;overflow-y:auto;';
    document.body.appendChild(box);

    function hide() {
        box.classList.add('d-none');
        box.innerHTML = '';
    }

    function place(input) {
        const r = input.getBoundingClientRect();
        box.style.left = r.left + 'px';
        box.style.top = (r.bottom + 2) + 'px';
        box.style.width = r.width + 'px';
        box.classList.remove('d-none');
    }

    function bind(input) {
        const hidden = document.getElementById('userId' + input.dataset.index);

        input.addEventListener('input', () => {
            if (hidden) hidden.value = '';
            clearTimeout(timers[input.dataset.index]);
            const q = input.value.trim();
            if (q.length < 2) { hide(); return; }

            timers[input.dataset.index] = setTimeout(async () => {
                try {
                    const res = await fetch(lookupUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    const items = data.results || [];

                    box.innerHTML = '';
                    if (!items.length) { hide(); return; }

                    items.forEach((item) => {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.innerHTML = '<strong>' + esc(item.name) + '</strong>' +
                            (item.position ? '<br><small class="text-muted">' + esc(item.position) + '</small>' : '');
                        a.addEventListener('click', (e) => {
                            e.preventDefault();
                            input.value = item.name;
                            if (hidden) hidden.value = item.user_id || '';
                            hide();
                        });
                        box.appendChild(a);
                    });

                    place(input);
                } catch (e) { /* diamkan */ }
            }, 250);
        });

        // Munculkan lagi saat fokus bila sudah ada isi saran.
        input.addEventListener('focus', () => {
            if (box.innerHTML.trim() !== '') place(input);
        });
    }

    document.querySelectorAll('.pemakai-input').forEach(bind);

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && !e.target.classList.contains('pemakai-input')) hide();
    });
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();
</script>
