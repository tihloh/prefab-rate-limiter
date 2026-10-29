<?php
declare(strict_types=1);
require_once __DIR__.'/../src/RateLimitResult.php';
require_once __DIR__.'/../src/RateLimiter.php';
use Tihloh\Prefab\RateLimiter\RateLimiter;
$dir = sys_get_temp_dir().'/prefab-limiter-'.bin2hex(random_bytes(6));
$limiter = new RateLimiter($dir);
try {
    $a=$limiter->attempt('search','client-a',2,60,120);
    $b=$limiter->attempt('search','client-a',2,60,120);
    $c=$limiter->attempt('search','client-a',2,60,120);
    assert($a->allowed && $a->remaining===1);
    assert($b->allowed && $b->remaining===0);
    assert(!$c->allowed && $c->retryAfter===60);
    assert($limiter->attempt('search','client-b',2,60,120)->allowed);
    assert($limiter->attempt('search','client-a',2,60,180)->allowed);
    echo "All rate limiter tests passed.\n";
} finally {
    foreach(glob($dir.'/*.rate')?:[] as $file) unlink($file);
    @rmdir($dir);
}
