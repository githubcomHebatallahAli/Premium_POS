<?php

namespace App\Http\Middleware;




use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies = '*'; // السماح بكل البروكسي

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    // protected $headers = Request::HEADER_X_FORWARDED_ALL;
    protected $headers = 0b11111111;
    // protected $headers = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_ALL;


}
