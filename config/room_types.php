<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Room Catalog
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the room types shown on the public booking
    | page: the room grid cards, the reservation "room type" select options,
    | the mobile sticky bar's "starting from" price, and the dedicated room
    | detail page at /rooms/{slug}.
    |
    | `image` is the hero/card shot. `gallery` is the detail page's photo
    | mosaic — the hero is prepended automatically by RoomCatalog, so list
    | only the *additional* shots here. `tags` are the short descriptive
    | chips under the room title on the detail page.
    |
    | IMPORTANT: The `id` and `beds` values must stay in sync with the
    | `$capacityMap` defined in App\Http\Controllers\BookingController, since
    | the backend re-validates capacity independently of the frontend.
    |
    */

    [
        'id'          => 'double',
        'title'       => 'Double Room',
        'description' => 'A cozy, well-appointed room with two single beds, perfect for pairs, colleagues, or friends visiting CLSU together. All essential amenities included for a comfortable stay.',
        'floor'       => 'Ground Floor · Block A',
        'beds'        => 2,
        'price'       => 1600,
        'image'       => 'image/double.jpg',
        'gallery'     => ['image/gallery/1.jpg', 'image/gallery/2.jpg', 'image/gallery/3.jpg'],
        'tags'        => ['Twin Beds', 'Ground Floor', 'Best Value'],
        'capacity'    => '2 Single Beds (2pax)',
        'includes'    => ['Free Breakfast (2 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
        ],
    ],
    [
        'id'          => 'triple',
        'title'       => 'Triple Room',
        'description' => 'Spacious room with three single beds, ideal for small groups, families, or research teams needing shared accommodation on campus.',
        'floor'       => 'Ground Floor · Block B',
        'beds'        => 3,
        'price'       => 2100,
        'image'       => 'image/triple.jpg',
        'gallery'     => ['image/gallery/4.jpg', 'image/gallery/5.jpg', 'image/gallery/6.jpg'],
        'tags'        => ['Three Singles', 'Ground Floor', 'Small Groups'],
        'capacity'    => '3 Single Beds (3pax)',
        'includes'    => ['Free Breakfast (3 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
        ],
    ],
    /*
     | Family Room — added at the client's request (3 pax).
     |
     | Price and capacity here are only a fallback: RoomCatalog overlays both
     | from the `room_types` table, which staff own from Room Types & Pricing.
     | The migration that seeds that row uses the same ₱2,400 as the Triple,
     | the property's other three-person room.
     */
    [
        'id'          => 'family',
        'title'       => 'Family Room',
        'description' => 'A room laid out for a family travelling together: a double bed and a single, with space to keep everyone\'s things without climbing over them. Suited to a couple with a child, or three adults who would rather not share singles.',
        'floor'       => 'Ground Floor · Block B',
        'beds'        => 3,
        'price'       => 2400,
        'image'       => 'image/triple.jpg',
        'gallery'     => ['image/gallery/4.jpg', 'image/gallery/5.jpg', 'image/gallery/6.jpg'],
        'tags'        => ['Double + Single', 'Ground Floor', 'Families'],
        'capacity'    => '1 Double + 1 Single (3pax)',
        'includes'    => ['Free Breakfast (3 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
        ],
    ],
    [
        'id'          => 'quadruple',
        'title'       => 'Quadruple Room',
        'description' => 'Our most popular room type, featuring a bunk bed and two single beds that comfortably accommodate up to four guests. The mini-fridge is a welcome addition for longer stays.',
        'floor'       => '2nd Floor · Block A',
        'beds'        => 4,
        'price'       => 2400,
        'image'       => 'image/quadruple.jpg',
        'gallery'     => ['image/gallery/7.jpg', 'image/gallery/8.jpg', 'image/gallery/9.jpg'],
        'tags'        => ['Family Friendly', 'Bunk Bed', 'Mini Fridge'],
        'capacity'    => '1 Bunk Bed & 2 Single Beds (4pax)',
        'badge'       => 'Popular',
        'includes'    => ['Free Breakfast (4 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower', 'Mini Refrigerator'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
            ['icon' => 'kitchen',     'label' => 'Mini Fridge'],
        ],
    ],
    [
        'id'          => 'deluxe',
        'title'       => 'Deluxe Room',
        'description' => 'A premium private room with a queen-size bed, designed for couples or solo travelers who want superior comfort. Fully air-conditioned with upgraded furnishings and extra privacy.',
        'floor'       => '2nd Floor · Block C',
        'beds'        => 2,
        'price'       => 2500,
        'image'       => 'image/deluxe.jpg',
        'gallery'     => ['image/gallery/10.jpg', 'image/gallery/11.jpg', 'image/gallery/12.jpg'],
        'tags'        => ['Queen Bed', 'Air-Conditioned', 'Private Stay'],
        'capacity'    => '1 Queen Size Bed (2pax)',
        'badge'       => 'Premium',
        'includes'    => ['Free Breakfast (2 pax)', 'Premium Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower', 'Air-Conditioning', 'Mini Refrigerator'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'kitchen',     'label' => 'Mini Fridge'],
            ['icon' => 'ac_unit',     'label' => 'Air-Conditioned'],
        ],
    ],
    [
        'id'          => 'dormitory1',
        'title'       => 'Dormitory Room I',
        'description' => 'A well-lit dormitory room featuring two bunk beds and one single bed. Best suited for groups, student organizations, and university delegations arriving on campus.',
        'floor'       => '3rd Floor · Dormitory Wing',
        'beds'        => 5,
        'price'       => 2500,
        'image'       => 'image/dormitory1.jpg',
        'gallery'     => ['image/gallery/2.jpg', 'image/gallery/6.jpg', 'image/gallery/9.jpg'],
        'tags'        => ['Bunk Beds', 'Dormitory Wing', 'Student Groups'],
        'capacity'    => '2 Bunk Beds & 1 Single Bed (5pax)',
        'includes'    => ['Free Breakfast (5 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
        ],
    ],
    [
        'id'          => 'dormitory2',
        'title'       => 'Dormitory Room II',
        'description' => 'The largest room at the hostel with three bunk beds that sleep up to six guests comfortably. Ideal for large delegations, institutional groups, and overnight campus events.',
        'floor'       => '3rd Floor · Dormitory Wing',
        'beds'        => 6,
        'price'       => 3000,
        'image'       => 'image/dormitory2.jpg',
        'gallery'     => ['image/gallery/3.jpg', 'image/gallery/7.jpg', 'image/gallery/11.jpg'],
        'tags'        => ['Largest Room', 'Dormitory Wing', 'Delegations'],
        'capacity'    => '3 Bunk Beds (6pax)',
        'includes'    => ['Free Breakfast (6 pax)', 'Complimentary Guest Kit', 'Free Wi-Fi', 'Daily Housekeeping', 'Hot & Cold Shower'],
        'amenities'   => [
            ['icon' => 'wifi',        'label' => 'Free Wi-Fi'],
            ['icon' => 'shower',      'label' => 'Hot & Cold'],
            ['icon' => 'inventory_2', 'label' => 'Guest Kit'],
        ],
    ],

];