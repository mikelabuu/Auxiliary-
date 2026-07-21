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
