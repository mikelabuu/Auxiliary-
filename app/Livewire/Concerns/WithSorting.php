<?php

namespace App\Livewire\Concerns;

/**
 * Column sorting for Livewire tables. A header calls sortBy('column'); the
 * component applies it in render() via applySort(), which orders the WHOLE
 * dataset (across pages) and falls back to a default order when nothing is
 * picked. Only whitelisted columns are honoured, so headers can't inject
 * arbitrary SQL.
 */
trait WithSorting
{
    public ?string $sortField = null;
    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Sort picked from a single <select> instead of a column header.
     *
     * A header can only offer the sort for a column that is on screen, and the
     * narrow table layout folds seven columns into four — so check-out and
     * status would lose their ordering entirely on a laptop. This keeps every
     * sort reachable at any width, and states direction outright rather than
     * making it a property of how many times you clicked.
     *
     * Value is "field:direction". An empty field means "no explicit sort",
     * which returns the table to its own default order; applySort() drops
     * anything outside the caller's whitelist, so a tampered <option> cannot
     * order by an arbitrary column.
     */
    public function setSort(string $value): void
    {
        [$field, $direction] = array_pad(explode(':', $value, 2), 2, 'asc');

        $this->sortField = $field !== '' ? $field : null;
        $this->sortDirection = $direction === 'desc' ? 'desc' : 'asc';

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** Current selection as the <select> spells it, for round-tripping. */
    public function sortValue(): string
    {
        return $this->sortField ? $this->sortField . ':' . $this->sortDirection : '';
    }

    /**
     * @param  array<string>  $allowed        columns the table permits sorting on
     * @param  callable       $default        fn($query) => $query (applied when unsorted)
     */
    public function applySort($query, array $allowed, callable $default)
    {
        if ($this->sortField && in_array($this->sortField, $allowed, true)) {
            return $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $default($query);
    }
}
