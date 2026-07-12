@props([
    'title' => 'Track Your Document',
    'sampleReference' => 'TL-2026-0412',
    'placeholder' => 'Enter reference code',
    'helpText' => 'Reference codes are found on your QR printout or the confirmation email.',
])

<div class="card tracker-search-card">
    <div class="tracker-search-inner">
        <svg class="tracker-search-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <h1 class="tracker-search-title">{{ $title }}</h1>

        <p class="tracker-search-copy">
            Enter your reference code to see the current status.
            <span class="tracker-search-sample">{{ $sampleReference }}</span>
        </p>

        <div class="scan-field tracker-search-field">
            <svg class="scan-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" x-model="code" placeholder="{{ $placeholder }}" @keydown.enter="search()" @focus="searchFocused = true" @blur="searchFocused = false">
        </div>

        <p class="tracker-search-example" x-show="!searchFocused && !code.trim()">Example: {{ $sampleReference }}</p>

        <div class="tracker-search-actions">
            <button class="btn btn-primary btn-lg" @click="search()" :disabled="searching || !code.trim()">
                <template x-if="!searching"><svg class="tracker-search-action-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></template>
                <template x-if="searching"><svg class="tracker-search-action-icon spinner-inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                <span x-text="searching ? 'Searching...' : 'Track Document'"></span>
            </button>
            <button class="btn btn-outline" @click="reset()" x-show="found || notFound">Clear</button>
        </div>

        <div class="tracker-recent" x-show="recentSearches.length > 0">
            <p class="tracker-recent-label">Recent searches</p>
            <div class="tracker-recent-list">
                <template x-for="recent in recentSearches" :key="recent">
                    <button type="button" class="tracker-recent-chip" @click="applyRecent(recent)" x-text="recent"></button>
                </template>
            </div>
        </div>

        <p class="tracker-search-help">{{ $helpText }}</p>
    </div>
</div>
