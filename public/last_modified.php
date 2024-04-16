<?php

$headers = getallheaders();

if (isset($headers['If-Modified-Since'])) {
    header('X-Test: 304');
    http_response_code(304);
} else {
    http_response_code(200);
}

header('Cache-Control: no-cache');

$lm = new \DateTime('2024-03-03T10:00:00');

header('Last-Modified: '.$lm->format('r'));

echo 'Foo Bar';
