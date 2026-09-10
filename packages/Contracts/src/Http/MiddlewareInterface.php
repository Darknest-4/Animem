<?php

declare(strict_types=1);

namespace Yume\Contracts\Http;

interface MiddlewareInterface
{
    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface;
}
