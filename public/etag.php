<?php

$headers = getallheaders();

if (isset($headers['If-None-Match'])) {
    header('X-Test: 304');
    http_response_code(304);
} else {
    http_response_code(200);
}

header('Cache-Control: no-cache');

$etag = '1234';

header('ETag: '.$etag);

echo 'Foo Bar';
