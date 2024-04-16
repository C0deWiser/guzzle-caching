<?php

namespace Tests;

use GuzzleHttp\Client;

class EtagTest extends Test
{
    public function testEtag()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $response = $client->get('etag.php');

        $body = $response->getBody()->getContents();

        // Response was cached
        $this->assertTrue($this->storage->hasCachedResponse());

        // It is stale
        $this->assertTrue($this->storage->getCachedResponse()->isStale());

        // Repeat
        $response = $client->get('etag.php');

        // Response was reused
        $this->assertArrayHasKey('X-Cache-Controlled', $response->getHeaders());

        $this->assertEquals($body, $response->getBody()->getContents());
    }
}