<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Receipt>
 *
 * A receipt is only meaningful alongside the file it hashes, so `withFile()`
 * writes a stand-in PDF to the local disk and records its real digest.
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'booking_id'     => Booking::factory()->paid(),
            'receipt_number' => 'R-' . fake()->unique()->numerify('######'),
            'generated_by'   => 'system',
            'file_path'      => 'receipts/Receipt_' . fake()->unique()->numerify('#####') . '.pdf',
            'sha256_hash'    => hash('sha256', 'placeholder'),
        ];
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn () => [
            'booking_id'     => $booking->id,
            'receipt_number' => 'R-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'file_path'      => "receipts/Receipt_{$booking->id}.pdf",
        ]);
    }

    /**
     * Write the backing file and store its true digest, so verification passes.
     * Requires Storage::fake('local') in the test.
     */
    public function withFile(string $contents = '%PDF-1.4 stand-in receipt'): static
    {
        return $this->afterCreating(function (Receipt $receipt) use ($contents) {
            Storage::disk('local')->put($receipt->file_path, $contents);

            $receipt->forceFill([
                'sha256_hash' => hash('sha256', $contents),
            ])->save();
        });
    }
}
