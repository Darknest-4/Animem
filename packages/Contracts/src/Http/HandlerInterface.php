<?php

declare(strict_types=1);

namespace Yume\Contracts\Http;

interface HandlerInterface
{
    public function handle(RequestInterface $request): ResponseInterface;
}
