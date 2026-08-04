<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * A payment that is waiting on a human. The guest has uploaded the receipt
     * their bank or e-wallet gave them and staff have not looked at it yet —
     * the money is claimed, not confirmed.
     */
    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    /** Staff looked at the proof and did not believe it. The guest may retry. */
    public const STATUS_REJECTED = 'rejected';

    /** Payment methods the guest can settle manually and prove afterwards. */
    public const PROOF_METHODS = [
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
    ];

    protected $fillable = [
        'booking_id',
        'user_id',
        'amount',
        'payment_type',
        'status',
        'reference_no',
        'gateway',
        'gateway_response',
        'landbank_transaction_id',
        'webhook_verified',
        'paid_at',
        'proof_path',
        'proof_method',
        'proof_reference',
        'proof_submitted_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        // decimal(10,2) in the database, and money everywhere it is read.
        // Left uncast it arrived as a driver string, so `$payment->amount ==
        // $booking->payable_amount` compared a string against a float.
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'webhook_verified' => 'boolean',
        'paid_at' => 'datetime',
        'proof_submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** The staff member who cleared or rejected the uploaded proof. */
    public function verifier()
    {
        return $this->belongsTo(Staff::class, 'verified_by');
    }

    /** Sitting in the staff verification queue. */
    public function scopeAwaitingVerification($query)
    {
        return $query->where('status', self::STATUS_AWAITING_VERIFICATION);
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === self::STATUS_AWAITING_VERIFICATION;
    }

    /** Human label for the method the guest claims they used. */
    public function getProofMethodLabelAttribute(): string
    {
        return self::PROOF_METHODS[$this->proof_method] ?? 'Manual';
    }
}
