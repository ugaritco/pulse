<?php

use Ugarit\Pulse\Recorders\SlowOutgoingRequests;

it('can normalize URLs', function (string $given, string $expected) {
    expect(SlowOutgoingRequests::normalizeUrl($given))->toBe($expected);
})->with([
    ['https://httpbin.org/delay/2', 'https://httpbin.org/delay/2'],
    ['https://ugarit@httpbin.org/delay/2', 'https://@httpbin.org/delay/2'],
    ['https://ugarit:password@httpbin.org/delay/2', 'https://@httpbin.org/delay/2'],
]);
