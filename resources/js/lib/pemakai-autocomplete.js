// Autocomplete nama pemakai SPD (Master Pekerja), dipakai form Buat Surat (driver & shortcut admin).
// Markup: <input data-pemakai-input data-index="1" data-lookup-url="..."> + <input type="hidden" id="userId1">
// Dropdown dipasang di <body> dengan position:fixed supaya tidak terpotong card/overflow induknya.
import { escapeHtml } from './format';

export function initPemakaiAutocomplete(root = document) {
    const inputs = root.querySelectorAll('[data-pemakai-input]');
    if (!inputs.length) return;

    const box = document.createElement('div');
    box.hidden = true;
    box.className = 'fixed z-[2000] max-h-60 overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-lg';
    document.body.appendChild(box);

    const hide = () => {
        box.hidden = true;
        box.innerHTML = '';
    };

    const place = (input) => {
        const r = input.getBoundingClientRect();
        Object.assign(box.style, { left: `${r.left}px`, top: `${r.bottom + 2}px`, width: `${r.width}px` });
        box.hidden = false;
    };

    inputs.forEach((input) => {
        const hidden = document.getElementById(`userId${input.dataset.index}`);
        let timer;

        input.addEventListener('input', () => {
            if (hidden) hidden.value = '';
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) return hide();

            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`${input.dataset.lookupUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                    const items = (await res.json()).results || [];
                    box.innerHTML = '';
                    if (!items.length) return hide();

                    items.forEach((item) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'grid w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground';
                        option.innerHTML = `<span class="font-medium">${escapeHtml(item.name)}</span>`
                            + (item.position ? `<span class="text-xs text-muted-foreground">${escapeHtml(item.position)}</span>` : '');
                        option.addEventListener('click', () => {
                            input.value = item.name;
                            if (hidden) hidden.value = item.user_id || '';
                            hide();
                        });
                        box.appendChild(option);
                    });
                    place(input);
                } catch {
                    // pencarian gagal: biarkan pengguna mengetik nama manual
                }
            }, 250);
        });

        input.addEventListener('focus', () => {
            if (box.innerHTML.trim() !== '') place(input);
        });
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && !e.target.matches('[data-pemakai-input]')) hide();
    });
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
}
