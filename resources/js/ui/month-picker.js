// <x-ui.month-picker>: tombol panah menukar panel tahun (panel lain disembunyikan).
document.addEventListener('click', (event) => {
    const step = event.target.closest('[data-month-picker] [data-year-step]');
    if (!step) return;
    const panel = step.closest('[data-year-panel]');
    const panels = [...panel.parentElement.querySelectorAll(':scope > [data-year-panel]')];
    const next = panels[panels.indexOf(panel) + Number(step.dataset.yearStep)];
    if (!next) return;
    panel.hidden = true;
    next.hidden = false;
    // Fokus tetap di panah yang sama bila masih bisa ditekan, selain itu ke bulan pertama.
    (next.querySelector(`[data-year-step="${step.dataset.yearStep}"]:not(:disabled)`)
        ?? next.querySelector('[role="menuitem"]'))?.focus();
});
