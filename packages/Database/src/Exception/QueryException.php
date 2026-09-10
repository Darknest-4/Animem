<?php

declare(strict_types=1);

namespace Yume\Database\Exception;

/**
 * Carries the failing SQL for the log, never for the response body.
 *
 * The HTTP error handler renders a generic message; only the structured logger
 * is allowed to read {@see self::sql()}.
 */
final class QueryException extends \RuntimeException
{
    private string $sql = '';

    public static function fromPdo(\PDOException $e, string $sql): self
    {
        $exception = new self('Database query failed.', previous: $e);
        $exception->sql = $sql;

        return $exception;
    }

    public function sql(): string
    {
        return $this->sql;
    }

    public function isUniqueViolation(): bool
    {
        $previous = $this->getPrevious();

        // PostgreSQL SQLSTATE 23505 = unique_violation
        return $previous instanceof \PDOException && ($previous->getCode() === '23505');
    }
}
