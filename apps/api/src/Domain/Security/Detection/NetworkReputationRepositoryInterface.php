<?php

declare(strict_types=1);

namespace Yume\Api\Domain\Security\Detection;

use Yume\Api\Domain\Shared\ValueObject\IpAddress;

/**
 * Lookup of network classifications (Tor exit nodes, known VPN/proxy ranges,
 * datacentre ASNs). Backed by a locally refreshed table, never by a blocking
 * third-party call on the request path.
 */
interface NetworkReputationRepositoryInterface
{
    public function isTorExitNode(IpAddress $ip): bool;

    public function isKnownVpn(IpAddress $ip): bool;

    public function isDatacentre(IpAddress $ip): bool;

    /** @return array{asn: int, organisation: string}|null */
    public function lookupAsn(IpAddress $ip): ?array;
}
