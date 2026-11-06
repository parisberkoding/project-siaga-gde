// Tabs (struktur shadcn): [data-tabs] berisi [data-tabs-trigger="x"] dan [data-tabs-content="x"].
// Saat berganti, container memancarkan event `tabs:change` dengan detail { value }.

function own(container, selector) {
    return [...container.querySelectorAll(selector)].filter((el) => el.closest('[data-tabs]') === container);
}

function activate(container, value) {
    own(container, '[data-tabs-trigger]').forEach((trigger) => {
        const active = trigger.dataset.tabsTrigger === value;
        trigger.dataset.state = active ? 'active' : 'inactive';
        trigger.setAttribute('aria-selected', active);
    });
    own(container, '[data-tabs-content]').forEach((panel) => {
        panel.hidden = panel.dataset.tabsContent !== value;
    });
    container.dispatchEvent(new CustomEvent('tabs:change', { detail: { value }, bubbles: true }));
}

document.querySelectorAll('[data-tabs]').forEach((container) => {
    const first = own(container, '[data-tabs-trigger]')[0];
    activate(container, container.dataset.tabs || first?.dataset.tabsTrigger);

    container.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-tabs-trigger]');
        if (trigger && trigger.closest('[data-tabs]') === container) {
            activate(container, trigger.dataset.tabsTrigger);
        }
    });
});
