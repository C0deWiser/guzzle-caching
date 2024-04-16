<?php

namespace Codewiser\GuzzleCaching;

class CacheControlHeader
{
    /**
     * @var array
     */
    protected $values;

    /**
     * @var string
     */
    protected $original;

    public function __construct(string $header)
    {
        $this->original = $header;

        $header = array_map(function (string $s) {
            return trim($s);
        }, explode(',', $header));

        foreach ($header as $value) {
            $value = explode('=', $value);

            if (count($value) == 1) {
                $this->values[$value[0]] = true;
            } else {
                $this->values[$value[0]] = $value[1];
            }
        }
    }

    public function __toString()
    {
        return $this->original;
    }

    /**
     * The max-age=N response directive indicates that the response remains fresh until N seconds after the response is generated.
     *
     * Indicates that caches can store this response and reuse it for subsequent requests while it's fresh.
     *
     * Note that max-age is not the elapsed time since the response was received;
     * it is the elapsed time since the response was generated on the origin server.
     * So if the other cache(s) — on the network route taken by the response — store the response for 100 seconds
     * (indicated using the Age response header field),
     * the browser cache would deduct 100 seconds from its freshness lifetime.
     */
    public function maxAge(): ?int
    {
        return $this->values['max-age'] ?? null;
    }

    /**
     * The s-maxage response directive indicates how long the response remains fresh in a shared cache.
     * The s-maxage directive is ignored by private caches, and overrides the value specified by the max-age directive
     * or the Expires header for shared caches, if they are present.
     */
    public function sMaxAge(): ?int
    {
        return $this->values['s-maxage'] ?? null;
    }

    /**
     * The no-cache response directive indicates that the response can be stored in caches,
     * but the response must be validated with the origin server before each reuse,
     * even when the cache is disconnected from the origin server.
     *
     * If you want caches to always check for content updates while reusing stored content, no-cache is the directive
     * to use. It does this by requiring caches to revalidate each request with the origin server.
     *
     * Note that no-cache does not mean "don't cache". no-cache allows caches to store a response but requires them
     * to revalidate it before reuse. If the sense of "don't cache" that you want is actually "don't store",
     * then no-store is the directive to use.
     */
    public function noCache(): bool
    {
        return $this->values['no-cache'] ?? false;
    }

    /**
     * The must-revalidate response directive indicates that the response can be stored in caches and can be reused
     * while fresh. If the response becomes stale, it must be validated with the origin server before reuse.
     *
     * Typically, must-revalidate is used with max-age.
     *
     * HTTP allows caches to reuse stale responses when they are disconnected from the origin server. must-revalidate
     * is a way to prevent this from happening - either the stored response is revalidated with the origin server
     * or a 504 (Gateway Timeout) response is generated.
     */
    public function mustRevalidate(): bool
    {
        return $this->values['must-revalidate'] ?? false;
    }

    /**
     * The proxy-revalidate response directive is the equivalent of must-revalidate,
     * but specifically for shared caches only.
     */
    public function proxyRevalidate(): bool
    {
        return $this->values['proxy-revalidate'] ?? false;
    }

    /**
     * The no-store response directive indicates that any caches of any kind (private or shared)
     * should not store this response.
     */
    public function noStore(): bool
    {
        return $this->values['no-store'] ?? false;
    }

    /**
     * The private response directive indicates that the response can be stored only in a private cache
     * (e.g. local caches in browsers).
     *
     * You should add the private directive for user-personalized content, especially for responses received after login
     * and for sessions managed via cookies.
     *
     * If you forget to add private to a response with personalized content, then that response can be stored
     * in a shared cache and end up being reused for multiple users, which can cause personal information to leak.
     */
    public function private(): bool
    {
        return $this->values['private'] ?? false;
    }

    /**
     * The public response directive indicates that the response can be stored in a shared cache. Responses for requests
     * with Authorization header fields must not be stored in a shared cache; however, the public directive will cause
     * such responses to be stored in a shared cache.
     *
     * n general, when pages are under Basic Auth or Digest Auth, the browser sends requests with the Authorization
     * header. This means that the response is access-controlled for restricted users (who have accounts),
     * and it's fundamentally not shared-cacheable, even if it has max-age.
     *
     * You can use the public directive to unlock that restriction.
     *
     * Note that s-maxage or must-revalidate also unlock that restriction.
     *
     * If a request doesn't have an Authorization header, or you are already using s-maxage or must-revalidate
     * in the response, then you don't need to use public.
     */
    public function public(): bool
    {
        return $this->values['public'] ?? false;
    }

    /**
     * The must-understand response directive indicates that a cache should store the response only if it understands
     * the requirements for caching based on status code.
     *
     * must-understand should be coupled with no-store for fallback behavior.
     *
     * If a cache doesn't support must-understand, it will be ignored. If no-store is also present,
     * the response isn't stored.
     *
     * If a cache supports must-understand, it stores the response with an understanding of cache requirements based on
     * its status code.
     */
    public function mustUnderstand(): bool
    {
        return $this->values['must-understand'] ?? false;
    }

    /**
     * Some intermediaries transform content for various reasons. For example, some convert images to reduce transfer
     * size. In some cases, this is undesirable for the content provider.
     *
     * no-transform indicates that any intermediary (regardless of whether it implements a cache) shouldn't transform
     * the response contents.
     */
    public function noTransform(): bool
    {
        return $this->values['no-transform'] ?? false;
    }

    /**
     * The immutable response directive indicates that the response will not be updated while it's fresh.
     */
    public function immutable(): bool
    {
        return $this->values['immutable'] ?? false;
    }

    /**
     * The stale-while-revalidate response directive indicates that the cache could reuse a stale response
     * while it revalidates it to a cache.
     */
    public function staleWhileRevalidate(): ?int
    {
        return $this->values['stale-while-validate'] ?? null;
    }

    /**
     * The stale-if-error response directive indicates that the cache can reuse a stale response when an upstream server
     * generates an error, or when the error is generated locally. Here, an error is considered any response
     * with a status code of 500, 502, 503, or 504.
     */
    public function staleIfError(): ?int
    {
        return $this->values['stale-if-error'] ?? null;
    }
}