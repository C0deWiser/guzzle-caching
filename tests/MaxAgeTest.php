<?php

namespace Tests;

use GuzzleHttp\Client;

class MaxAgeTest extends Test
{
    public function testMaxAgeStaling()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('max_age.php');

        // Response was cached
        $this->assertTrue($this->storage->hasCachedResponse());

        // It is fresh
        $this->assertTrue($this->storage->getCachedResponse()->isFresh());

        sleep(1);

        // It is stale after 1 second...
        $this->assertTrue($this->storage->getCachedResponse()->isStale());
    }

    public function testMaxAgeReuse()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('max_age.php');

        // Response was cached
        $this->assertTrue($this->storage->hasCachedResponse());

        // It is fresh
        $this->assertTrue($this->storage->getCachedResponse()->isFresh());

        $response = $client->get('max_age.php');

        // Response was reused
        $this->assertArrayHasKey('X-Cache-Controlled', $response->getHeaders());
    }
}