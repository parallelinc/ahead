<?php

declare(strict_types=1);

test('robots.txt disallows all crawlers from the entire site', function (): void {
    $contents = file_get_contents(public_path('robots.txt'));

    expect($contents)->toContain('User-agent: *');
    expect($contents)->toContain('Disallow: /');
});
