<?php

namespace App\Services\ShortDrama;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Safely merges Short Drama paths into the host mix-manifest.
 * Never replaces the full manifest (avoids losing /css/icon.min.css, /js/backend.js, etc.).
 */
class ShortDramaMixManifest
{
    public const SHORT_DRAMA_BUNDLES = ['style.css', 'script.js', 'watch.js'];

    /** Required by backend.layouts.app — not part of the Short Drama ZIP. */
    public const HOST_CORE_KEYS = [
        '/js/backend.js',
        '/css/backend.css',
        '/css/icon.min.css',
        '/css/libs.min.css',
    ];

    public function mergeShortDramaBundles(?string $manifestPath = null): void
    {
        $manifestPath = $manifestPath ?? public_path('mix-manifest.json');

        if (! File::isFile($manifestPath)) {
            throw new RuntimeException(
                'Host mix-manifest.json is missing. From the Streamit site root run: npm install && npm run prod'
            );
        }

        $decoded = json_decode(File::get($manifestPath), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Host mix-manifest.json is invalid JSON.');
        }

        $manifest = $decoded;

        foreach (self::HOST_CORE_KEYS as $key) {
            if (isset($manifest[$key])) {
                continue;
            }

            $publicFile = public_path(ltrim($key, '/'));
            if (! File::isFile($publicFile)) {
                continue;
            }

            $manifest[$key] = $this->resolveManifestValue($key, $manifest);
        }

        $missingCore = [];
        foreach (self::HOST_CORE_KEYS as $key) {
            if (! isset($manifest[$key])) {
                $missingCore[] = $key;
            }
        }

        if ($missingCore !== []) {
            throw new RuntimeException(
                'Host Streamit assets are incomplete (missing mix entries: '
                .implode(', ', $missingCore)
                .'). From the site root run: npm install && npm run prod'
            );
        }

        foreach (self::SHORT_DRAMA_BUNDLES as $name) {
            $key = '/modules/shortdrama/'.$name;
            $manifest[$key] = $this->resolveManifestValue($key, $manifest);
        }

        ksort($manifest);

        File::put(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    private function resolveManifestValue(string $key, array $manifest): string
    {
        if (isset($manifest[$key]) && is_string($manifest[$key]) && $manifest[$key] !== '') {
            return $manifest[$key];
        }

        $publicFile = public_path(ltrim($key, '/'));
        if (File::isFile($publicFile)) {
            return $key.'?id='.md5_file($publicFile);
        }

        return $key;
    }
}
