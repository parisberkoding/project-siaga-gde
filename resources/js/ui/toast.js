// Toast (pola shadcn Sonner): notifikasi singkat di pojok kanan bawah, hilang sendiri, berhenti saat disorot.
//   toast({ title, description, variant: 'success' | 'error' | 'warning' | 'info' | 'default', duration })
// Pesan sesi (flash) dari server dikirim lewat <div id="toaster" data-flash='[...]'> di layout.
const variants = {
    success: { icon: 'bi-check-circle-fill', color: 'text-success' },
    error: { icon: 'bi-x-circle-fill', color: 'text-destructive' },
    warning: { icon: 'bi-exclamation-triangle-fill', color: 'text-warning' },
    info: { icon: 'bi-info-circle-fill', color: 'text-info' },
    default: { icon: 'bi-bell-fill', color: 'text-primary' },
};

const toaster = document.getElementById('toaster');

export function toast({ title, description = '', variant = 'default', duration = 5000 } = {}) {
    if (!toaster) return;
    const { icon, color } = variants[variant] ?? variants.default;

    const item = document.createElement('div');
    item.setAttribute('role', variant === 'error' ? 'alert' : 'status');
    item.className = 'pointer-events-auto relative flex w-full items-start gap-3 overflow-hidden rounded-lg border bg-popover p-4 pr-9 text-sm text-popover-foreground shadow-lg'
        + ' translate-y-2 opacity-0 transition-all duration-300 motion-reduce:transition-none';
    item.innerHTML = `<i class="bi ${icon} ${color} mt-0.5 text-base"></i>
        <div class="grid min-w-0 gap-0.5"><p data-title class="font-semibold"></p><p data-description class="text-muted-foreground empty:hidden"></p></div>
        <button type="button" class="absolute right-2 top-2 rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground" aria-label="Tutup notifikasi"><i class="bi bi-x-lg text-xs"></i></button>
        <span data-progress class="absolute inset-x-0 bottom-0 h-0.5 origin-left bg-current ${color} opacity-40"></span>`;
    item.querySelector('[data-title]').textContent = title;
    item.querySelector('[data-description]').textContent = description;

    const close = () => {
        item.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => item.remove(), 300);
    };
    item.querySelector('button').addEventListener('click', close);

    // Hitung mundur yang berhenti saat kursor di atas toast.
    const progress = item.querySelector('[data-progress]');
    let remaining = duration;
    let startedAt;
    let timer;
    const start = () => {
        startedAt = Date.now();
        timer = setTimeout(close, remaining);
        progress.style.transition = `transform ${remaining}ms linear`;
        progress.style.transform = 'scaleX(0)';
    };
    const pause = () => {
        clearTimeout(timer);
        remaining -= Date.now() - startedAt;
        progress.style.transition = 'none';
        progress.style.transform = `scaleX(${remaining / duration})`;
    };
    item.addEventListener('mouseenter', pause);
    item.addEventListener('mouseleave', start);

    toaster.appendChild(item);
    requestAnimationFrame(() => {
        item.classList.remove('translate-y-2', 'opacity-0');
        start();
    });
}

try {
    JSON.parse(toaster?.dataset.flash || '[]').forEach((message, i) => setTimeout(() => toast(message), i * 150));
} catch {
    // data flash rusak: lewati saja, halaman tetap berjalan
}

window.siaga = Object.assign(window.siaga ?? {}, { toast });
