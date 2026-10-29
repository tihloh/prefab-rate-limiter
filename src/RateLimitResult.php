<?php
declare(strict_types=1);
namespace Tihloh\Prefab\RateLimiter;
final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $remaining,
        public int $retryAfter,
    ) {}
    public function headers(): array
    {
        $headers = ['X-RateLimit-Limit' => (string)$this->limit, 'X-RateLimit-Remaining' => (string)$this->remaining];
        if (!$this->allowed) $headers['Retry-After'] = (string)$this->retryAfter;
        return $headers;
    }
}
