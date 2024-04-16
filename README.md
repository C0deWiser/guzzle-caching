# Caching Guzzle

This package brings caching layer to the Guzzle library. Cache is managed by
response headers, such as `Cache-Control` and others.

## Usage

Add two middlewares to the Guzzle default handler stack. One to the top, next to
the bottom.

First middleware will examine existing caches and reuse cached requests.
Otherwise, it may add conditional headers to the request.

Second middleware will examine server response and cache it, if required.

```php
use Codewiser\GuzzleCaching\CacheControlStorage;
use Codewiser\GuzzleCaching\Middlewares\ReuseCachedResponse;
use Codewiser\GuzzleCaching\Middlewares\CacheResponse;

$cache = // Any Psr\SimpleCache\CacheInterface
$storage = CacheControlStorage::make($cache);

$handler = \GuzzleHttp\HandlerStack::create();
$handler->unshift(new ReuseCachedResponse($storage), 'reuse_cached');
$handler->push(new CacheResponse($storage), 'cache_response');

$client = new \GuzzleHttp\Client([
    'handler' => $handler,
    'base_uri' => 'https://example.com'
]);
```

## Testing

Start web-server before running tests:

```shell
php -S localhost:8000 -t public/
```

## Cache management

### Stored responses states

Stored HTTP responses have two states: `fresh` and `stale`. The `fresh` state
usually indicates that the response is still valid and can be reused, while
the `stale`
state means that the cached response has already expired.

The criterion for determining when a response is `fresh` and when it is `stale`
is age. In HTTP, age is the time elapsed since the response was generated. This
is similar to the TTL in other caching mechanisms.

`max-age` directive is not the elapsed time since the response was received; it
is the elapsed time since the response was generated on the origin
server (`Date` response header). So if the other cache(s) — on the network route
taken by the response — store the response for 100 seconds (indicated using
the `Age` response header field), the client's cache would deduct 100 seconds
from its freshness lifetime.

The `Expires` HTTP header contains the date/time after which the response is
considered expired.

If there is a `Cache-Control` header with the `max-age` directive in the
response, the `Expires` header is ignored.

### Caching keys

Requests are differs by method and uri (we will cache only `GET` and `HEAD`
requests).

The `Vary` HTTP response header describes the parts of the request message aside
from the method and URL that influenced the content of the response it occurs
in. Most often, this is used to create a cache key when content negotiation is
in use.

## Caching directives

We will cache only `GET` and `HEAD` requests and `200` responses.

### `no-store`

The `no-store` response directive indicates that any caches of any kind (private
or shared) should not store this response.

Caching is disabled.

### `private`

The `private` response directive indicates that the response can be stored only
in a private cache (e.g. local caches in browsers).

We will not cache such a responses.

### `public`

The `public` response directive indicates that the response can be stored in a
shared cache. Responses for requests with `Authorization` header fields must not
be stored in a shared cache; however, the public directive will cause such
responses to be stored in a shared cache.

In general, when pages are under Basic Auth or Digest Auth, the browser sends
requests with the `Authorization` header. This means that the response is
access-controlled for restricted users (who have accounts), and it's
fundamentally not shared-cacheable, even if it has `max-age`.

### `no-cache`

The `no-cache` response directive indicates that the response can be stored in
caches, but the response must be validated with the origin server before each
reuse, even when the cache is disconnected from the origin server.

Synonym of `Cache-Control: max-age=0, must-revalidate`.

### `max-age`

The `max-age=N` response directive indicates that the response remains `fresh`
until N seconds after the response is generated.

## Revalidation

HTTP allows caches to reuse `stale` responses when they are disconnected from
the origin server. `must-revalidate` is a way to prevent this from happening -
either the stored response is revalidated with the origin server or a 504 (
Gateway Timeout) response is generated.

(To be implemented):
The `stale-while-revalidate` response directive indicates that the cache could
reuse a `stale` response while it revalidates it to a cache.

(To be implemented):
The `stale-if-error` response directive indicates that the cache can reuse a
`stale` response when an upstream server generates an error, or when the error is
generated locally. Here, an error is considered any response with a status code
of `500`, `502`, `503`, or `504`.

`Stale` responses are not immediately discarded. HTTP has a mechanism to
transform a `stale` response into a `fresh` one by asking the origin server.

Validation is done by using a conditional request that includes an
`If-Modified-Since` or `If-None-Match` request header.