<?php
declare(strict_types=1);
namespace Tihloh\Prefab\RateLimiter;
/** Independent, atomic file-backed limiter. No session or database required. */
final class RateLimiter
{
    public function __construct(private string $directory) {}

    /** Fixed window, including the final allowed request. */
    public function attempt(string $policy, string $identity, int $limit, int $windowSeconds = 60, ?int $time = null): RateLimitResult
    {
        if ($limit < 1 || $windowSeconds < 1 || $policy === '' || $identity === '') {
            throw new \InvalidArgumentException('Invalid rate limiting policy.');
        }
        $now = $time ?? time();
        $bucket = intdiv($now, $windowSeconds);
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Rate limiting storage is unavailable.');
        }
        $file = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . hash('sha256', $policy . "\0" . $identity . "\0" . $bucket . "\0" . $windowSeconds) . '.rate';
        $handle = @fopen($file, 'c+b');
        if ($handle === false) throw new \RuntimeException('Unable to open rate limiting counter.');
        try {
            if (!flock($handle, LOCK_EX)) throw new \RuntimeException('Unable to lock rate limiting counter.');
            rewind($handle);
            $count = max(0, (int)trim((string)stream_get_contents($handle)));
            $allowed = $count < $limit;
            if ($allowed) {
                ++$count;
                rewind($handle);
                ftruncate($handle, 0);
                if (fwrite($handle, (string)$count) === false || !fflush($handle)) {
                    throw new \RuntimeException('Unable to save rate limiting counter.');
                }
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        return new RateLimitResult($allowed, $limit, max(0, $limit - $count), max(1, ($bucket + 1) * $windowSeconds - $now));
    }
}
