<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Security\Risk\RiskContext;
use Yume\Api\Domain\Security\Risk\RiskSignal;

/**
 * Detects forwarding headers arriving from an untrusted hop.
 *
 * The legacy `getip()` read HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR straight
 * from the client and wrote the result into SQL, so any visitor could pick their
 * own IP address. Here the real client IP is resolved from trusted proxies only
 * (see TrustedProxyResolver) and *unexpected* forwarding headers are a signal.
 */
final class ProxyDetector implements DetectorInterface
{
    private const FORWARD_HEADERS = [
        'x-forwarded-for', 'client-ip', 'x-real-ip', 'forwarded',
        'x-cluster-client-ip', 'via', 'x-proxy-id',
    ];

    /** @param list<string> $expectedHeaders headers our own edge legitimately sets */
    public function __construct(private readonly array $expectedHeaders = ['x-forwarded-for', 'x-real-ip'])
    {
    }

    public function name(): string
    {
        return 'proxy';
    }

    public function detect(RiskContext $context): ?RiskSignal
    {
        $unexpected = [];

        foreach (self::FORWARD_HEADERS as $header) {
            if ($context->header($header) !== null && !in_array($header, $this->expectedHeaders, true)) {
                $unexpected[] = $header;
            }
        }

        if ($unexpected === []) {
            return null;
        }

        return new RiskSignal(
            $this->name(),
            20,
            'Request carries forwarding headers our edge does not set.',
            ['headers' => $unexpected],
        );
    }
}
