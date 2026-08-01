<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payments = App\Models\Payment::where('booking_id', 27)->get();
foreach($payments as $p) {
    echo "Payment ID: {$p->id}, Status: {$p->status}, Method: {$p->payment_type}, Reason: {$p->rejection_reason}\n";
}
