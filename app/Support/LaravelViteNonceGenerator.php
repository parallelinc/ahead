<?php

declare(strict_types=1);

namespace App\Support;

use Exception;
use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

final class LaravelViteNonceGenerator implements NonceGenerator
{
    /**
     * @throws Exception
     */
    public function generate(): string
    {
        $nonce = Vite::cspNonce();

        throw_if(is_null($nonce), Exception::class, 'Nonce must be a string');

        return $nonce;
    }
}
