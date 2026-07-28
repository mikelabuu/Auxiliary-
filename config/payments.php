<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Signing Secret
    |--------------------------------------------------------------------------
    |
    | Shared secret used to authenticate gateway callbacks. The webhook route
    | cannot use session auth or CSRF — the caller is a payment provider, not a
    | browser — so it verifies an HMAC-SHA256 signature over the raw request
    | body instead.
    |
    | Deliberately has no default. An unset secret makes the webhook reject
    | everything, which is the correct failure mode: a misconfigured deployment
    | should decline confirmations, not accept unsigned ones.
    |
    | Set in .env when the real gateway is wired up:
    |     PAYMENT_WEBHOOK_SECRET=<the provider's signing secret>
    |
    */

    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),

    /*
    | Header carrying the signature. PayMongo uses `Paymongo-Signature`,
    | Xendit `x-callback-token`; change this to match whichever is adopted.
    */

    'signature_header' => env('PAYMENT_SIGNATURE_HEADER', 'X-Signature'),

];
