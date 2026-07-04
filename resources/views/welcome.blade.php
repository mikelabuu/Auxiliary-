@extends('layouts.guest')
@section('title', 'Farmers Hostel | Book now')
@section('content')

    <style>
        /* Theme Overrides for Agricultural/Academic feel */
        :root {
            --color-clsu-green: #0a4f2d; /* Deep Forest Green */
            --color-clsu-green-light: #12663c;
            --color-clsu-gold: #d4af37; /* Wheat/Gold */
            --color-linen: #fdfcf8;
            --font-serif: 'Lora', 'Playfair Display', serif;
        }

        /* Fallback for JS-controlled display states */
        .d-none { display: none !important; }
        
        /* Custom styles for room number tiles */
        .room-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }
        .room-tile {
            padding: 10px 6px;
            text-align: center;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }
        .room-tile:hover {
            border-color: #cbd5e1;
            background-color: #f1f5f9;
        }
        .room-tile.available {
            border-color: #bbf7d0;
            background-color: #f0fdf4;
            color: #15803d;
        }
        .swiper-button-next-custom::after,
        .swiper-button-prev-custom::after {
            display: none;
        }
        .flatpickr-calendar {
            box-shadow: var(--shadow-md) !important;
            border: 1px solid var(--color-slate-100) !important;
            border-radius: 1rem !important;
        }
        @keyframes slow-zoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }
        .room-tile.available:hover {
            background-color: #dcfce7;
            border-color: #86efac;
        }
        .room-tile.selected {
            background-color: var(--color-nautical-teal) !important;
            color: #ffffff !important;
            border-color: var(--color-nautical-teal) !important;
            box-shadow: 0 4px 12px rgba(8, 78, 114, 0.3);
        }
        .room-tile.booked {
            background-color: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .room-tile.cleaning {
            background-color: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .room-tile.maintenance {
            background-color: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.6;
            text-decoration: line-through;
        }
    </style>

    <!-- ====== HERO SECTION ====== -->
    <section id="firstsection" class="relative min-h-[95vh] pb-16 pt-40 flex flex-col items-center justify-center overflow-hidden" style="background-color: var(--color-clsu-green);">
        
        <!-- Hero Background Image & Overlays -->
        <div class="absolute inset-0 z-0 overflow-hidden" style="background-color: var(--color-clsu-green);">
            <img src="{{ asset('image/hostel1.jpg') }}" class="w-full h-full object-cover opacity-50" style="transform-origin: center; animation: slow-zoom 20s ease-in-out infinite alternate; filter: contrast(1.1) brightness(0.9);" alt="Farmers Hostel" />
            <!-- Structured Glassmorphic Backdrop for Contrast -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/30 to-[#0a4f2d]/80"></div>
            <!-- Additional vignette for text focus -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(0,0,0,0.5)_100%)]"></div>
        </div>

        <!-- Welcome Text -->
        <div class="relative z-10 text-center max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6 mt-10 sm:mt-0">
            <div class="flex justify-center mb-6 animate-[fade-in-up_0.8s_ease-out_both]">
                <span class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full text-[11px] font-bold text-[#0a4f2d] bg-[#d4af37] shadow-lg border border-[#d4af37]/50 uppercase tracking-[0.18em]">
                    <span class="material-icons text-[14px]">school</span>
                    A Premium Stay on Campus
                </span>
            </div>
            
            <h1 class="text-6xl sm:text-7xl md:text-[88px] text-white tracking-tight leading-[1.05] text-balance animate-[fade-in-up_1s_ease-out_0.2s_both] drop-shadow-[0_4px_16px_rgba(0,0,0,0.8)]" style="font-family: var(--font-serif); font-weight: 700;">
                Welcome to <br class="hidden sm:block" />
                <span class="text-[#d4af37] drop-shadow-[0_4px_24px_rgba(212,175,55,0.3)]" style="font-style: italic;">Farmers</span> Hostel
            </h1>
            
            <p class="text-xl sm:text-[22px] font-medium text-slate-100 max-w-3xl mx-auto leading-relaxed mt-8 animate-[fade-in-up_1s_ease-out_0.4s_both] drop-shadow-md">
                Experience unparalleled comfort and convenience directly within the Central Luzon State University agricultural research campus.
            </p>
        </div>

        <!-- Structured Booking Widget -->
        <div class="relative z-20 mt-16 w-full max-w-5xl px-4 sm:px-6 animate-[fade-in-up_1s_ease-out_0.6s_both]">
            <div class="bg-white p-3 rounded-[2rem] sm:rounded-full shadow-[0_30px_60px_rgba(0,0,0,0.5)] border border-white w-full flex flex-col sm:flex-row items-center justify-between transition-all gap-2">
                <div class="flex-1 w-full px-7 py-4 sm:py-3.5 border-b sm:border-b-0 sm:border-r border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors group sm:rounded-l-full relative flex items-center justify-between" onclick="document.getElementById('widget_check_in').click()">
                    <div class="flex-1">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-1 group-hover:text-[#0a4f2d] transition-colors">Check In</label>
                        <input type="text" class="flatpickr-date w-full bg-transparent text-[18px] font-bold text-slate-900 outline-none placeholder-slate-400 cursor-pointer" placeholder="dd/mm/yyyy" id="widget_check_in">
                    </div>
                    <span class="material-icons text-slate-400 group-hover:text-[#0a4f2d] transition-colors ml-2 pointer-events-none">calendar_today</span>
                </div>
                <div class="flex-1 w-full px-7 py-4 sm:py-3.5 border-b sm:border-b-0 sm:border-r border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors group relative flex items-center justify-between" onclick="document.getElementById('widget_check_out').click()">
                    <div class="flex-1">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-1 group-hover:text-[#0a4f2d] transition-colors">Check Out</label>
                        <input type="text" class="flatpickr-date w-full bg-transparent text-[18px] font-bold text-slate-900 outline-none placeholder-slate-400 cursor-pointer" placeholder="dd/mm/yyyy" id="widget_check_out">
                    </div>
                    <span class="material-icons text-slate-400 group-hover:text-[#0a4f2d] transition-colors ml-2 pointer-events-none">calendar_today</span>
                </div>
                <div class="flex-[0.8] w-full px-7 py-4 sm:py-3.5 border-b sm:border-b-0 sm:border-r border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors group relative flex flex-col justify-center select-none" id="widget_guests_container">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] mb-1 group-hover:text-[#0a4f2d] transition-colors flex items-center gap-1">
                        <span class="material-icons text-[14px]">people</span> Guests
                    </label>
                    <div class="flex items-center justify-between mt-1 w-full">
                        <span id="guests_display" class="text-[18px] font-bold text-slate-900">1</span>
                        <input type="hidden" id="widget_guests" value="1">
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn_minus_guests" class="w-7 h-7 rounded-full border border-slate-300 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:border-slate-400 hover:text-slate-800 transition-all focus:outline-none">
                                <span class="material-icons text-[16px] font-bold">remove</span>
                            </button>
                            <button type="button" id="btn_plus_guests" class="w-7 h-7 rounded-full border border-slate-300 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:border-slate-400 hover:text-slate-800 transition-all focus:outline-none">
                                <span class="material-icons text-[16px] font-bold">add</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="w-full sm:w-auto mt-4 sm:mt-0 px-2 sm:px-0">
                    <button type="button" onclick="document.getElementById('rooms').scrollIntoView({ behavior: 'smooth' });" class="w-full sm:w-auto h-[64px] px-10 rounded-full relative group inline-flex items-center justify-center text-white shadow-[0_10px_25px_rgba(10,79,45,0.4)] hover:shadow-[0_15px_35px_rgba(10,79,45,0.5)] transition-all duration-300 hover:scale-[1.02] active:scale-95" style="background-color: var(--color-clsu-green);">
                        <span class="text-[16px] font-bold tracking-wide flex items-center gap-2">
                            Search Rooms <span class="material-icons text-[18px] transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subtle Scroll Indicator -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex flex-col items-center gap-2 animate-[fade-in-up_1s_ease-out_1s_both]">
            <span class="text-[9px] font-bold text-white/50 uppercase tracking-[0.2em]">Scroll</span>
            <div class="w-px h-12 bg-gradient-to-b from-white/40 to-transparent"></div>
        </div>
    </section>

    <!-- ====== FEATURES SECTION ====== -->
    <section class="py-24 bg-white-canvas relative z-10 border-t border-slate-100/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <x-booking.feature-card
                    icon="school"
                    title="Heart of Campus"
                    description="Located directly inside CLSU, offering unparalleled convenience for visiting researchers, alumni, and guests."
                    data-aos="fade-up"
                    data-aos-delay="100"
                />
                <x-booking.feature-card
                    icon="security"
                    title="24/7 Security"
                    description="Enjoy peace of mind with round-the-clock campus security and dedicated hostel staff always on duty."
                    data-aos="fade-up"
                    data-aos-delay="200"
                />
                <x-booking.feature-card
                    icon="wifi"
                    title="Modern Amenities"
                    description="Stay connected and comfortable with high-speed Wi-Fi, air-conditioned rooms, and inclusive guest kits."
                    data-aos="fade-up"
                    data-aos-delay="300"
                />
            </div>
        </div>
    </section>

    <!-- ====== ROOMS GRID SECTION ====== -->
    <section id="rooms" class="py-24 bg-white-canvas relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-booking.section-heading
                eyebrow="Choose Your Stay"
                title="Reserve A Room Now"
                description="Select from our range of cozy, fully-serviced rooms. Ideal for short stays, transient guests, and university researchers."
                data-aos="fade-up"
            />

            <!-- Error List -->
            @if ($errors->any())
                <div class="max-w-3xl mx-auto mb-8">
                    <x-booking.alert type="danger">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-booking.alert>
                </div>
            @endif

            <!-- Room Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach (config('room_types', []) as $idx => $type)
                    <div data-aos="fade-up" data-aos-delay="{{ ($idx + 1) * 100 }}">
                        <x-booking.room-card
                            :title="$type['title']"
                            :beds="$type['beds']"
                            :price="$type['price']"
                            :typeId="$type['id']"
                            :image="$type['image']"
                            :capacity="$type['capacity']"
                            :badge="$type['badge'] ?? null"
                            :amenities="$type['amenities'] ?? []"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ====== TESTIMONIALS SECTION ====== -->
    <section class="py-24 overflow-hidden border-t border-slate-200" style="background-color: var(--color-linen);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6" data-aos="fade-up">
                <div class="max-w-2xl">
                    <span class="font-bold tracking-[0.14em] uppercase text-[10px] mb-3 block" style="color: var(--color-clsu-green);">Guest Experiences</span>
                    <h2 class="text-3xl sm:text-[44px] font-bold text-slate-900 tracking-tight leading-[1.08]" style="font-family: var(--font-serif);">Loved by academics and travelers alike.</h2>
                </div>
                <div class="flex gap-3">
                    <button class="swiper-button-prev-custom w-12 h-12 rounded-full border border-slate-300 flex items-center justify-center hover:bg-white hover:shadow-md transition-all cursor-pointer">
                        <span class="material-icons text-slate-600 text-[20px]">arrow_back</span>
                    </button>
                    <button class="swiper-button-next-custom w-12 h-12 rounded-full border border-slate-300 flex items-center justify-center hover:bg-white hover:shadow-md transition-all cursor-pointer">
                        <span class="material-icons text-slate-600 text-[20px]">arrow_forward</span>
                    </button>
                </div>
            </div>

            <!-- Swiper -->
            <div class="swiper testimonials-swiper !overflow-visible" data-aos="fade-up" data-aos-delay="200">
                <div class="swiper-wrapper">
                    <div class="swiper-slide w-full md:w-[400px]">
                        <x-booking.testimonial-card
                            quote="Perfect location for my research week at CLSU. The rooms are incredibly clean, the Wi-Fi is reliable, and being right on campus saved me hours of commute time."
                            name="Dr. Reyes"
                            role="Visiting Professor"
                            initials="DR"
                            :rating="5"
                        />
                    </div>
                    <div class="swiper-slide w-full md:w-[400px]">
                        <x-booking.testimonial-card
                            quote="The staff is exceptionally accommodating. We booked the dormitory room for our student organization retreat and the facilities exceeded our expectations."
                            name="Maria C."
                            role="Student Leader"
                            initials="MC"
                            :rating="5"
                        />
                    </div>
                    <div class="swiper-slide w-full md:w-[400px]">
                        <x-booking.testimonial-card
                            quote="Very peaceful environment. It's surrounded by nature, making it a great place to rest after a long day of meetings. The breakfast options were also a nice touch."
                            name="Juan P."
                            role="Government Official"
                            initials="JP"
                            :rating="5"
                        />
                    </div>
                    <div class="swiper-slide w-full md:w-[400px]">
                        <x-booking.testimonial-card
                            quote="I highly recommend the Deluxe Room. It felt very premium, and the hot shower was perfect. Will definitely book here again next year."
                            name="Alumni Sy"
                            role="CLSU Alumni"
                            initials="AS"
                            :rating="5"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== GALLERY SECTION (MASONRY) ====== -->
    <section class="py-24 bg-white-canvas border-t border-slate-100/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-booking.section-heading
                eyebrow="Visual Tour"
                title="Our Gallery"
                description="Take a virtual tour of our rooms, common spaces, dining hall, and hostel facilities."
                data-aos="fade-up"
            />

            <!-- CSS Masonry Grid -->
            <div class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-4">
                @for ($i = 1; $i <= 12; $i++)
                    <x-booking.gallery-item
                        :image="'image/gallery/' . $i . '.jpg'"
                        :alt="'Farmers Hostel Gallery ' . $i"
                        data-aos="fade-up"
                        data-aos-delay="{{ ($i % 4) * 100 }}"
                    />
                @endfor
            </div>
        </div>
    </section>

    <!-- Booking modal moved to checkout page -->

    <!-- ====== MOBILE STICKY BOOK NOW BAR ====== -->
    <div id="mobileStickyBar" class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200 p-4 shadow-[0_-10px_20px_rgba(0,0,0,0.05)] z-40 transform translate-y-full transition-transform duration-300 md:hidden flex justify-between items-center">
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Starting from</p>
            <p class="text-lg font-black" style="color: var(--color-clsu-green);">₱1,600 <span class="text-xs font-semibold text-slate-500">/ night</span></p>
        </div>
        <button type="button" onclick="document.getElementById('rooms').scrollIntoView({ behavior: 'smooth' });" class="px-6 py-3 rounded-xl text-white text-sm font-black tracking-wide shadow-md cursor-pointer hover:opacity-90" style="background-color: var(--color-clsu-green);">
            Book Now
        </button>
    </div>

    <!-- ====== ROOM DETAIL MODAL ====== -->
    @php
        $roomsJson = collect(config('room_types', []))->keyBy('id')->toJson();
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('roomModal', () => ({
                isOpen: false,
                room: null,
                rooms: @json(collect(config('room_types', []))->keyBy('id')),
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
                bookThis() {
                    const roomId = this.room ? this.room.id : null;
                    if (!roomId) return;
                    
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
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[998] bg-slate-900/70 backdrop-blur-sm"
            @click="close()"
        ></div>

        <!-- Panel -->
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="fixed inset-0 z-[999] overflow-y-auto flex items-end sm:items-center justify-center p-0 sm:p-6"
        >
            <div
                @click.stop
                x-show="room"
                class="bg-white w-full sm:max-w-3xl sm:rounded-[2.5rem] overflow-hidden shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] relative border border-slate-200/60 flex flex-col max-h-screen sm:max-h-[90vh]"
            >
                <!-- Hero Image -->
                <div class="relative h-64 sm:h-72 flex-shrink-0 bg-slate-200 overflow-hidden">
                    <img :src="room ? '{{ asset('/') }}' + room.image : ''" class="w-full h-full object-cover" :alt="room ? room.title : ''">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/10 to-transparent"></div>

                    <!-- Badge -->
                    <template x-if="room && room.badge">
                        <span class="absolute top-4 left-4 bg-accent/90 backdrop-blur-md text-slate-900 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.15em] shadow-lg" x-text="room.badge"></span>
                    </template>

                    <!-- Close button -->
                    <button type="button" @click="close()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md text-white flex items-center justify-center transition-colors cursor-pointer z-10">
                        <span class="material-icons text-[18px]">close</span>
                    </button>

                    <!-- Bottom info overlay -->
                    <div class="absolute bottom-0 left-0 right-0 px-6 pb-5 pt-10 bg-gradient-to-t from-black/70 to-transparent">
                        <p class="text-white/70 text-[11px] font-bold uppercase tracking-widest flex items-center gap-1 mb-1">
                            <span class="material-icons text-[13px]">location_on</span>
                            <span x-text="room ? room.floor : ''"></span>
                        </p>
                        <h2 class="text-white text-2xl font-black leading-tight" x-text="room ? room.title : ''"></h2>
                    </div>
                </div>

                <!-- Scrollable Body -->
                <div class="overflow-y-auto flex-1 custom-scrollbar">
                    <div class="px-6 py-5 space-y-5">

                        <!-- Price + Capacity row -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Room Rate</p>
                                <p class="text-3xl font-black text-brand leading-none mt-1">
                                    ₱<span x-text="room ? Number(room.price).toLocaleString() : ''"></span>
                                </p>
                                <p class="text-xs font-semibold text-slate-400 mt-0.5">per night</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Capacity</p>
                                <p class="text-sm font-black text-slate-800 mt-1" x-text="room ? room.capacity : ''"></p>
                                <p class="text-xs font-semibold text-slate-400 mt-0.5 flex items-center justify-end gap-0.5">
                                    <span class="material-icons text-[13px]">bed</span>
                                    <span x-text="room ? room.beds + ' pax max' : ''"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div x-show="room && room.description" class="bg-slate-50 rounded-2xl px-4 py-3.5 border border-slate-100">
                            <p class="text-sm text-slate-600 leading-relaxed" x-text="room ? room.description : ''"></p>
                        </div>

                        <!-- Amenities -->
                        <div x-show="room && room.amenities && room.amenities.length > 0">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2.5">Room Features</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="amenity in (room ? room.amenities : [])" :key="amenity.label">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-muted text-brand-dark text-xs font-bold">
                                        <span class="material-icons text-[15px]" x-text="amenity.icon"></span>
                                        <span x-text="amenity.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- What's Included -->
                        <div x-show="room && room.includes && room.includes.length > 0">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2.5">What's Included</p>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <template x-for="item in (room ? room.includes : [])" :key="item">
                                    <li class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                            <span class="material-icons text-[13px]">check</span>
                                        </span>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Policies -->
                        <div class="bg-amber-50 rounded-2xl px-4 py-3.5 border border-amber-100">
                            <p class="text-[11px] font-black text-amber-700 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                <span class="material-icons text-[14px]">policy</span>
                                Stay Policies
                            </p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs font-semibold text-amber-900">
                                <span class="flex items-center gap-1.5"><span class="material-icons text-[13px] text-amber-500">login</span> Check-in: 2:00 PM</span>
                                <span class="flex items-center gap-1.5"><span class="material-icons text-[13px] text-amber-500">logout</span> Check-out: 12:00 PM</span>
                                <span class="flex items-center gap-1.5 col-span-2"><span class="material-icons text-[13px] text-amber-500">no_food</span> Outside food not allowed in rooms</span>
                            </div>
                        </div>

                    </div>
                </div>

                    <div class="px-8 py-5 bg-white/90 backdrop-blur-2xl border-t border-slate-100 flex gap-3 sticky bottom-0 z-20">
                        <button type="button" @click="close()" class="flex-1 px-6 py-3 rounded-full text-sm font-bold bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 transition-all cursor-pointer">Close</button>
                        <button type="button" @click="bookThis()" class="flex-[2] px-6 py-3 rounded-full text-sm font-bold text-white shadow-[0_4px_14px_0_rgba(10,79,45,0.39)] hover:shadow-[0_6px_20px_rgba(10,79,45,0.23)] hover:-translate-y-0.5 transition-all cursor-pointer flex items-center justify-center gap-2" style="background-color: var(--color-clsu-green);">
                            <span class="material-icons text-[16px]">calendar_month</span>
                            Book This Room
                        </button>
                    </div>
            </div>
        </div>
    </div>

    <!-- jQuery for booking script fallback and script inclusion -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/booking.js') }}"></script>

    <!-- Swiper & Scroll Logic -->
    <script>
        function bookRoomDirect(roomId) {
            if (!roomId) return;
            const checkIn = document.getElementById('widget_check_in').value;
            const checkOut = document.getElementById('widget_check_out').value;
            const guests = document.getElementById('widget_guests').value;
            let url = `/checkout?room_type=${roomId}`;
            if (checkIn) url += `&check_in=${checkIn}`;
            if (checkOut) url += `&check_out=${checkOut}`;
            if (guests) url += `&guests=${guests}`;
            window.location.href = url;
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Guests Increment / Decrement controls logic
            const minusBtn = document.getElementById('btn_minus_guests');
            const plusBtn = document.getElementById('btn_plus_guests');
            const display = document.getElementById('guests_display');
            const hiddenInput = document.getElementById('widget_guests');

            if (minusBtn && plusBtn && display && hiddenInput) {
                minusBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    let val = parseInt(hiddenInput.value) || 1;
                    if (val > 1) {
                        val--;
                        hiddenInput.value = val;
                        display.textContent = val;
                    }
                });

                plusBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    let val = parseInt(hiddenInput.value) || 1;
                    val++;
                    hiddenInput.value = val;
                    display.textContent = val;
                });
            }

            // Testimonials Swiper
            new Swiper('.testimonials-swiper', {
                slidesPerView: 1,
                spaceBetween: 24,
                navigation: {
                    nextEl: '.swiper-button-next-custom',
                    prevEl: '.swiper-button-prev-custom',
                },
                breakpoints: {
                    768: {
                        slidesPerView: 'auto',
                    }
                }
            });

            // Mobile Sticky Bar Intersection Observer
            const stickyBar = document.getElementById('mobileStickyBar');
            const heroSection = document.getElementById('firstsection');
            
            if (stickyBar && heroSection) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) {
                            // Hero is out of view, show sticky bar
                            stickyBar.classList.remove('translate-y-full');
                        } else {
                            // Hero is in view, hide sticky bar
                            stickyBar.classList.add('translate-y-full');
                        }
                    });
                }, { threshold: 0.1 });

                observer.observe(heroSection);
            }
        });
    </script>
@endsection
