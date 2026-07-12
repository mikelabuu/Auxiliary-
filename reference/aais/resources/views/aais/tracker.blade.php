@php
    $title     = 'Track Document';
    $topbarSub = 'Check the status of your document using your reference code or QR code';
@endphp

@extends('layouts.client')

@section('content')
<div x-data="trackerApp()" x-cloak class="tracker-root">
    <x-aais.tracker.search-card />
    <x-aais.tracker.not-found-card />

    <div x-show="found && result" x-transition>
        <div class="tracker-results-grid">
            <div class="tracker-results-main">
                <x-aais.tracker.result-header
                    reference-x-text="result.ref"
                    document-line-x-text="(result.type || 'General Document') + ' - ' + (result.purpose || 'General Request')"
                    status-class-expr="'status-' + result.status"
                    status-text-expr="result.statusLabel"
                    submitted-date-x-text="result.submittedDisplay"
                    office-x-text="result.office"
                    staff-x-text="result.staff"
                />

                <div class="card card-overflow-hidden">
                    <x-aais.ui.card-header title="Tracking Notes" />

                    <div class="card-body">
                        <div class="tracker-notes-grid">
                            <div><p class="kv-label">Accepted</p><p class="kv-value" x-text="result.acceptedDisplay"></p></div>
                            <div><p class="kv-label">Reference</p><p class="kv-value font-mono" x-text="result.ref"></p></div>
                        </div>

                        <div class="info-box info-box-gold tracker-info-gap" x-show="!result.accepted">
                            <svg class="tracker-warm-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                            <div>
                                <p class="info-box-title tracker-warm-title">Your request is now with staff</p>
                                <p class="info-box-text">Your submission is recorded. Staff will confirm once the physical document is received at the service window.</p>
                            </div>
                        </div>

                        <div class="info-box info-box-green tracker-info-gap" x-show="result.remarks">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <div>
                                <p class="info-box-title">Latest Staff Remarks</p>
                                <p class="info-box-text" x-text="result.remarks"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-overflow-hidden">
                    <x-aais.ui.card-header
                        title="Document Timeline"
                        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
                    />

                    <div class="card-body tracker-timeline-list">
                        <template x-for="(step, idx) in result.timeline" :key="step.title + idx">
                            <div class="tracker-timeline-row" :class="{ 'tracker-timeline-row-active': step.active }">
                                <div class="tracker-timeline-marker">
                                    <span class="tracker-timeline-dot" :class="step.done ? 'done' : (step.active ? 'active' : 'pending')">
                                        <template x-if="step.done"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></template>
                                        <template x-if="!step.done && step.active"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></template>
                                        <template x-if="!step.done && !step.active"><span class="tracker-timeline-dot-core"></span></template>
                                    </span>
                                    <span class="tracker-timeline-line" x-show="idx < result.timeline.length - 1"></span>
                                </div>
                                <div class="tracker-timeline-content">
                                    <div class="tracker-timeline-head">
                                        <p class="tracker-timeline-title" x-text="step.title"></p>
                                        <span class="tracker-timeline-tag" :class="step.done ? 'done' : (step.active ? 'active' : 'pending')" x-text="step.done ? 'Completed' : (step.active ? 'Now' : 'Pending')"></span>
                                    </div>
                                    <p class="tracker-timeline-meta" x-text="step.meta"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="card card-overflow-hidden tracker-status-sidebar">
                <x-aais.ui.card-header
                    title="Current Status"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M12 8v4l3 3'/><circle cx='12' cy='12' r='10'/></svg>"
                />

                <div class="card-body tracker-sidebar-stack">
                    <div>
                        <p class="kv-label">Status</p>
                        <x-aais.ui.status-badge-dynamic
                            class-expr="'status-' + result.status"
                            text-expr="result.statusLabel"
                            class="tracker-status-badge"
                        />
                        <p class="tracker-last-updated" x-text="'Last updated: ' + (result.lastUpdatedLabel || 'just now')"></p>
                    </div>

                    <div>
                        <p class="kv-label">Assigned Staff</p>
                        <p class="kv-value" x-text="result.staff"></p>
                    </div>

                    <div class="info-box info-box-green">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <div>
                            <p class="info-box-title">Tracking Tip</p>
                            <p class="info-box-text">Keep your reference code. Status and remarks update when staff processes your document.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function trackerApp() {
    const RECENT_SEARCHES_KEY = 'aais-tracker-recent-v1';

    return {
        code: '',
        searching: false,
        found: false,
        notFound: false,
        result: null,
        searchFocused: false,
        recentSearches: [],
        copyFeedback: false,
        referencePattern: /^TL-\d{4}-\d{4}$/i,

        init() {
            this.loadRecentSearches();
        },

        loadRecentSearches() {
            try {
                const raw = window.localStorage.getItem(RECENT_SEARCHES_KEY);
                const parsed = JSON.parse(raw || '[]');
                this.recentSearches = Array.isArray(parsed) ? parsed.filter((item) => this.referencePattern.test(String(item))) : [];
            } catch (error) {
                this.recentSearches = [];
            }
        },

        saveRecentSearches() {
            try {
                window.localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(this.recentSearches.slice(0, 3)));
            } catch (error) {
                // localStorage may be unavailable in restricted environments.
            }
        },

        pushRecent(reference) {
            const normalized = String(reference || '').trim().toUpperCase();
            if (!normalized) {
                return;
            }

            this.recentSearches = [normalized, ...this.recentSearches.filter((item) => item !== normalized)].slice(0, 3);
            this.saveRecentSearches();
        },

        applyRecent(reference) {
            this.code = String(reference || '').trim().toUpperCase();
            this.search();
        },

        relativeTimeLabel(isoString) {
            if (!isoString) {
                return 'just now';
            }

            const timestamp = new Date(isoString).getTime();
            if (Number.isNaN(timestamp)) {
                return 'just now';
            }

            const diffMinutes = Math.floor((Date.now() - timestamp) / 60000);
            if (diffMinutes <= 0) return 'just now';
            if (diffMinutes === 1) return '1 minute ago';
            if (diffMinutes < 60) return `${diffMinutes} minutes ago`;

            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours === 1) return '1 hour ago';
            if (diffHours < 24) return `${diffHours} hours ago`;

            const diffDays = Math.floor(diffHours / 24);
            if (diffDays === 1) return '1 day ago';
            return `${diffDays} days ago`;
        },

        async copyReference() {
            const value = String(this.result?.ref || this.code || '').trim().toUpperCase();
            if (!value) {
                return;
            }

            const fallbackCopy = () => {
                const helper = document.createElement('textarea');
                helper.value = value;
                helper.setAttribute('readonly', '');
                helper.style.position = 'absolute';
                helper.style.left = '-9999px';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                document.body.removeChild(helper);
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    fallbackCopy();
                }
            } catch (error) {
                fallbackCopy();
            }

            this.copyFeedback = true;
            setTimeout(() => {
                this.copyFeedback = false;
            }, 1400);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Copied!',
                showConfirmButton: false,
                timer: 1400,
                timerProgressBar: true,
            });
        },

        search() {
            const normalized = this.code.trim().toUpperCase();
            if (!normalized) return;

            this.code = normalized;
            this.searching = true;
            this.found = false;
            this.notFound = false;

            setTimeout(() => {
                this.searching = false;

                if (!this.referencePattern.test(normalized)) {
                    this.notFound = true;
                    this.result = null;
                    return;
                }

                const store = window.AAISDemoStore;
                const payload = store ? store.getTrackerPayload(normalized) : null;

                if (payload) {
                    payload.lastUpdatedLabel = this.relativeTimeLabel(payload.updatedAt);
                    this.result = payload;
                    this.found = true;
                    this.pushRecent(normalized);
                    return;
                }

                this.result = null;
                this.notFound = true;
            }, 450);
        },

        reset() {
            this.code = '';
            this.searchFocused = false;
            this.found = false;
            this.notFound = false;
            this.result = null;
        },
    };
}
</script>
@endpush
