
function initializeReviewForms() {
    document.querySelectorAll('[data-review-form]').forEach((form) => {
        if (form.dataset.initialized) return;
        form.dataset.initialized = 'true';
        const container = form.querySelector('[data-review-rows]');
        form.querySelector('[data-add-review-row]')?.addEventListener('click', () => {
            const original = container.querySelector('[data-review-row]');
            if (!original || container.children.length >= 74) return;
            const clone = original.cloneNode(true);
            const index = Math.max(...Array.from(container.querySelectorAll('[name]')).map((input) => Number(input.name.match(/\[(\d+)\]/)?.[1] ?? 0))) + 1;
            clone.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/records\[\d+\]/, `records[${index}]`);
                if (/\[(cases|deaths|recoveries)\]$/.test(input.name)) input.value = '';
            });
            container.append(clone);
            clone.querySelector('select')?.focus();
        });
        container.addEventListener('click', (event) => {
            if (event.target.closest('[data-remove-row]') && container.children.length > 1) {
                event.target.closest('[data-review-row]').remove();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeReviewForms);
document.addEventListener('livewire:navigated', initializeReviewForms);

function initializeDashboardFilters() {
    const form = document.querySelector('.watch-filters');
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized = 'true';
    form.querySelector('[name="mode"]')?.addEventListener('change', () => {
        for (const name of ['year', 'frequency', 'case_type', 'coverage', 'state']) {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) input.disabled = true;
        }
        form.requestSubmit();
    });
    form.querySelector('[name="disease"]')?.addEventListener('change', () => {
        if (form.querySelector('[name="mode"]').value !== 'imported') return;
        for (const name of ['year', 'frequency', 'case_type', 'coverage', 'state']) {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) input.disabled = true;
        }
        form.requestSubmit();
    });
    form.querySelector('[name="state"]')?.addEventListener('change', (event) => {
        const coverage = form.querySelector('[name="coverage"]');
        if (coverage && event.target.value) coverage.value = 'states';
    });
    form.querySelector('[name="coverage"]')?.addEventListener('change', (event) => {
        if (event.target.value === 'national') form.querySelector('[name="state"]').value = '';
    });
}

document.addEventListener('DOMContentLoaded', initializeDashboardFilters);
document.addEventListener('livewire:navigated', initializeDashboardFilters);
