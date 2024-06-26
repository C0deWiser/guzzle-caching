<?php

namespace Tests;

use Codewiser\GuzzleCaching\ArrayCache;
use Codewiser\GuzzleCaching\CacheControlStorage;
use Codewiser\GuzzleCaching\Middlewares\CacheResponse;
use Codewiser\GuzzleCaching\Middlewares\ReuseCachedResponse;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

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
