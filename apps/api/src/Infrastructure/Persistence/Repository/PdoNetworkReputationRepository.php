<?php

declare(strict_types=1);

namespace Yume\Api\Infrastructure\Persistence\Repository;

use Yume\Api\Domain\Security\Detection\NetworkReputationRepositoryInterface;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Contracts\Persistence\ConnectionInterface;

/**
 * Reads the locally refreshed network_reputation table.
 *
 * All classifications for one IP are fetched in a single indexed containment
 * query and memoised for the request; a detector must never cost a round trip
 * per question, and must never call a third-party API on the request path.
 */
final class PdoNetworkReputationRepository implements NetworkReputationRepositoryInterface
{
    /** @var array<string, array{classifications: list<string>, asn: int|null, organisation: string|null}> */
    private array $cache = [];

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function isTorExitNode(IpAddress $ip): bool
    {
        return in_array('tor_exit', $this->lookup($ip)['classifications'], true);
    }

    public function isKnownVpn(IpAddress $ip): bool
    {
        return in_array('vpn', $this->lookup($ip)['classifications'], true);
    }

    public function isDatacentre(IpAddress $ip): bool
    {
        return in_array('datacentre', $this->lookup($ip)['classifications'], true);
    }

    public function lookupAsn(IpAddress $ip): ?array
    {
        $entry = $this->lookup($ip);

        if ($entry['asn'] === null) {
            return null;
        }

        return ['asn' => $entry['asn'], 'organisation' => $entry['organisation'] ?? ''];
    }

    /** @return array{classifications: list<string>, asn: int|null, organisation: string|null} */
    private function lookup(IpAddress $ip): array
    {
        if (isset($this->cache[$ip->value])) {
            return $this->cache[$ip->value];
        }

        $rows = $this->connection->select(
            <<<'SQL'
            SELECT classification, asn, organisation
            FROM network_reputation
            WHERE network >>= CAST(:ip AS inet)
            ORDER BY masklen(network) DESC
            SQL,
            ['ip' => $ip->value],
        );

        $entry = ['classifications' => [], 'asn' => null, 'organisation' => null];

        foreach ($rows as $row) {
            $entry['classifications'][] = (string) $row['classification'];
            // Most specific prefix wins; the ORDER BY puts it first.
            $entry['asn'] ??= isset($row['asn']) ? (int) $row['asn'] : null;
            $entry['organisation'] ??= isset($row['organisation']) ? (string) $row['organisation'] : null;
        }

        return $this->cache[$ip->value] = $entry;
    }
}
