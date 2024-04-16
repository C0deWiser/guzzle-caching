<?php

namespace Pm\GuzzleCaching\Middlewares;

use Pm\GuzzleCaching\CacheControlStorage;
use Psr\Http\Message\RequestInterface;

class ReuseCachedResponse
{
    /**
     * @var CacheControlStorage
     */
    protected $storage;

    public function __construct(CacheControlStorage $storage)
    {
        $this->storage = $storage;
    }

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $this->storage
                ->withOptions($options)
                ->withRequest($request);

            if ($this->storage->isCacheableRequest()) {
                if ($this->storage->hasCachedResponse()) {
                    // Reuse?
                    $cachedResponse = $this->storage->getCachedResponse();

                    if ($cachedResponse->isFresh()) {
                        // Reuse cached response
                        return $cachedResponse->toResponse();
                    } elseif($cachedResponse->isConditional()) {
                        // Make request conditional
                        foreach ($cachedResponse->getConditionalHeaders() as $name => $value) {
                            $request = $request->withHeader($name, $value);
                        }
                    }
                }
            }

            return $handler($request, $options);
        };
    }
}