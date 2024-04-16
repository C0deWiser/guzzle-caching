<?php

namespace Codewiser\GuzzleCaching;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class CachedResponse
{
    public static function makeFromResponse(ResponseInterface $response): CachedResponse
    {
        return new static(
            $response->getStatusCode(),
            $response->getHeaders(),
            Utils::copyToString($response->getBody())
        );
    }

    protected $statusCode;
    protected $headers;
    protected $body;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    public function toResponse(): ResponseInterface
    {
        return new Response(
            $this->statusCode,
            $this->headers + [
                'X-Cache-Controlled' => [
                    'reused'
                ],
            ],
            $this->body
        );
    }

    /**
     * Check if cached response is fresh.
     */
    public function isFresh(): bool
    {
        $cacheControl = $this->cacheControl();

        $expires = null;

        if ($cacheControl && !is_null($cacheControl->maxAge())) {
            $maxAge = $cacheControl->maxAge() - $this->age();

            if ($date = $this->date()) {
                $expires = $date->add(new \DateInterval('PT' . $maxAge . 'S'));
            }
        } else {
            $expires = $this->expires();
        }

        if ($expires && $expires > new \DateTime()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if cached response is stale.
     */
    public function isStale(): bool
    {
        return !$this->isFresh();
    }

    /**
     * Check if cached response is revalidated conditionally.
     */
    public function isConditional(): bool
    {
        return
            isset($this->headers['ETag']) ||
            isset($this->headers['Last-Modified']);
    }

    /**
     * Get headers for making conditional request
     */
    public function getConditionalHeaders(): array
    {
        $headers = [];

        if (isset($this->headers['ETag'])) {
            $headers['If-None-Match'] = $this->headers['ETag'];
        }

        if (isset($this->headers['Last-Modified'])) {
            $headers['If-Modified-Since'] = $this->headers['Last-Modified'];
        }

        return $headers;
    }

    /**
     * Get Age header value
     */
    public function age(): int
    {
        return ($this->headers['Age'] ?? [])[0] ?? 0;
    }

    /**
     * Get Date header value
     */
    public function date(): ?\DateTimeInterface
    {
        $date = ($this->headers['Date'] ?? [])[0] ?? null;

        return $date ? new \DateTime($date) : null;
    }

    /**
     * Get Expires header value
     */
    public function expires(): ?\DateTimeInterface
    {
        $date = ($this->headers['Expires'] ?? [])[0] ?? null;

        return $date ? new \DateTime($date) : null;
    }

    /**
     * Get Last-Modified header value
     */
    public function lastModified(): ?\DateTimeInterface
    {
        $date = ($this->headers['Last-Modified'] ?? [])[0] ?? null;

        return $date ? new \DateTime($date) : null;
    }

    /**
     * Get ETag header value
     */
    public function etag(): ?string
    {
        return ($this->headers['Etag'] ?? [])[0] ?? null;
    }

    /**
     * Get Cache-Control header object
     */
    public function cacheControl(): ?CacheControlHeader
    {
        return new CacheControlHeader(
            ($this->headers['Cache-Control'] ?? [])[0] ?? ''
        );
    }
}