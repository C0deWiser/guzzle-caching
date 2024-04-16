# Caching Guzzle

This package bring caching layer to the Guzzle library. 
Caching managed by response headers, such as `Cache-Control` and others.

## Usage

Add two middlewares to the Guzzle default handler stack. 
One to the top, next to the bottom.

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

## Состояние кеша

Сохраненный ответ может быть в двух состояниях: `fresh` или `stale`.

«Свежесть» или «тухлость» определяется исходя из «возраста» 
— времени, прошедшего с момента создания ответа на сервере.

Период «свежести» задается значением директивы `Cache-Control: max-age=<seconds>`.
Или, если `max-age` не передан, значением заголовка ответа `Expires`.

Возраст вычисляется относительно заголовка ответа `Date`.
Если в ответе сервера есть заголовок `Age`, он уменьшает значение директивы `max-age`.

## Ключ кеширования

Непосредственным ключом для кеширования является комбинация метода и адреса запроса (только `GET` и `HEAD`).
Однако сервер может вернуть заголовок `Vary`, в котором будут перечислены заголовки, оказывающие влияние на ключ кеширования.

Например, если `Vary: Accept-Language`, то запросы к этому ресурсу с разными `Accept-Language`,
должны кешироваться отдельно друг от друга.

## Стратегии ревалидации

Кешируем только `GET` и `HEAD` запросы и `200` ответы.

### `no-store`

Если сервер вернул `Cache-Control: no-store` — кеширование не производится, сохраненный кеш удаляется.
Остальные директивы игнорируются.

### `private`

Директива `Cache-Control: private` указывает, что ответ содержит персональные данные и не должен быть в общем кеше.
Во избежание проблем, мы будем считать её синонимом `Cache-Control: no-store`.

### `public`

Если запрос содержит заголовок `Authorization`, то ответ может быть сохранен 
только при наличии в ответе директивы `Cache-Control: public`.

### `no-cache`

Если сервер вернул `Cache-Control: no-cache` - кешируем ответ, но каждый раз он должен быть перепроверен на сервере.
Подразумеваются условные проверки `In-None-Match` и `If-Modified-Since`.
То есть, если в ответе не было `ETag` или `Last-Modified` мы делаем полный запрос к серверу, ожидая только `200`.
Если же мы сделали условный запрос, то, получив `304`, можем использовать ранее сохраненный ответ.

Директивы `Cache-Control: max-age=0, must-revalidate` являются синонимом `Cache-Control: no-cache`.

### `max-age`

Эта директива в отсутствие других разрешает использование сохраненного ответа, пока он сохраняет «свежесть».

После утраты «свежести» ответ желательно бы актуализировать. 
Если нам известны `ETag` или `Last-Modified` — делаем условную проверку. Иначе — полную.

Но если сервер недоступен или отвечает `5**` — разрешается использовать несвежий кеш.
Но в комбинации с `must-revalidate` использование несвежего кеша строго запрещено.

На директиву `must-revalidate` влияют только `stale-while-revalidate=<seconds>`,
которая разрешает использование несвежего кеша, если сервер не отвечает, 
и `stale-if-error=<seconds>`, которая разрешает использование несвежего кеша, 
если сервер `5**`.