<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Controller;

use Yume\Api\Application\Security\SecurityAuditor;
use Yume\Api\Domain\Security\Audit\SecurityAuditRepositoryInterface;
use Yume\Api\Domain\Security\Audit\SecurityEvent;
use Yume\Api\Domain\Security\Audit\SecurityEventType;
use Yume\Api\Domain\Security\Ban\Ban;
use Yume\Api\Domain\Security\Ban\BanRepositoryInterface;
use Yume\Api\Domain\Security\Ban\BanScope;
use Yume\Api\Domain\Security\Ban\BanType;
use Yume\Api\Domain\Shared\ValueObject\IpAddress;
use Yume\Api\Domain\Shared\ValueObject\UserAgent;
use Yume\Api\Domain\User\ValueObject\UserId;
use Yume\Api\Presentation\Http\Response\JsonResponse;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Clock\ClockInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Identity\IdGeneratorInterface;

/**
 * Reading the audit trail and applying or lifting bans.
 *
 * `security.view` is read-only and can reasonably be given to moderators;
 * `security.manage` applies bans and is separate for that reason.
 */
final class AdminSecurityController
{
    public function __construct(
        private readonly SecurityAuditRepositoryInterface $audit,
        private readonly BanRepositoryInterface $bans,
        private readonly SecurityAuditor $auditor,
        private readonly IdGeneratorInterface $ids,
        private readonly ClockInterface $clock,
    ) {
    }

    /** @param array<string, string> $parameters */
    public function events(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $severity = (string) ($request->query('severity') ?? 'warning');

        if (!in_array($severity, ['info', 'notice', 'warning', 'critical'], true)) {
            return ProblemDetails::validation(['severity' => ['Must be info, notice, warning or critical.']]);
        }

        $limit = max(1, min(200, (int) ($request->query('limit') ?? 50)));

        return JsonResponse::ok([
            'severity' => $severity,
            'events' => array_map($this->presentEvent(...), $this->audit->recentBySeverity($severity, $limit)),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function userEvents(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $userId = UserId::fromString($parameters['id'] ?? '');
        $limit = max(1, min(200, (int) ($request->query('limit') ?? 50)));

        return JsonResponse::ok([
            'user_id' => $userId->value,
            'events' => array_map($this->presentEvent(...), $this->audit->recentForUser($userId, $limit)),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function listBans(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        return JsonResponse::ok([
            'bans' => array_map($this->presentBan(...), $this->bans->listActive($this->clock->now(), 100)),
        ]);
    }

    /** @param array<string, string> $parameters */
    public function createBan(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $body = $request->body();

        $scope = BanScope::tryFrom((string) ($body['scope'] ?? ''));
        if ($scope === null) {
            return ProblemDetails::validation(['scope' => ['Must be ip, subnet, user or global.']]);
        }

        $type = BanType::tryFrom((string) ($body['type'] ?? 'manual'));
        if ($type === null) {
            return ProblemDetails::validation(['type' => ['Must be automatic, manual or read_only.']]);
        }

        $reason = trim((string) ($body['reason'] ?? ''));
        if ($reason === '') {
            // A ban with no stated reason is unreviewable six months later.
            return ProblemDetails::validation(['reason' => ['A reason is required.']]);
        }

        $subject = $this->normaliseSubject($scope, $body['subject'] ?? null);
        if ($scope !== BanScope::Global && $subject === null) {
            return ProblemDetails::validation(['subject' => ['Required, and must match the chosen scope.']]);
        }

        $now = $this->clock->now();
        $expiresAt = null;

        if (isset($body['expires_in_seconds'])) {
            $seconds = (int) $body['expires_in_seconds'];
            if ($seconds < 60) {
                return ProblemDetails::validation(['expires_in_seconds' => ['Must be at least 60, or omitted for a permanent ban.']]);
            }
            $expiresAt = $now->modify(sprintf('+%d seconds', $seconds));
        }

        $actingUserId = $this->actingUserId($request);
        $actingIp = IpAddress::fromString($request->ip());

        // A global ban locks everyone out, including the admin issuing it.
        if ($scope === BanScope::Global) {
            return ProblemDetails::make(
                422,
                'ban.global_refused',
                'A global ban cannot be applied through the API. Use bin/console on the host, where it can also be lifted.',
            );
        }

        // Refuse a ban that would immediately lock out the person issuing it.
        if (($scope === BanScope::Ip && $subject === $actingIp->value)
            || ($scope === BanScope::Subnet && $subject === $actingIp->subnetKey())
            || ($scope === BanScope::User && $actingUserId !== null && $subject === $actingUserId->value)
        ) {
            return ProblemDetails::make(422, 'ban.self_ban_refused', 'That ban would lock you out.');
        }

        $ban = new Ban(
            $this->ids->generate(),
            $scope,
            $type,
            $subject,
            $reason,
            $now,
            $expiresAt,
            $actingUserId?->value,
        );

        $this->bans->save($ban);

        $this->auditor->record(
            SecurityEventType::BanApplied,
            $actingIp,
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $actingUserId,
            100,
            ['ban_id' => $ban->id, 'scope' => $scope->value, 'subject' => $subject, 'reason' => $reason],
        );

        return JsonResponse::created(['ban' => $this->presentBan($ban)]);
    }

    /** @param array<string, string> $parameters */
    public function liftBan(RequestInterface $request, array $parameters = []): ResponseInterface
    {
        $banId = $parameters['id'] ?? '';
        $this->bans->lift($banId, $this->clock->now());

        $this->auditor->record(
            SecurityEventType::BanLifted,
            IpAddress::fromString($request->ip()),
            UserAgent::fromString($request->userAgent()),
            $request->method(),
            $request->path(),
            $this->actingUserId($request),
            0,
            ['ban_id' => $banId],
        );

        return JsonResponse::ok(['lifted' => true, 'ban_id' => $banId]);
    }

    private function normaliseSubject(BanScope $scope, mixed $raw): ?string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $value = trim($raw);

        return match ($scope) {
            BanScope::Ip => IpAddress::tryFromString($value)?->value,
            // Stored in the same form the risk engine computes at request time,
            // otherwise the lookup silently never matches.
            BanScope::Subnet => IpAddress::tryFromString($value)?->subnetKey(),
            BanScope::User => $this->tryUserId($value),
            BanScope::Global => null,
        };
    }

    private function tryUserId(string $value): ?string
    {
        try {
            return UserId::fromString($value)->value;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function presentEvent(SecurityEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type->value,
            'severity' => $event->severity(),
            'user_id' => $event->userId?->value,
            'ip' => $event->ip->value,
            'method' => $event->method,
            'path' => $event->path,
            'risk_score' => $event->riskScore,
            'metadata' => $event->metadata === [] ? new \stdClass() : $event->metadata,
            'occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function presentBan(Ban $ban): array
    {
        return [
            'id' => $ban->id,
            'scope' => $ban->scope->value,
            'type' => $ban->type->value,
            'subject' => $ban->subject,
            'reason' => $ban->reason,
            'created_at' => $ban->createdAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $ban->expiresAt?->format(\DateTimeInterface::ATOM),
            'permanent' => $ban->isPermanent(),
            'created_by' => $ban->createdBy,
        ];
    }

    private function actingUserId(RequestInterface $request): ?UserId
    {
        $raw = $request->attribute('user_id');

        return is_string($raw) ? UserId::fromString($raw) : null;
    }
}
