{{-- Room detail modal — opened by x-booking.cards.room via the
     `open-room-detail` window event. Needs the page's $roomTypes. --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('roomModal', () => ({
            isOpen: false,
            room: null,
            rooms: @json($roomTypes),
            openRoom(roomId) {
                this.room = this.rooms[roomId] ?? null;
                if (this.room) {
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                }
            },
            close() {
                this.isOpen = false;
                document.body.style.overflow = '';
            },
            isFullyBooked() {
                if (!this.room) return false;
                const data = window.LAST_AVAILABILITY;
                if (!data || !data.summary) return false;
                const typeSummary = data.summary.find(s => s.room_type === this.room.id);
                return typeSummary ? typeSummary.available <= 0 : false;
            },
            bookThis() {
                const roomId = this.room ? this.room.id : null;
                if (!roomId) return;
                if (this.isFullyBooked()) {
                    alert('This room type is fully booked for the selected dates.');
                    return;
                }
                const checkIn = document.getElementById('widget_check_in').value;
                const checkOut = document.getElementById('widget_check_out').value;
                const guests = document.getElementById('widget_guests').value;
                let url = `/checkout?room_type=${roomId}`;
                if (checkIn) url += `&check_in=${checkIn}`;
                if (checkOut) url += `&check_out=${checkOut}`;
                if (guests) url += `&guests=${guests}`;
                window.location.href = url;
            }
        }));
    });
</script>
<div
    x-data="roomModal"
    @open-room-detail.window="openRoom($event.detail.roomId)"
    @keydown.escape.window="if(isOpen) close()"
>
    <!-- Backdrop -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-out duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[998] bg-black/70 backdrop-blur-sm"
        @click="close()"
        style="display:none;"
    ></div>

    <!-- Panel -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-400"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-out duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed inset-0 z-[999] flex items-end justify-center overflow-y-auto p-0 sm:items-center sm:p-6"
        style="display:none;"
    >
        <div
            @click.stop
            x-show="room"
            class="relative flex max-h-screen w-full flex-col overflow-hidden border border-white/10 bg-night-2 sm:max-h-[90vh] sm:max-w-3xl sm:rounded-[2rem]"
            style="box-shadow: var(--shadow-night-float)"
        >
            <!-- Hero Image -->
            <div class="relative h-64 flex-shrink-0 overflow-hidden bg-night sm:h-72">
                <img :src="room ? '{{ asset('/') }}' + room.image : ''" class="h-full w-full object-cover brightness-[0.9]" :alt="room ? room.title : ''">
                <div class="absolute inset-0 bg-linear-to-t from-night-2 via-night/20 to-transparent"></div>

                <template x-if="room && room.badge">
                    <span class="absolute top-4 left-4 rounded-full bg-gold px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-night shadow-lg" x-text="room.badge"></span>
                </template>

                <button type="button" @click="close()" aria-label="Close room details" class="absolute top-4 right-4 z-10 grid h-9 w-9 place-items-center rounded-full bg-black/40 text-bone backdrop-blur-md transition-colors hover:bg-black/60 cursor-pointer">
                    <x-booking.ui.icon name="x" class="h-4 w-4" />
                </button>

                <div class="absolute bottom-0 left-0 right-0 px-6 pb-5 pt-10">
                    <p class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.28em] text-gold">
                        <x-booking.ui.icon name="map-pin" class="h-3.5 w-3.5" />
                        <span x-text="room ? room.floor : ''"></span>
                    </p>
                    <h2 class="font-display text-3xl leading-tight text-bone" x-text="room ? room.title : ''"></h2>
                </div>
            </div>

            <!-- Scrollable Body -->
            <div class="custom-scrollbar flex-1 overflow-y-auto">
                <div class="space-y-6 px-6 py-6">
                    <!-- Price + Capacity -->
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Room rate</p>
                            <p class="mt-1 font-display text-3xl leading-none text-ink">₱<span x-text="room ? Number(room.price).toLocaleString() : ''"></span></p>
                            <p class="mt-1 text-[10px] uppercase tracking-[0.22em] text-ink/45">per night</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Capacity</p>
                            <p class="mt-1 text-sm font-semibold text-ink" x-text="room ? room.capacity : ''"></p>
                            <p class="mt-1 flex items-center justify-end gap-1 text-[10px] uppercase tracking-[0.22em] text-ink/45">
                                <x-booking.ui.icon name="users" class="h-3 w-3" />
                                <span x-text="room ? room.beds + ' pax max' : ''"></span>
                            </p>
                        </div>
                    </div>

                    <span aria-hidden="true" class="block h-px w-12 bg-gold"></span>

                    <!-- Description -->
                    <p x-show="room && room.description" class="text-pretty text-sm leading-relaxed text-ink/60" x-text="room ? room.description : ''"></p>

                    <!-- Amenities -->
                    <div x-show="room && room.amenities && room.amenities.length > 0">
                        <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">Room features</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="amenity in (room ? room.amenities : [])" :key="amenity.label">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 px-3 py-1.5 text-[11px] font-medium text-ink/85 ring-1 ring-white/10">
                                    <span class="h-1 w-1 rounded-full bg-gold"></span>
                                    <span x-text="amenity.label"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- What's Included -->
                    <div x-show="room && room.includes && room.includes.length > 0">
                        <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-bone/45">What's included</p>
                        <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="item in (room ? room.includes : [])" :key="item">
                                <li class="flex items-center gap-2 text-sm font-medium text-ink/80">
                                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-gold/15 text-gold">
                                        <x-booking.ui.icon name="check" class="h-3 w-3" />
                                    </span>
                                    <span x-text="item"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Policies -->
                    <div class="rounded-2xl border border-gold/25 bg-gold/10 px-5 py-4">
                        <p class="mb-2.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.28em] text-ink/75">
                            <x-booking.ui.icon name="clock" class="h-3.5 w-3.5 text-gold" />
                            Stay policies
                        </p>
                        <div class="grid grid-cols-1 gap-y-1.5 text-xs font-medium text-ink/75 sm:grid-cols-2">
                            <span>Check-in · 2:00 PM</span>
                            <span>Check-out · 12:00 NN</span>
                            <span class="sm:col-span-2">Outside food is not allowed in the rooms</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky footer CTAs -->
            <div class="sticky bottom-0 z-20 flex gap-3 border-t border-white/10 bg-night-2/95 px-6 py-5 backdrop-blur-xl">
                <button type="button" @click="close()" class="press focus-ring flex-1 rounded-full border border-white/15 bg-white/5 px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-ink/70 hover:bg-white/10 cursor-pointer">Close</button>
                <button type="button" @click="bookThis()" :disabled="isFullyBooked()" :class="isFullyBooked() ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''" class="press focus-ring flex-[2] inline-flex items-center justify-center gap-2 rounded-full bg-bone px-6 py-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-night cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                    <x-booking.ui.icon name="calendar" class="h-4 w-4" />
                    <span x-text="isFullyBooked() ? 'Fully Booked' : 'Book this room'">Book this room</span>
                </button>
            </div>
        </div>
    </div>
</div>
