<?php
declare(strict_types=1);

namespace App\DataProvider;

use Redis;
use RedisException;

class BaseDataProvider
{
    protected Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @throws RedisException
     */
    protected function resetCachePatterns(array $patterns): void
    {
        $keys = [];
        foreach ($patterns as $pattern) {
            $keys = array_merge($keys, $this->redis->keys($pattern));
        }

        $prefix = $this->redis->_prefix('');
        $keys = array_map(static function (string $key) use ($prefix) {
            return str_replace($prefix, '', $key);
        }, $keys);

        if (!empty($keys)) {
            $this->redis->del($keys);
        }

        $this->redis->save();
    }
}
