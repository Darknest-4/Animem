<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Response;

/**
 * RFC 9457 problem responses.
 *
 * One shape for every error the API can return, so a client never has to guess
 * whether a failure arrived as a string, an object or an HTML stack trace — the
 * legacy site returned all three depending on which file handled the request.
 */
final class ProblemDetails
{
    /**
     * @param array<string, mixed> $extensions
     * @param array<string, string> $headers
     */
    public static function make(
        int $status,
        string $code,
        string $detail,
        array $extensions = [],
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'type' => 'https://yume.dev/problems/' . $code,
            'title' => self::titleFor($status),
            'status' => $status,
            'code' => $code,
            'detail' => $detail,
        ];

        if ($extensions !== []) {
            $payload = [...$payload, ...$extensions];
        }

        return JsonResponse::withStatusAndPayload($status, $payload, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
            ...$headers,
        ]);
    }

    /** @param array<string, list<string>> $errors field => messages */
    public static function validation(array $errors): JsonResponse
    {
        return self::make(
            422,
            'validation.failed',
            'The submitted data is invalid.',
            ['errors' => $errors],
        );
    }

    private static function titleFor(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Content',
            423 => 'Locked',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }
}
