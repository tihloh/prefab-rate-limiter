# Prefab Rate Limiter

Standalone PHP 8.2+ Composer package. Initial release supports concurrency-safe, file-backed fixed-window rate limiting. No PBO Albay dependencies.

## Install

Add this repository as a Composer VCS repository if not indexed on Packagist, then run `composer require tihloh/prefab-rate-limiter:dev-main`.

## Usage

```php
use Tihloh\Prefab\RateLimiter\RateLimiter;
$limiter = new RateLimiter(__DIR__.'/../storage/rate-limits');
$result = $limiter->attempt('documents.search', $userId !== null ? 'user:'.$userId : 'ip:'.$ip, $userId ? 120 : 30);
foreach ($result->headers() as $name => $value) header($name.': '.$value);
if (!$result->allowed) {
    http_response_code(429);
    exit('Too many requests. Try again shortly.');
}
```

Use trusted proxy handling for IP addresses. Keep the file storage private and writable. This implementation is suitable for a single shared filesystem, not distributed deployments. Fixed windows permit bursts at boundaries; Redis/APCu backends and sliding windows can be added later.

## Test

`php -d zend.assertions=1 -d assert.exception=1 tests/run.php`
