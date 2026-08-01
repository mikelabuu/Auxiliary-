<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = App\Models\Booking::with('payments')->find(27);
if ($b) {
    echo json_encode([
        'status' => $b->status,
        'check_in' => $b->check_in,
        'check_in_today' => \Carbon\Carbon::parse($b->check_in)->timezone('Asia/Manila')->isToday(),
        'payment_exists' => $b->payments !== null,
        'payment_status' => $b->payments->status ?? null
    ], JSON_PRETTY_PRINT);
} else {
    echo "Booking not found";
}
