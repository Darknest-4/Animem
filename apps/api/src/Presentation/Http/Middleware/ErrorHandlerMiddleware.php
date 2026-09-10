<?php

declare(strict_types=1);

namespace Yume\Api\Presentation\Http\Middleware;

use Yume\Api\Domain\Shared\Exception\DomainException;
use Yume\Api\Presentation\Http\Response\ProblemDetails;
use Yume\Contracts\Http\HandlerInterface;
use Yume\Contracts\Http\MiddlewareInterface;
use Yume\Contracts\Http\RequestInterface;
use Yume\Contracts\Http\ResponseInterface;
use Yume\Contracts\Logging\LoggerInterface;
use Yume\Database\Exception\QueryException;

/**
 * Outermost middleware: nothing escapes past it.
 *
 * Expected failures (DomainException) become their declared status and code.
 * Everything else becomes an opaque 500 — the message, the stack trace and the
 * failing SQL go to the log, never to the client. The legacy code did the exact
 * opposite: `exit("SQL Syntax Error! SQL: " . $sql)` printed the query to the browser.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    public function process(RequestInterface $request, HandlerInterface $next): ResponseInterface
    {
        try {
            return $next->handle($request);
        } catch (DomainException $e) {
            $this->logger->info('Domain rule rejected the request.', [
                'code' => $e->errorCode(),
                'path' => $request->path(),
                'context' => $e->context(),
            ]);

            return ProblemDetails::make(
                $e->httpStatus(),
                $e->errorCode(),
                $e->getMessage(),
                $e->context(),
                $this->retryHeaders($e),
            );
        } catch (\InvalidArgumentException $e) {
            // Value object construction failed: bad input, not a server fault.
            return ProblemDetails::make(422, 'validation.failed', $e->getMessage());
        } catch (QueryException $e) {
            $this->logger->critical('Database query failed.', [
                'path' => $request->path(),
                'sql' => $e->sql(),
                'exception' => $e->getPrevious()?->getMessage(),
            ]);

            return ProblemDetails::make(500, 'server.error', 'An unexpected error occurred.');
        } catch (\Throwable $e) {
            $this->logger->critical('Unhandled exception.', [
                'path' => $request->path(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ProblemDetails::make(
                500,
                'server.error',
                'An unexpected error occurred.',
                // Detail is exposed only when APP_DEBUG is on, which the production
                // compose file never sets.
                $this->debug ? ['debug' => [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ]] : [],
            );
        }
    }

    /** @return array<string, string> */
    private function retryHeaders(DomainException $e): array
    {
        $retryAfter = $e->context()['retry_after'] ?? null;

        return is_int($retryAfter) ? ['Retry-After' => (string) $retryAfter] : [];
    }
}
