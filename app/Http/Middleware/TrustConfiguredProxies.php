<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

class TrustConfiguredProxies extends TrustProxies
{
    protected function proxies()
    {
        $configured = config('school.trusted_proxies');

        if (is_string($configured)) {
            $configured = trim($configured);

            if ($configured === '') {
                return parent::proxies();
            }

            return $configured === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $configured))));
        }

        return parent::proxies();
    }
}
