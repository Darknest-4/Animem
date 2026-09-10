<?php

declare(strict_types=1);

namespace Yume\Api\Application\Auth\Exception;

use Yume\Api\Domain\Shared\Exception\DomainException;

final class TermsNotAcceptedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You must accept the terms of service to register.');
    }

    public function errorCode(): string
    {
        return 'auth.terms_not_accepted';
    }
}
