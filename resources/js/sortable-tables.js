// ── Client-side sortable tables ──────────────────────────────────────────────
// Makes every `.data-table` sortable by clicking its column headers, reusing the
// existing `.sortable` / `.sort-asc` / `.sort-desc` arrow CSS. Sorts the rows
// currently in the DOM (for paginated tables, that's the current page).
//
// Tables that do their own sorting (Livewire `wire:click="sortBy(...)"`, or the
// server-side ones) opt OUT with `data-server-sort` on the <table>. Individual
// columns opt out with `data-no-sort` on the <th>.

(function () {
    const NUM_RE = /^[-+]?[₱$€£]?\s?[\d,]+(\.\d+)?%?$/;
    const SKIP_HEADERS = new Set(['', 'action', 'actions']);

    const isSortableTable = (t) => t && t.matches('table.data-table') && !t.hasAttribute('data-server-sort');

    const isExcludedHeader = (th) =>
        th.hasAttribute('data-no-sort') || SKIP_HEADERS.has(th.textContent.trim().toLowerCase());

    // Tag eligible headers so the CSS arrow shows. Idempotent.
    function enhance() {
        document.querySelectorAll('table.data-table:not([data-server-sort])').forEach((table) => {
            const head = table.tHead && table.tHead.rows[0];
            if (!head) return;
            [...head.cells].forEach((th) => {
                if (!isExcludedHeader(th)) th.classList.add('sortable');
            });
        });
    }

    const cellText = (row, i) => (row.cells[i] ? row.cells[i].textContent.trim() : '');
    const toNumber = (s) => parseFloat(s.replace(/[₱$€£,%\s]/g, ''));

    function detectType(values) {
        const vals = values.filter((v) => v !== '' && v !== '—' && v !== 'None' && v !== 'N/A');
        if (!vals.length) return 'string';
        if (vals.every((v) => NUM_RE.test(v))) return 'number';
        if (vals.every((v) => !isNaN(Date.parse(v)))) return 'date';
        return 'string';
    }

    function sortTable(table, idx, th) {
        const tbody = table.tBodies[0];
        if (!tbody) return;

        // Only reorder real data rows — leave spanning rows (empty states) put.
        const rows = [...tbody.rows].filter((r) => r.cells.length > idx && !r.querySelector('[colspan]'));
        if (rows.length < 2) return;

        const asc = !th.classList.contains('sort-asc');
        const type = detectType(rows.map((r) => cellText(r, idx)));

        const cmp = (a, b) => {
            const av = cellText(a, idx), bv = cellText(b, idx);
            if (type === 'number') return (toNumber(av) || 0) - (toNumber(bv) || 0);
            if (type === 'date') return (Date.parse(av) || 0) - (Date.parse(bv) || 0);
            return av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
        };

        rows.sort((a, b) => (asc ? cmp(a, b) : -cmp(a, b)));
        const frag = document.createDocumentFragment();
        rows.forEach((r) => frag.appendChild(r));
        tbody.appendChild(frag);

        [...th.parentNode.cells].forEach((c) => c.classList.remove('sort-asc', 'sort-desc'));
        th.classList.add(asc ? 'sort-asc' : 'sort-desc');
    }

    // Delegated click — keeps working after Livewire re-renders.
    document.addEventListener('click', (e) => {
        const th = e.target.closest('th.sortable');
        if (!th) return;
        const table = th.closest('table');
        if (!isSortableTable(table)) return;
        const idx = [...th.parentNode.cells].indexOf(th);
        if (idx > -1) sortTable(table, idx, th);
    });

    // Re-tag headers whenever the DOM changes (Livewire morphs, tab switches…).
    let t = null;
    const schedule = () => { clearTimeout(t); t = setTimeout(enhance, 120); };
    if (document.readyState !== 'loading') enhance();
    document.addEventListener('DOMContentLoaded', enhance);
    document.addEventListener('livewire:navigated', enhance);
    new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
})();
