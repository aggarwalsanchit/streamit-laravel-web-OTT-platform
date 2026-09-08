<?php

namespace App\Services\ShortDrama;

use RuntimeException;

/**
 * Normalizes ZIP entry names and enforces containment under a single module folder
 * (e.g. ShortDrama/) so traversal payloads cannot escape the intended extraction root.
 */
final class InstallZipEntryPath
{
    /**
     * Normalize a raw ZIP entry name to a stable relative path (forward slashes, no leading
     * or trailing slashes). Resolves "." and ".." per path segment; rejects escapes past
     * the root of the relative path.
     *
     * @return string '' when the entry resolves to the archive root (e.g. empty or "./")
     *
     * @throws RuntimeException On null bytes, absolute paths, or traversal past the root.
     */
    public static function normalize(string $rawName): string
    {
        if (str_contains($rawName, "\0")) {
            throw new RuntimeException('ZIP entry name contains a null byte.');
        }

        $name = str_replace('\\', '/', $rawName);

        // Windows absolute: C:\foo, C:/foo
        if (preg_match('#^[a-zA-Z]:[/\\\\]#', $name) === 1) {
            throw new RuntimeException('ZIP entry uses an absolute path: '.$rawName);
        }

        // UNC or leading slash (Unix absolute)
        if (str_starts_with($name, '//') || str_starts_with($name, '\\\\') || str_starts_with($name, '/')) {
            throw new RuntimeException('ZIP entry uses an absolute path: '.$rawName);
        }

        $trimmed = rtrim($name, '/');
        if ($trimmed === '') {
            return '';
        }

        $parts = explode('/', $trimmed);
        $stack = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($stack === []) {
                    throw new RuntimeException('ZIP entry path escapes the extraction root: '.$rawName);
                }
                array_pop($stack);

                continue;
            }
            $stack[] = $part;
        }

        if ($stack === []) {
            return '';
        }

        return implode('/', $stack);
    }

    /**
     * Require normalized path to be exactly {@see $folderName} or a path under {@see $folderName}/.
     *
     * @throws RuntimeException
     */
    public static function assertUnderModuleFolder(string $normalizedRelative, string $folderName): void
    {
        if ($normalizedRelative !== $folderName && ! str_starts_with($normalizedRelative, $folderName.'/')) {
            throw new RuntimeException('ZIP entry is outside '.($folderName.'/').
                ': '.$normalizedRelative);
        }
    }

    /**
     * Join project Modules base with a normalized relative path (must already be validated).
     */
    public static function joinUnderModulesBase(string $modulesBasePath, string $normalizedRelative): string
    {
        $segments = explode('/', $normalizedRelative);
        $path = rtrim($modulesBasePath, DIRECTORY_SEPARATOR);

        foreach ($segments as $segment) {
            if ($segment === '..') {
                throw new RuntimeException('Invalid path segment after normalization.');
            }
            $path .= DIRECTORY_SEPARATOR.$segment;
        }

        return $path;
    }

    /**
     * True if $candidate is $allowedRoot or a path inside it (filesystem-aware separator).
     */
    public static function isPathInsideModuleRoot(string $candidate, string $allowedRoot): bool
    {
        $c = self::normalizePathForComparison($candidate);
        $r = self::normalizePathForComparison($allowedRoot);

        if ($c === $r) {
            return true;
        }

        $prefix = rtrim($r, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($c, $prefix);
    }

    private static function normalizePathForComparison(string $path): string
    {
        $p = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($p) : $p;
    }
}
