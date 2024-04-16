<?php

namespace Tests;

use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Pm\GuzzleCaching\CacheControlStorage;
use Pm\GuzzleCaching\Middlewares\CacheResponse;
use Pm\GuzzleCaching\Middlewares\ReuseCachedResponse;

abstract class Test extends TestCase
{
    public $storage;
    public $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = CacheControlStorage::make(new ArrayCache(86000));

        $handler = HandlerStack::create();
        $handler->unshift(new ReuseCachedResponse($this->storage), 'reuse_cached');
        $handler->push(new CacheResponse($this->storage), 'cache_response');
        $this->handler = $handler;
    }
}
