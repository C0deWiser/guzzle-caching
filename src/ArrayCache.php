<?php

namespace Codewiser\GuzzleCaching;

use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    protected $storage = [];
    protected $ttl = [];
    /**
     * @var int
     */
    protected $max_ttl;

    /**
     * @param \DateInterval|int $max_ttl
     */
    public function __construct($max_ttl)
    {
        if ($max_ttl instanceof \DateInterval) {
            $max_ttl = (new \DateTime)->add($max_ttl)->getTimestamp() - time();
        }

        $this->max_ttl = $max_ttl;
    }

    /**
     * @param  \DateInterval|int|null  $ttl
     *
     * @return int
     */
    protected function exp($ttl = null): int
    {
        if ($ttl instanceof \DateInterval) {
            return (new \DateTime)->add($ttl)->getTimestamp();
        }

        if ($ttl) {
            return time() + $ttl;
        }

        return time() + $this->max_ttl;
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? unserialize($this->storage[$key]) : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->storage[$key] = serialize($value);
        $this->ttl[$key] = $this->exp($ttl);

        return true;
    }

    public function delete($key): bool
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
        }
        if (isset($this->ttl[$key])) {
            unset($this->ttl[$key]);
        }

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
        $this->ttl = [];

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key): bool
    {
        $ttl = $this->ttl[$key] ?? $this->exp();

        if ($ttl < time() && isset($this->storage[$key])) {
            $this->delete($key);
        }

        return $ttl > time() && isset($this->storage[$key]);
    }
}