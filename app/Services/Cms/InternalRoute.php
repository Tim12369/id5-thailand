<?php

namespace App\Services\Cms;

/**
 * Where a request meant for WordPress is actually sent.
 *
 * Normally that is WordPress's own address. Where WordPress runs in the same
 * container as this application (CMS_INTERNAL_URL), going out to the public
 * address and back in would be a detour — and while a new version is starting
 * it would reach the *old* instance, which is the one still answering the
 * public address. So requests go straight to the local web server instead.
 *
 * Only the destination changes. Addresses stored in content, shown to
 * editors, or compared against stay the public ones.
 */
class InternalRoute
{
    /**
     * The address to connect to for a URL under WordPress's public address.
     */
    public static function url(string $url): string
    {
        [$public, $internal] = self::pair();

        if ($internal === '' || $public === '' || ! str_starts_with($url, $public)) {
            return $url;
        }

        return $internal.substr($url, strlen($public));
    }

    /**
     * Headers that tell WordPress how the request would have arrived from
     * outside. It only accepts an application password over HTTPS; the host's
     * proxy normally reports that, and here nothing else would.
     *
     * @return array<string,string>
     */
    public static function headers(string $url): array
    {
        [$public, $internal] = self::pair();

        if ($internal === '' || ! str_starts_with($url, $public) || ! str_starts_with($public, 'https://')) {
            return [];
        }

        return ['X-Forwarded-Proto' => 'https'];
    }

    /** @return array{0:string,1:string} */
    private static function pair(): array
    {
        return [(string) config('cms.base_url'), rtrim((string) config('cms.internal_url'), '/')];
    }
}
