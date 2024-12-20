<?php

namespace Razorpay\Api\Errors;

class ErrorCode
{
    const BAD_REQUEST_ERROR                 = 'BAD_REQUEST_ERROR';
    const SERVER_ERROR                      = 'SERVER_ERROR';
    const GATEWAY_ERROR                     = 'GATEWAY_ERROR';

    /**
     * @param string $code
     * @return bool
     */
    public static function exists(string $code): bool
    {
        $code = strtoupper($code);

        return defined(static::class. '::' . $code);
    }
}
