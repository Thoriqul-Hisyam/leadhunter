<?php

use App\Helpers\Phone;
use App\Helpers\Url;

test('urls are normalised and unsafe schemes rejected', function (?string $input, ?string $expected) {
    expect(Url::normalize($input))->toBe($expected);
})->with([
    ['kopisenja.id', 'https://kopisenja.id'],
    ['http://kopisenja.id/menu', 'http://kopisenja.id/menu'],
    ['  ', null],
    [null, null],
    ['javascript:alert(1)', null],
    ['data:text/html,hi', null],
    ['ftp://files.example.com', null],
]);

test('only public http hosts are considered safe to crawl', function (string $url, bool $safe) {
    $resolver = fn (string $host) => match ($host) {
        'public.example' => ['93.184.216.34'],
        'internal.example' => ['10.0.0.5'],
        default => [],
    };

    expect(Url::isPublicHttpUrl($url, $resolver))->toBe($safe);
})->with([
    ['https://public.example', true],
    ['https://internal.example', false],
    ['https://unknown.example', false],
    ['http://127.0.0.1', false],
    ['http://[::1]/', false],
    ['http://localhost', false],
    ['http://169.254.169.254', false],
    ['file:///etc/passwd', false],
    ['http://8.8.8.8', true],
]);

test('indonesian phone numbers are converted for whatsapp', function (?string $input, ?string $expected) {
    expect(Phone::toWhatsApp($input))->toBe($expected);
})->with([
    ['0812-3456-789', '628123456789'],
    ['+62 812 3456 789', '628123456789'],
    ['812 3456 789', '628123456789'],
    ['0062 812 3456 789', '628123456789'],
    ['(031) 123-4567', '62311234567'],
    ['123', null],
    [null, null],
]);
