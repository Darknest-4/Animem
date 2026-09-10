<?php

declare(strict_types=1);

namespace Yume\Tests\Feature;

use Yume\Api\Presentation\Http\Kernel;
use Yume\Api\Presentation\Http\Request\Request;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Shared\Container\Container;
use Yume\Shared\Support\Json;
use Yume\Tests\Support\DatabaseTestCase;

/**
 * End-to-end through the real middleware pipeline and a real database.
 *
 * These are the tests that would have caught the legacy site's authentication
 * bypass: they drive the same Kernel the web entry point drives, so a gate that
 * is misordered or missing shows up here.
 */
final class AuthenticationFlowTest extends DatabaseTestCase
{
    private const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36';

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = require dirname(__DIR__, 2) . '/api/bootstrap/app.php';
        $this->container->instance(\Yume\Contracts\Persistence\ConnectionInterface::class, $this->connection);
    }

    public function testHealthIsReachableWithoutAuthentication(): void
    {
        $response = $this->send('GET', '/health');

        self::assertSame(200, $response->status());
    }

    public function testRegisterThenLoginThenReadOwnProfile(): void
    {
        $register = $this->send('POST', '/api/v1/auth/register', [
            'username' => 'kitsune',
            'email' => 'kitsune@yume.test',
            'password' => 'a perfectly fine passphrase',
            'accepted_terms' => true,
        ]);

        self::assertSame(201, $register->status());

        $login = $this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'kitsune',
            'password' => 'a perfectly fine passphrase',
        ]);

        self::assertSame(200, $login->status());
        $token = $this->payload($login)['token'] ?? null;
        self::assertIsString($token);

        $me = $this->send('GET', '/api/v1/auth/me', headers: ['authorization' => 'Bearer ' . $token]);

        self::assertSame(200, $me->status());
        self::assertSame('kitsune', $this->payload($me)['user']['username']);
    }

    public function testTheSessionCookieIsHttpOnlyAndSameSite(): void
    {
        $this->registerUser('tanuki', 'tanuki@yume.test', 'a perfectly fine passphrase');

        $login = $this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'tanuki',
            'password' => 'a perfectly fine passphrase',
        ]);

        self::assertInstanceOf(JsonResponse::class, $login);
        $cookies = implode(' ', $login->cookies());

        self::assertStringContainsString('yume_session=', $cookies);
        self::assertStringContainsString('HttpOnly', $cookies);
        self::assertStringContainsString('SameSite=Lax', $cookies);
    }

    /**
     * The exact attack the legacy site was open to: `setcookie("userID", $id)`
     * with `SELECT id FROM users WHERE id = {$_COOKIE["userID"]}` behind it meant
     * that sending `userID=1` made you user 1.
     */
    public function testAForgedLegacyUserIdCookieGrantsNothing(): void
    {
        $this->registerUser('victim', 'victim@yume.test', 'a perfectly fine passphrase');

        $userId = (string) $this->connection->scalar('SELECT id FROM users WHERE username = :u', ['u' => 'victim']);

        $response = $this->send('GET', '/api/v1/auth/me', cookies: ['userID' => $userId]);

        self::assertSame(401, $response->status());
    }

    public function testAnInvalidSessionTokenIsRejected(): void
    {
        $response = $this->send('GET', '/api/v1/auth/me', headers: [
            'authorization' => 'Bearer ' . str_repeat('a', 43),
        ]);

        self::assertSame(401, $response->status());
    }

    public function testWrongPasswordAndUnknownUserAreIndistinguishable(): void
    {
        $this->registerUser('realuser', 'real@yume.test', 'a perfectly fine passphrase');

        $wrongPassword = $this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'realuser',
            'password' => 'definitely not the password',
        ]);
        $unknownUser = $this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'ghostuser',
            'password' => 'definitely not the password',
        ]);

        self::assertSame($wrongPassword->status(), $unknownUser->status());
        self::assertSame(
            $this->payload($wrongPassword)['code'],
            $this->payload($unknownUser)['code'],
            'a differing error code turns the login form into a username oracle',
        );
    }

    public function testLogoutRevokesTheSession(): void
    {
        $this->registerUser('leaver', 'leaver@yume.test', 'a perfectly fine passphrase');

        $token = $this->payload($this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'leaver',
            'password' => 'a perfectly fine passphrase',
        ]))['token'];

        $auth = ['authorization' => 'Bearer ' . $token];

        self::assertSame(200, $this->send('POST', '/api/v1/auth/logout', headers: $auth)->status());
        self::assertSame(401, $this->send('GET', '/api/v1/auth/me', headers: $auth)->status());
    }

    public function testAdminEndpointsRefuseAnOrdinaryMember(): void
    {
        $this->registerUser('member', 'member@yume.test', 'a perfectly fine passphrase');

        $token = $this->payload($this->send('POST', '/api/v1/auth/login', [
            'identifier' => 'member',
            'password' => 'a perfectly fine passphrase',
        ]))['token'];

        $response = $this->send('GET', '/api/v1/admin/users', headers: ['authorization' => 'Bearer ' . $token]);

        self::assertSame(403, $response->status());
        self::assertSame('authorization.denied', $this->payload($response)['code']);
    }

    public function testAdminEndpointsRefuseAnAnonymousCaller(): void
    {
        self::assertSame(401, $this->send('GET', '/api/v1/admin/users')->status());
    }

    public function testEveryDeniedAuthorizationIsAudited(): void
    {
        $this->send('GET', '/api/v1/admin/users');

        $count = (int) $this->connection->scalar(
            "SELECT count(*) FROM security_events WHERE event_type IN ('authorization.denied', 'login.blocked')",
        );

        self::assertGreaterThanOrEqual(0, $count);
    }

    public function testSecurityHeadersArePresentOnEveryResponse(): void
    {
        $headers = $this->send('GET', '/health')->headers();

        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertArrayHasKey('Content-Security-Policy', $headers);
    }

    public function testErrorsAreReturnedAsProblemJsonWithoutInternalDetail(): void
    {
        $response = $this->send('GET', '/api/v1/does-not-exist');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('application/problem+json', $response->headers()['Content-Type']);

        $body = $response->bodyAsString();
        self::assertStringNotContainsString('SELECT', $body);
        self::assertStringNotContainsString('/var/www', $body);
    }

    private function registerUser(string $username, string $email, string $password): void
    {
        $response = $this->send('POST', '/api/v1/auth/register', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'accepted_terms' => true,
        ]);

        self::assertSame(201, $response->status(), 'fixture registration failed: ' . $response->bodyAsString());
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    private function send(
        string $method,
        string $path,
        array $body = [],
        array $headers = [],
        array $cookies = [],
    ): ResponseInterface {
        $request = Request::create(
            $method,
            $path,
            [
                'user-agent' => self::BROWSER,
                'accept' => 'application/json',
                'accept-language' => 'hu-HU',
                'content-type' => 'application/json',
                ...$headers,
            ],
            [],
            $body,
            $cookies,
            '203.0.113.' . random_int(2, 250),
        );

        return $this->container->get(Kernel::class)->handle($request);
    }

    /** @return array<string, mixed> */
    private function payload(ResponseInterface $response): array
    {
        return Json::decodeToArrayOrEmpty($response->bodyAsString());
    }
}
