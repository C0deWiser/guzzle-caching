<?php

namespace Pm\GuzzleCaching;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class CacheControlStorage
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var CacheControlHeader
     */
    protected $cacheControl;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public static function make(CacheInterface $cache): CacheControlStorage
    {
        return new static($cache);
    }

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function withOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function withRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function withResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        $this->cacheControl = new CacheControlHeader($response->getHeaderLine('Cache-Control'));
        return $this;
    }

    public function isCacheableRequest(): bool
    {
        return in_array($this->request->getMethod(), ['GET', 'HEAD']);
    }

    public function isCacheableResponse(): bool
    {
        return
            // Successful
            $this->response->getStatusCode() === 200 &&
            // Indicates that factors other than request headers influenced the generation of this response.
            // Implies that the response is uncacheable.
            $this->response->getHeaderLine('Vary') !== '*' &&
            // Server doesn't restrict caching
            !$this->cacheControl->noStore() &&
            // Cache is not private
            !$this->cacheControl->private() &&
            (
                // Request is not private or response allows to use public cache anyway
                !$this->request->hasHeader('Authorization') || $this->cacheControl->public()
            );
    }

    public function isNotModifiedResponse(): bool
    {
        return $this->response->getStatusCode() === 304;
    }

    public function getCacheKey(): string
    {
        return md5($this->request->getMethod().$this->request->getUri());
    }

    public function dump()
    {
        dump($this->cache->get($this->getCacheKey()));
    }

    public function hasCachedResponse(): bool
    {
        return (boolean) $this->getCachedResponse();
    }

    public function getCachedResponse(): ?CachedResponse
    {
        $cached = $this->cache->get($this->getCacheKey(), []);

        foreach ($cached as $vary => $value) {
            if ($vary === 0) {
                //dump('Has cached response without vary');
                return $value;
            }

            $headers = $this->unserializeVaryHeaders($vary);
            $matched = true;
            foreach ($headers as $headerName => $headerValue) {
                if ($headerValue !== $this->request->getHeaderLine($headerName)) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                //dump('Has cached response with vary: '.$vary);
                return $value;
            }
        }

        return null;
    }

    public function storeResponse(): CachedResponse
    {
        $cached = $this->cache->get($this->getCacheKey(), []);
        $vary = $this->serializeVaryRequestHeaders($this->response->getHeaderLine('Vary'));

        $serialized = CachedResponse::makeFromResponse($this->response);

        if (!$vary) {
            // No Vary header, single response in cache
            $cached = [$serialized];
        } else {
            // Multiple responses in cache
            $cached[$vary] = $serialized;
        }

        $this->cache->set($this->getCacheKey(), $cached);

        return $this->getCachedResponse();
    }

    public function updateResponse(): CachedResponse
    {
        // Replace headers with fresh copy
        $cached = $this
            ->getCachedResponse()
            ->withHeaders($this->response->getHeaders());

        // Re-store response with new headers
        return $this
            ->withResponse($cached->toResponse())
            ->storeResponse();
    }

    public function serializeVaryRequestHeaders(string $vary): string
    {
        if (!$vary) {
            return '';
        } else {
            $headers = array_map(function ($h) {
                return trim($h);
            }, explode(',', $vary));

            $headers = array_map(
                function ($header) {
                    return $header.': '.$this->request->getHeaderLine($header);
                }, $headers);

            return implode('; ', $headers);
        }
    }

    public function unserializeVaryHeaders(string $vary): array
    {
        $varyHeaders = explode('; ', $vary);

        $headers = [];

        foreach ($varyHeaders as $header) {
            $header = explode(': ', $header);
            $headers[$header[0]] = $header[1];
        }

        return $headers;
    }
}