<?php

namespace Pm\GuzzleCaching\Middlewares;

use GuzzleHttp\Psr7\Utils;
use Pm\GuzzleCaching\CacheControlStorage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CacheResponse
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

            $promise = $handler($request, $options);

            if ($this->storage->isCacheableRequest()) {
                return $promise->then(function (ResponseInterface $response) {
                    $this->storage->withResponse($response);

                    if ($this->storage->isCacheableResponse()) {
                        // Store response for reuse
                        $cached = $this->storage->storeResponse();

                        // Restore stream
                        return $response
                            ->withBody(Utils::streamFor($cached->getBody()));
                    } elseif ($this->storage->isNotModifiedResponse()) {
                        // Update and reuse cached response
                        $cached = $this->storage->updateResponse();

                        // Modify current response
                        foreach ($cached->getHeaders() as $headerName => $headerValue) {
                            $response = $response
                                ->withHeader($headerName, $headerValue);
                        }

                        return $response
                            ->withStatus($cached->getStatusCode())
                            ->withBody(Utils::streamFor($cached->getBody()));
                    }

                    return $response;
                });
            }

            return $promise;
        };
    }
}