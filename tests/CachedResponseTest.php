<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pm\GuzzleCaching\CachedResponse;

class CachedResponseTest extends TestCase
{
    public function testFreshnessBasedOnExpires()
    {
        $response = new CachedResponse(200, [
            'Expires' => [
                (new \DateTime())
                    ->add(new \DateInterval('PT5S'))
                    ->format('r'),
            ]
        ], '');

        $this->assertTrue($response->isFresh());

        $response = new CachedResponse(200, [
            'Expires' => [
                (new \DateTime())
                    ->sub(new \DateInterval('PT5S'))
                    ->format('r'),
            ]
        ], '');

        $this->assertTrue($response->isStale());
    }

    public function testFreshnessBasedOnMaxAge()
    {
        $response = new CachedResponse(200, [
            'Date'          => [
                (new \DateTime())->format('r'),
            ],
            'Cache-Control' => [
                'max-age=300'
            ]
        ], '');

        $this->assertTrue($response->isFresh());

        $response = new CachedResponse(200, [
            'Date'          => [
                (new \DateTime())
                    ->sub(new \DateInterval('PT300S'))
                    ->format('r'),
            ],
            'Cache-Control' => [
                'max-age=300'
            ]
        ], '');

        $this->assertTrue($response->isStale());
    }

    public function testFreshnessBasedOnMaxAgeAndAge()
    {
        $response = new CachedResponse(200, [
            'Date'          => [
                (new \DateTime())->format('r'),
            ],
            'Age'           => [
                50
            ],
            'Cache-Control' => [
                'max-age=350'
            ]
        ], '');

        $this->assertTrue($response->isFresh());

        $response = new CachedResponse(200, [
            'Date'          => [
                (new \DateTime())
                    ->sub(new \DateInterval('PT300S'))
                    ->format('r'),
            ],
            'Age'           => [
                50
            ],
            'Cache-Control' => [
                'max-age=350'
            ]
        ], '');

        $this->assertTrue($response->isStale());
    }
}
