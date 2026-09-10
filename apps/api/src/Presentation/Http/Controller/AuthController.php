<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Auth\Command\ChangePasswordCommand;
use Yume\Api\Application\Auth\Command\LoginCommand;
use Yume\Api\Application\Auth\Command\LogoutCommand;
use Yume\Api\Application\Auth\Command\RegisterCommand;
use Yume\Api\Application\Auth\Command\RequestPasswordResetCommand;
use Yume\Api\Application\Auth\Command\ResendVerificationCommand;
use Yume\Api\Application\Auth\Command\ResetPasswordCommand;
use Yume\Api\Application\Auth\Command\VerifyEmailCommand;
use Yume\Api\Application\Auth\DTO\AuthenticatedUser;
use Yume\Api\Application\Auth\DTO\LoginResult;
use Yume\Api\Application\Auth\Query\GetCurrentUserQuery;
use Yume\Api\Presentation\Http\Middleware\AuthenticateMiddleware;
use Yume\Api\Presentation\Http\Request\Validator;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Bus\CommandBusInterface;
use Yume\Contracts\Bus\QueryBusInterface;
use Yume\Contracts\Feature\FeatureFlagsInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Security\Csrf\CsrfTokenManager;

/**
 * Thin by design: validate the shape, dispatch a command, serialise the result.
 *
 * There is no business logic here at all — that is the whole point of the
 * Application layer. Compare `html/index.php:107`, where the login "controller"
 * built SQL, hashed the password, set the cookie and rendered four templates in
 * one 40-line function that was then copy-pasted for the profile page.
 */
final class AuthController
{
    public function __construct(
        private readonly CommandBusInterface $commands,
        private readonly QueryBusInterface $queries,
        private readonly AuthenticateMiddleware $authentication,
        private readonly CsrfTokenManager $csrf,
        private readonly FeatureFlagsInterface $features,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function register(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        if (!$this->features->enabled('registration_open', ['ip' => $request->ip()])) {
            return ProblemDetails::make(
                403,
                'auth.registration_closed',
                'Registration is currently closed.',
            );
        }

        $data = Validator::for($request->body())
            ->string('username', 3, 32)
            ->email('email')
            ->password('password')
            ->boolean('accepted_terms')
            ->validated();

        /** @var AuthenticatedUser $user */
        $user = $this->commands->dispatch(new RegisterCommand(
            (string) $data['username'],
            (string) $data['email'],
            (string) $data['password'],
            $request->ip(),
            $request->userAgent(),
            (bool) $data['accepted_terms'],
        ));

        return JsonResponse::created(['user' => $user->toArray()]);
    }

    /** @param array<string, string> $parameters */
    public function login(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())
            ->string('identifier', 3, 254)
            // No minimum here: rejecting a short password with a validation error
            // would tell an attacker the policy applies to existing accounts too.
            ->password('password', min: 1)
            ->validated();

        /** @var LoginResult $result */
        $result = $this->commands->dispatch(new LoginCommand(
            (string) $data['identifier'],
            (string) $data['password'],
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::ok([
            'user' => $result->user->toArray(),
            'session' => [
                'id' => $result->sessionId,
                'expires_at' => $result->expiresAt->format(\DateTimeInterface::ATOM),
                'csrf_token' => $this->csrf->generate($result->sessionId),
            ],
            // Also set as an HttpOnly cookie below; returned in the body so
            // non-browser clients can use it as a bearer token.
            'token' => $result->sessionToken,
        ])->withCookie($this->authentication->cookieLine($result->sessionToken, $result->expiresAt));
    }

    /** @param array<string, string> $parameters */
    public function logout(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $sessionId = $request->attribute('session_id');

        if (is_string($sessionId)) {
            $this->commands->dispatch(new LogoutCommand(
                $sessionId,
                $request->ip(),
                $request->userAgent(),
            ));
        }

        return JsonResponse::ok(['logged_out' => true])
            ->withCookie(AuthenticateMiddleware::COOKIE_NAME . '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; SameSite=Lax');
    }

    /** @param array<string, string> $parameters */
    public function me(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = $request->attribute('user_id');

        if (!is_string($userId)) {
            return ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.');
        }

        /** @var AuthenticatedUser $user */
        $user = $this->queries->ask(new GetCurrentUserQuery($userId));

        $sessionId = $request->attribute('session_id');

        return JsonResponse::ok([
            'user' => $user->toArray(),
            'csrf_token' => is_string($sessionId) ? $this->csrf->generate($sessionId) : null,
        ]);
    }

    /**
     * Confirms an email address from the token in the link.
     *
     * @param array<string, string> $parameters
     */
    public function verifyEmail(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())->string('token', 16, 128)->validated();

        $this->commands->dispatch(new VerifyEmailCommand(
            (string) $data['token'],
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::ok(['verified' => true]);
    }

    /**
     * Requests a fresh verification link.
     *
     * Always answers 202, whether or not the address belongs to an unverified
     * account — otherwise the endpoint reports who is registered.
     *
     * @param array<string, string> $parameters
     */
    public function resendVerification(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())->email('email')->validated();

        $this->commands->dispatch(new ResendVerificationCommand(
            (string) $data['email'],
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::withStatusAndPayload(202, [
            'message' => 'If that address belongs to an unverified account, a new link is on its way.',
        ]);
    }

    /**
     * Starts a password reset. Same non-committal answer, for the same reason.
     *
     * @param array<string, string> $parameters
     */
    public function forgotPassword(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())->email('email')->validated();

        $this->commands->dispatch(new RequestPasswordResetCommand(
            (string) $data['email'],
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::withStatusAndPayload(202, [
            'message' => 'If that address belongs to an account, a reset link is on its way.',
        ]);
    }

    /** @param array<string, string> $parameters */
    public function resetPassword(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $data = Validator::for($request->body())
            ->string('token', 16, 128)
            ->password('password')
            ->validated();

        $this->commands->dispatch(new ResetPasswordCommand(
            (string) $data['token'],
            (string) $data['password'],
            $request->ip(),
            $request->userAgent(),
        ));

        // Every session was revoked, including any the attacker held.
        return JsonResponse::ok(['reset' => true, 'sessions_revoked' => true])
            ->withCookie(AuthenticateMiddleware::COOKIE_NAME . '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; SameSite=Lax');
    }

    /** @param array<string, string> $parameters */
    public function changePassword(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = $request->attribute('user_id');
        $sessionId = $request->attribute('session_id');

        if (!is_string($userId)) {
            return ProblemDetails::make(401, 'auth.required', 'Authentication is required for this endpoint.');
        }

        $data = Validator::for($request->body())
            ->password('current_password', min: 1)
            ->password('new_password')
            ->validated();

        $revoked = $this->commands->dispatch(new ChangePasswordCommand(
            $userId,
            (string) $data['current_password'],
            (string) $data['new_password'],
            is_string($sessionId) ? $sessionId : '',
            $request->ip(),
            $request->userAgent(),
        ));

        return JsonResponse::ok(['changed' => true, 'other_sessions_revoked' => $revoked]);
    }
}
