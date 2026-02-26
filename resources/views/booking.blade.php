@extends('layouts.booking_layout')
@section('title', 'Farmers Hostel | Book now')
@section('page-title', 'Book Now!')

@section('content')
    <section id="firstsection" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
        <!-- Welcome text overlay -->
        <div class="welcomeline">
            <h1 class="welcometag">Welcome to Farmers Hostel</h1>
        </div>

        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: url('{{ asset('image/4.jpg') }}');"></div>
            <div class="carousel-item" style="background-image: url('{{ asset('image/2.jpg') }}');"></div>
            <div class="carousel-item" style="background-image: url('{{ asset('image/1.jpg') }}');"></div>
            <div class="carousel-item" style="background-image: url('{{ asset('image/3.jpg') }}');"></div>
            
        </div>

        <!-- Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#firstsection" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#firstsection" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>

        <!-- Indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#firstsection" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#firstsection" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#firstsection" data-bs-slide-to="2"></button>
            <button type="button" data-bs-target="#firstsection" data-bs-slide-to="3"></button>
        </div>
    </section>

    <!-- Room Selection Section (unchanged cards but keep data-room-type-id values matching select options) -->
    <section id="rooms" class="py-5 bg-light">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="container">
            <h2 class="text-center mb-4 fw-bold">Reserve A Room Now</h2>
            <div class="row g-4">
                <!-- Double Room -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/double.jpg') }}" class="card-img-top" alt="Double Room">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Double Room</h5>
                            <p class="card-text">2 Single Beds (2pax)</p>
                            <p class="price fw-bold">₱1,600 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>

                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Double Room"
                                data-beds="2"
                                data-price="1600"
                                data-room-type-id="double"
                            >Book Now</a>
                        </div>
                    </div>
                </div>

                <!-- Triple Room -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/triple.jpg') }}" class="card-img-top" alt="Triple Room">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Triple Room</h5>
                            <p class="card-text">3 Single Beds (3pax)</p>
                            <p class="price fw-bold">₱2,100 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>

                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Triple Room"
                                data-beds="3"
                                data-price="2100"
                                data-room-type-id="triple"
                            >Book Now</a>
                        </div>
                    </div>
                </div>
                <!-- Quadruple Room -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/quadruple.jpg') }}" class="card-img-top" alt="Quadruple Room">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Quadruple Room</h5>
                            <p class="card-text">1 Bunk Bed & 2 Single Beds (4pax)</p>
                            <p class="price fw-bold">₱2,400 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>

                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Quadruple Room"
                                data-beds="4"
                                data-price="2400"
                                data-room-type-id="quadruple"
                            >Book Now</a>
                        </div>
                    </div>
                </div>
                <!-- Deluxe Room -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/deluxe.jpg') }}" class="card-img-top" alt="Deluxe Room">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Deluxe Room</h5>
                            <p class="card-text">1 Queen Size Bed (2pax)</p>
                            <p class="price fw-bold">₱2,500 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>
                            
                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Deluxe Room"
                                data-beds="2"
                                data-price="2500"
                                data-room-type-id="deluxe"
                            >Book Now</a>
                        </div>
                    </div>
                </div>
                <!-- Dormitory Room I -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/dormitory1.jpg') }}" class="card-img-top" alt="Dormitory Room I">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Dormitory Room I</h5>
                            <p class="card-text">2 Bunk Beds & 1 Single Bed (5pax)</p>
                            <p class="price fw-bold">₱2,500 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>
                            
                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Dormitory I Room"
                                data-beds="5"
                                data-price="2500"
                                data-room-type-id="dormitory1"
                            >Book Now</a>
                        </div>
                    </div>
                </div>
                <!-- Dormitory Room II -->
                <div class="col-md-4">
                    <div class="card room-card shadow-sm h-100">
                        <img src="{{ asset('image/dormitory2.jpg') }}" class="card-img-top" alt="Dormitory Room II">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Dormitory Room II</h5>
                            <p class="card-text">3 Bunk Beds (6pax)</p>
                            <p class="price fw-bold">₱3,000 / night</p>

                            <ul class="amenities list-unstyled mt-2">
                                <li><i class="fa-solid fa-wifi"></i> Free Wi-Fi</li>
                                <li><i class="fa-solid fa-temperature-high"></i> Hot & Cold Shower</li>
                                <li><i class="fa-solid fa-box"></i> Guest Kit</li>
                                <li><i class="fa-solid fa-ice-cream"></i> Mini Fridge</li>
                            </ul>

                            <a href="#" class="book-btn mt-auto btn-open-booking"
                                data-room-type="Dormitory II Room"
                                data-beds="6"
                                data-price="3000"
                                data-room-type-id="dormitory2"
                            >Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ====== GALLERY SECTION ====== -->
    <section class="gallery-section">
        <h2 class="section-title">Gallery</h2>
        <div class="gallery-grid">
            <a href="{{ asset('image/gallery/1.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/1.jpg') }}" alt="Hotel View">
            </a>
            <a href="{{ asset('image/gallery/2.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/2.jpg') }}" alt="Room Interior">
            </a>
            <a href="{{ asset('image/gallery/3.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/3.jpg') }}" alt="Dining Area">
            </a>
            <a href="{{ asset('image/gallery/4.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/4.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/5.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/5.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/6.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/6.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/7.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/7.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/8.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/8.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/9.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/9.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/10.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/10.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/11.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/11.jpg') }}" alt="Lobby">
            </a>
            <a href="{{ asset('image/gallery/12.jpg') }}" data-lightbox="hotel-gallery">
                <img src="{{ asset('image/gallery/12.jpg') }}" alt="Lobby">
            </a>
        </div>
    </section>

   <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="bookingForm" method="POST" action="{{ route('booking.store') }}"> <!-- Removed the enctype -->
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Reserve Your Room(s)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <!-- GUEST INFO -->
                    <section class="mb-4">
                        <h5 class="fw-semibold">Guest Information</h5>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                        
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Suffix <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="suffix" class="form-control">
                            </div>
                        
                            <div class="col-md-6">
                                <label class="form-label">Contact number</label>
                                <input type="tel" name="guest_phone" id="guest_phone" class="form-control" 
                                       inputmode="numeric" pattern="^(09|\+639)\d{9}$" maxlength="13" required>
                            </div>
                            <!--Address Info (livewire)-->
                            <hr class="divider">
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <livewire:address-selector />
                            </div>
                            <hr class="divider">
                            <div class="col-md-6">
                                <label class="form-label" for="expected_guests">Total No. of Guests</label>
                                <input type="number" id="expected_guests" name="expected_guests" class="form-control" min="1" value="1" required>
                            </div>
                            
                            <div class="col-md-6">
                                <br>
                                <div class="form-control-plaintext" id="maxSeniorsLabelDisplay">Max seniors/pwd: <span id="maxSeniorsLabel">0</span></div>
                            </div>

                            <div class="form-group mt-3">
                                <label>
                                <input type="checkbox" id="request_discount" name="request_discount" value="1">
                                I want to request a 20% discount per senior/pwd
                                </label>
                            </div>
                            <hr class="divider">
                        </div>
                    </section>
                    <!-- RESERVATION INFO -->
                    <section class="mb-4">
                        <h5 class="fw-semibold">Room Reservation</h5>
                        <p class="small text-muted mb-2">Check-in time: 2:00 PM · Check-out time: 12:00 NN</p>


                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Check-in date</label>
                                <input type="date" name="check_in" id="check_in" class="form-control" required>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">Check-out date</label>
                                <input type="date" name="check_out" id="check_out" class="form-control" required>
                            </div>
                        </div>

                            <!-- Dynamic reservation blocks container -->
                        <div id="reservationBlocksContainer">
                        
                        </div>
                        <div class="mt-2">
                            <button type="button" id="btnAddRoom" class="btn btn-sm btn-outline-primary">+ Add Room</button>
                        </div>
                    </section>
                    <!-- Hidden Values -->
                    <section class="mb-4">
                        <!-- Hidden inputs to store server data -->
                        <input type="hidden" name="room_numbers" id="selected_room_number">
                        <input type="hidden" name="num_seniors" id="num_seniors" value="0">
                    </section>
                    <!-- Validation / Error message container -->
                    <div id="bookingFormAlert" class="alert alert-danger d-none"></div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btnSubmitBooking" class="book-btn">Confirm Reservation</button>
                    </div>
                </form>
            </div><!-- .modal-content -->
        </div><!-- .modal-dialog -->
    </div><!-- .modal -->

    <template id="reservationBlockTemplate">
        <div class="reservation-block border p-3 mb-3" data-index="__INDEX__">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label>Room type</label>
                    <select name="reservations[__INDEX__][room_type]" class="form-control room-type-select" required>
                        <option value="">Select room type</option>
                        <option value="double" data-beds="2" data-price="1600">Double Room (2 pax)</option>
                        <option value="triple" data-beds="3" data-price="2100">Triple Room (3 pax)</option>
                        <option value="quadruple" data-beds="4" data-price="2400">Quadruple Room (4 pax)</option>
                        <option value="deluxe" data-beds="2" data-price="2500">Deluxe Room (2 pax)</option>
                        <option value="dormitory1" data-beds="5" data-price="2500">Dormitory I (5 pax)</option>
                        <option value="dormitory2" data-beds="6" data-price="3000">Dormitory II (6 pax)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Capacity:</label>
                    <input type="number" name="reservations[__INDEX__][beds]" class="form-control res-beds" readonly>
                </div>
                <div class="col-md-3">
                    <label>Price / night</label>
                    <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
                    <input type="text" class="form-control res-price" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">No. of Guest(s)</label>
                    <input type="number" name="reservations[__INDEX__][num_guests]" class="form-control res-num-guests" min="1" required>
                    <small class="capacity-hint text-muted"></small>
                </div>
                <div class="col-md-3">
                    <label>No. of Senior/PWD</label>
                    <p class="small text-muted mb-2">20% discount per head</p>
                    <input type="number" name="reservations[__INDEX__][num_seniors]" class="form-control res-num-seniors" min="0" value="0">
                </div>
            </div>

            <!-- Meal selection (collapsible) -->
            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#mealSelection___INDEX__">
                    Select Free Breakfast
                </button>
                <div class="collapse mt-2" id="mealSelection___INDEX__">
                    <div class="border rounded p-2">
                        <h6 class="mb-2">Free Breakfast Choices</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <label class="form-label">Bangsilog</label>
                                <input type="number" name="reservations[__INDEX__][meal][bangsilog]" class="form-control meal-qty" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">Tocilog</label>
                                <input type="number" name="reservations[__INDEX__][meal][tocilog]" class="form-control meal-qty" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">Hotsilog</label>
                                <input type="number" name="reservations[__INDEX__][meal][hotsilog]" class="form-control meal-qty" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">Spamsilog</label>
                                <input type="number" name="reservations[__INDEX__][meal][spamsilog]" class="form-control meal-qty" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">Tapsilog</label>
                                <input type="number" name="reservations[__INDEX__][meal][tapsilog]" class="form-control meal-qty" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room tiles -->
            <div class="mt-3">
                <div class="room-tiles-wrapper"></div>
                <input type="hidden" name="reservations[__INDEX__][room_number]" class="res-room-number-hidden">
            </div>

            <!-- Footer buttons -->
            <div class="reservation-block-footer mt-3">
                <button type="button" class="check-btn btn-check-availability">Check Availability</button>
                <button type="button" class="delete-btn btn-remove-block" style="display:none;">Delete Room</button>
            </div>
        </div>
    </template>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/booking.js') }}"></script>
@endsection
