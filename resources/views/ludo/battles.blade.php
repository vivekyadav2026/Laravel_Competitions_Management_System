@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Battles & Tournaments')

@section('content')
    {{-- Notice Banner --}}
    <x-ludo.announcement>
        {!! $announcement ?? '<span class="fw-bold">⭐ 🙏 🙏 सभी यूजर ध्यान दें:</span> मैच शुरू होने के 15 मिनट के अंदर रूम कोड डालें। किसी भी विवाद की स्थिति में स्क्रीनशॉट अनिवार्य है।' !!}
    </x-ludo.announcement>

    {{-- Create a Battle Section (Screenshot #5 Top Card) --}}
    <div class="ludo-create-battle-card" id="create-battle">
        <div class="ludo-create-battle-header">
            <div class="ludo-create-battle-title">
                <i class="fa-solid fa-gamepad text-warning"></i>
                <span>Create a Battle!</span>
            </div>
            <button class="ludo-rules-btn" type="button" data-bs-toggle="modal" data-bs-target="#ludoRulesModal">
                <span>Rules</span> <i class="fa-solid fa-circle-info ms-1"></i>
            </button>
        </div>

        <form action="{{ route('ludo.battles.create') }}" method="POST" id="createBattleForm">
            @csrf
            <div class="ludo-battle-input-group">
                <span class="text-warning fs-5">🪙</span>
                <input 
                    type="number" 
                    name="amount" 
                    class="ludo-battle-input @error('amount') is-invalid @enderror" 
                    placeholder="Enter points (min {{ $minBattlePoints ?? 50 }})" 
                    value="{{ old('amount', $minBattlePoints ?? 50) }}"
                    min="{{ $minBattlePoints ?? 50 }}" 
                    max="50000"
                    step="50" 
                    required
                >
                <button type="submit" class="ludo-btn-set" id="btnSetBattle">
                    <i class="fa-solid fa-plus small"></i> Set
                </button>
            </div>
            @error('amount')
                <div class="text-danger small mt-2 fw-semibold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                </div>
            @enderror
            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                <span class="text-secondary small" style="font-size: 10.5px;">Virtual Points only • Free Fair Play</span>
                <span class="text-info small" style="font-size: 10.5px;">Est. Win Prize: 190%</span>
            </div>
        </form>
    </div>

    {{-- OPEN BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="OPEN BATTLES" 
        subtitle="Choose your battle and win big" 
    />

    {{-- Open Battles List Container --}}
    <div class="mb-4" id="open-battles-section">
        <div id="open-battles-list" class="d-flex flex-column gap-2" data-initial-cursor="{{ $openBattles->last()?->id ?? 0 }}" data-has-more="{{ $openBattlesHasMore ? 'true' : 'false' }}">
            @forelse($openBattles as $battle)
                @include('ludo.partials.open_battle_card', ['battle' => $battle])
            @empty
                <div id="open-battles-empty">
                    <x-ludo.empty-state 
                        title="No Open Battles Right Now"
                        message="Be the first to challenge other players! Set an entry amount above to create a battle."
                    />
                </div>
            @endforelse
        </div>

        {{-- Open Battles Infinite Scroll Sentinel & Loader --}}
        <div id="open-battles-loader" class="ludo-infinite-status text-center py-2" data-type="open">
            <div class="ludo-loader-spinner d-none py-2">
                <span class="spinner-border spinner-border-sm text-info me-1" role="status" aria-hidden="true"></span>
                <span class="text-info small fw-semibold">Loading more open battles...</span>
            </div>
            <div class="ludo-loader-end {{ !$openBattlesHasMore && count($openBattles) > 0 ? '' : 'd-none' }} py-2 text-secondary small" style="font-size: 11px;">
                <i class="fa-solid fa-flag-checkered me-1 text-warning"></i> You have reached the end of open battles
            </div>
            <div class="ludo-loader-error d-none py-2">
                <span class="text-danger small me-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load battles</span>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 small ludo-retry-btn" style="font-size: 11px;">
                    Retry <i class="fa-solid fa-rotate-right ms-1"></i>
                </button>
            </div>
            <div class="ludo-loader-fallback {{ $openBattlesHasMore ? '' : 'd-none' }} py-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold ludo-load-more-btn" style="font-size: 11px;">
                    Load More Battles <i class="fa-solid fa-chevron-down ms-1"></i>
                </button>
            </div>
            {{-- Sentinel element for IntersectionObserver --}}
            <div class="ludo-sentinel" style="height: 1px; width: 100%;"></div>
        </div>
    </div>

    {{-- RUNNING BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="RUNNING BATTLES" 
        subtitle="Join running battles and compete to win" 
    />

    {{-- Running Battles List Container --}}
    <div class="mb-4" id="running-battles-section">
        <div id="running-battles-list" class="d-flex flex-column gap-2" data-initial-cursor="{{ $runningBattles->last()?->id ?? 0 }}" data-has-more="{{ $runningBattlesHasMore ? 'true' : 'false' }}">
            @forelse($runningBattles as $running)
                @include('ludo.partials.running_battle_card', ['running' => $running])
            @empty
                <div id="running-battles-empty">
                    <x-ludo.empty-state 
                        title="No Matches Currently In Progress"
                        message="Matches will appear here as soon as two players begin a contest."
                    />
                </div>
            @endforelse
        </div>

        {{-- Running Battles Infinite Scroll Sentinel & Loader --}}
        <div id="running-battles-loader" class="ludo-infinite-status text-center py-2" data-type="running">
            <div class="ludo-loader-spinner d-none py-2">
                <span class="spinner-border spinner-border-sm text-info me-1" role="status" aria-hidden="true"></span>
                <span class="text-info small fw-semibold">Loading more running battles...</span>
            </div>
            <div class="ludo-loader-end {{ !$runningBattlesHasMore && count($runningBattles) > 0 ? '' : 'd-none' }} py-2 text-secondary small" style="font-size: 11px;">
                <i class="fa-solid fa-flag-checkered me-1 text-warning"></i> You have reached the end of running battles
            </div>
            <div class="ludo-loader-error d-none py-2">
                <span class="text-danger small me-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load matches</span>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 small ludo-retry-btn" style="font-size: 11px;">
                    Retry <i class="fa-solid fa-rotate-right ms-1"></i>
                </button>
            </div>
            <div class="ludo-loader-fallback {{ $runningBattlesHasMore ? '' : 'd-none' }} py-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold ludo-load-more-btn" style="font-size: 11px;">
                    Load More Battles <i class="fa-solid fa-chevron-down ms-1"></i>
                </button>
            </div>
            {{-- Sentinel element for IntersectionObserver --}}
            <div class="ludo-sentinel" style="height: 1px; width: 100%;"></div>
        </div>
    </div>

    {{-- Rules Modal --}}
    <div class="modal fade" id="ludoRulesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content ludo-modal-dialog">
                <div class="modal-header border-secondary border-opacity-25 pb-2">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fa-solid fa-scroll text-warning me-2"></i> Ludo Tournament Rules
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-secondary small py-3">
                    <ol class="ps-3 mb-0 text-light d-flex flex-column gap-2">
                        <li>The match creator must share the Ludo King Room Code within 5 minutes.</li>
                        <li>Opponent must join within 5 minutes of room code being provided.</li>
                        <li>Take a screenshot of the winning screen for verification.</li>
                        <li>Cancellation or fake results will lead to immediate account suspension.</li>
                        <li>Platform fee is calculated in the winning prize. Battles use non-cash virtual points only.</li>
                    </ol>
                </div>
                <div class="modal-footer border-secondary border-opacity-25 pt-2">
                    <button type="button" class="btn btn-sm btn-info rounded-pill px-4 text-dark fw-bold" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    class BattleInfiniteScroll {
        constructor(config) {
            this.type = config.type; // 'open' | 'running'
            this.listEl = document.getElementById(config.listId);
            this.loaderEl = document.getElementById(config.loaderId);
            this.endpoint = config.endpoint;
            this.perPage = config.perPage || 5;

            if (!this.listEl || !this.loaderEl) {
                return;
            }

            this.spinnerEl = this.loaderEl.querySelector('.ludo-loader-spinner');
            this.endEl = this.loaderEl.querySelector('.ludo-loader-end');
            this.errorEl = this.loaderEl.querySelector('.ludo-loader-error');
            this.fallbackContainer = this.loaderEl.querySelector('.ludo-loader-fallback');
            this.fallbackBtn = this.loaderEl.querySelector('.ludo-load-more-btn');
            this.retryBtn = this.loaderEl.querySelector('.ludo-retry-btn');
            this.sentinelEl = this.loaderEl.querySelector('.ludo-sentinel');
            this.emptyEl = document.getElementById(config.emptyId);

            this.cursor = this.listEl.dataset.initialCursor ? parseInt(this.listEl.dataset.initialCursor, 10) : null;
            this.hasMore = this.listEl.dataset.hasMore === 'true';
            this.isLoading = false;
            this.loadedIds = new Set();

            // Register existing IDs to prevent duplicates
            this.listEl.querySelectorAll('[data-battle-id]').forEach(el => {
                const id = parseInt(el.getAttribute('data-battle-id'), 10);
                if (id) this.loadedIds.add(id);
            });

            this.init();
        }

        init() {
            if (this.fallbackBtn) {
                this.fallbackBtn.addEventListener('click', () => this.fetchNextBatch());
            }

            if (this.retryBtn) {
                this.retryBtn.addEventListener('click', () => {
                    this.hideError();
                    this.fetchNextBatch();
                });
            }

            if (!this.hasMore) {
                this.onEndReached();
                return;
            }

            this.setupIntersectionObserver();
        }

        setupIntersectionObserver() {
            if (!('IntersectionObserver' in window) || !this.sentinelEl) {
                // Fallback to manual button if IntersectionObserver not available
                if (this.fallbackContainer && this.hasMore) {
                    this.fallbackContainer.classList.remove('d-none');
                }
                return;
            }

            this.observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                if (entry && entry.isIntersecting && this.hasMore && !this.isLoading) {
                    this.fetchNextBatch();
                }
            }, {
                root: null,
                rootMargin: '250px',
                threshold: 0.01
            });

            this.observer.observe(this.sentinelEl);
        }

        showLoading() {
            this.isLoading = true;
            if (this.spinnerEl) this.spinnerEl.classList.remove('d-none');
            if (this.fallbackContainer) this.fallbackContainer.classList.add('d-none');
            this.hideError();
        }

        hideLoading() {
            this.isLoading = false;
            if (this.spinnerEl) this.spinnerEl.classList.add('d-none');
        }

        showError() {
            if (this.errorEl) this.errorEl.classList.remove('d-none');
            if (this.fallbackContainer) this.fallbackContainer.classList.add('d-none');
        }

        hideError() {
            if (this.errorEl) this.errorEl.classList.add('d-none');
        }

        onEndReached() {
            this.hasMore = false;
            if (this.observer) {
                this.observer.disconnect();
            }
            if (this.fallbackContainer) {
                this.fallbackContainer.classList.add('d-none');
            }
            if (this.endEl && this.loadedIds.size > 0) {
                this.endEl.classList.remove('d-none');
            }
        }

        async fetchNextBatch() {
            if (this.isLoading || !this.hasMore) return;

            this.showLoading();

            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('type', this.type);
                if (this.cursor) {
                    url.searchParams.set('cursor', this.cursor);
                }
                url.searchParams.set('per_page', this.perPage);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load battles');
                }

                this.cursor = data.next_cursor;
                this.hasMore = Boolean(data.has_more);

                if (data.html && data.html.trim().length > 0) {
                    // Remove empty placeholder if any
                    if (this.emptyEl) {
                        this.emptyEl.remove();
                        this.emptyEl = null;
                    }

                    // Parse returned HTML and append elements
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;

                    // Append while avoiding any duplicate card IDs
                    const children = Array.from(tempDiv.children);
                    children.forEach(child => {
                        const cardId = child.getAttribute('data-battle-id') 
                            ? parseInt(child.getAttribute('data-battle-id'), 10) 
                            : null;

                        if (cardId) {
                            if (!this.loadedIds.has(cardId)) {
                                this.loadedIds.add(cardId);
                                this.listEl.appendChild(child);
                            }
                        } else {
                            // If it's a modal or non-card node associated with the card
                            this.listEl.appendChild(child);
                        }
                    });
                }

                this.hideLoading();

                if (!this.hasMore) {
                    this.onEndReached();
                } else {
                    // Show fallback button as alternative if user stops scrolling
                    if (this.fallbackContainer) {
                        this.fallbackContainer.classList.remove('d-none');
                    }
                }
            } catch (error) {
                console.error(`Error loading more ${this.type} battles:`, error);
                this.hideLoading();
                this.showError();
            }
        }
    }

    // Initialize Open Battles Infinite Scroller
    const openBattlesScroller = new BattleInfiniteScroll({
        type: 'open',
        listId: 'open-battles-list',
        loaderId: 'open-battles-loader',
        emptyId: 'open-battles-empty',
        endpoint: "{{ route('ludo.battles.loadMore') }}",
        perPage: 5
    });

    // Initialize Running Battles Infinite Scroller
    const runningBattlesScroller = new BattleInfiniteScroll({
        type: 'running',
        listId: 'running-battles-list',
        loaderId: 'running-battles-loader',
        emptyId: 'running-battles-empty',
        endpoint: "{{ route('ludo.battles.loadMore') }}",
        perPage: 5
    });

    // Double-submit prevention for Battle Creation
    const createBattleForm = document.getElementById('createBattleForm');
    const btnSetBattle = document.getElementById('btnSetBattle');
    if (createBattleForm && btnSetBattle) {
        createBattleForm.addEventListener('submit', function (e) {
            if (btnSetBattle.disabled) {
                e.preventDefault();
                return;
            }
            btnSetBattle.disabled = true;
            btnSetBattle.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Setting...';
        });
    }
});
</script>
@endpush
