<?php
//TEMPORARY JOB FILE FOR PAYMENT (DOESN'T WORK)
namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FakeWebHookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function handle()
    {
        sleep(10); // simulate network delay

        $url = config('app.url') . '/sandbox/webhook/' . $this->payment->id;

        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post($url, [
                    'source' => 'FakeWebhookJob',
                    'timestamp' => now()->toISOString(),
                ]);

            Log::info("[FakeWebhookJob] Posting to: {$url}");
            Log::info("[FakeWebhookJob] Triggered webhook for payment {$this->payment->id}");
            Log::info("[FakeWebhookJob] Response: " . $response->body());
        } catch (\Exception $e) {
            Log::error("[FakeWebhookJob] Failed to trigger webhook: " . $e->getMessage());
        }
    }
}
