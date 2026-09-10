<?php

declare(strict_types=1);

namespace Yume\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yume\Api\Infrastructure\Security\TrustedProxyResolver;

/**
 * Regression tests for the legacy `getip()`.
 *
 * That function read HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR straight from the
 * request and wrote the result into SQL, so any visitor could choose their own
 * IP address and evade every per-IP control on the site.
 */
final class TrustedProxyResolverTest extends TestCase
{
    public function testAForwardedHeaderFromAnUntrustedPeerIsIgnored(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        $ip = $resolver->resolve('203.0.113.9', ['x-forwarded-for' => '1.2.3.4']);

        self::assertSame('203.0.113.9', $ip->value, 'a client must not be able to declare its own address');
    }

    public function testAForwardedHeaderFromATrustedProxyIsHonoured(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        $ip = $resolver->resolve('10.1.2.3', ['x-forwarded-for' => '198.51.100.7']);

        self::assertSame('198.51.100.7', $ip->value);
    }

    /**
     * With Caddy in front of nginx the chain has two of our own hops. Walking it
     * from the right and dropping trusted entries yields the real client; taking
     * the leftmost entry would take whatever the client injected.
     */
    public function testTheChainIsWalkedFromTheRightSoAnInjectedEntryIsNotTrusted(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8', '172.16.0.0/12']);

        $ip = $resolver->resolve('172.18.0.5', [
            'x-forwarded-for' => '1.1.1.1, 198.51.100.7, 10.0.0.9',
        ]);

        self::assertSame(
            '198.51.100.7',
            $ip->value,
            'the spoofed 1.1.1.1 sits left of the real client and must not win',
        );
    }

    public function testLegacyClientIpHeaderIsNeverConsulted(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        // The legacy getip() preferred HTTP_CLIENT_IP above everything else.
        $ip = $resolver->resolve('10.0.0.1', ['client-ip' => '6.6.6.6']);

        self::assertNotSame('6.6.6.6', $ip->value);
    }

    public function testFallsBackToXRealIpWhenNoForwardedForIsPresent(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        self::assertSame('198.51.100.7', $resolver->resolve('10.0.0.1', ['x-real-ip' => '198.51.100.7'])->value);
    }

    public function testMalformedInputDoesNotProduceAnInvalidAddress(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        self::assertSame('10.0.0.1', $resolver->resolve('10.0.0.1', ['x-forwarded-for' => 'not-an-ip, "; DROP TABLE users;--'])->value);
        self::assertSame('127.0.0.1', $resolver->resolve('', [])->value);
    }

    public function testIpv6ChainIsHandled(): void
    {
        $resolver = new TrustedProxyResolver(['10.0.0.0/8']);

        $ip = $resolver->resolve('10.0.0.1', ['x-forwarded-for' => '2001:db8::1']);

        self::assertSame('2001:db8::1', $ip->value);
    }
}
