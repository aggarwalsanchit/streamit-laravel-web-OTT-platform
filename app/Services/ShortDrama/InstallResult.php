<?php

namespace App\Services\ShortDrama;

/**
 * Immutable value object returned by ShortDramaPluginInstaller.
 */
final class InstallResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $log = [],
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'steps' => $this->log,
        ];
    }
}
