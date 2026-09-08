<?php

namespace App\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadValidationService
{
    public function validate(UploadedFile $file, string $profile): UploadedFile
    {
        if ($profile === 'media_library') {
            $profile = $this->resolveMediaLibraryProfile($file);
        }

        $config = $this->getProfile($profile);

        if ($this->hasDangerousExtension($file->getClientOriginalName())) {
            $this->logRejection($file, $profile, 'dangerous_extension');

            throw ValidationException::withMessages([
                'file' => [__('messages.invalid_upload_file_type')],
            ]);
        }

        $rules = $this->rulesFor($profile);

        try {
            Validator::make(['file' => $file], ['file' => $rules])->validate();
        } catch (ValidationException $e) {
            $this->logRejection($file, $profile, 'laravel_validator');

            throw $e;
        }

        $detected = $this->detectMimeType($file);

        if (! $this->mimeMatchesProfile($detected, $config['mime_types'], $file, $profile)) {
            $this->logRejection($file, $profile, 'mime_mismatch', $detected);

            throw ValidationException::withMessages([
                'file' => [__('messages.invalid_upload_file_type')],
            ]);
        }

        if (! $this->runContentChecks($file, $config['content_checks'] ?? [])) {
            $this->logRejection($file, $profile, 'content_check_failed', $detected);

            throw ValidationException::withMessages([
                'file' => [__('messages.invalid_upload_file_type')],
            ]);
        }

        return $file;
    }

    public function validateMany(array $files, string $profile): array
    {
        return array_map(fn ($file) => $this->validate($file, $profile), $files);
    }

    public function resolveMediaLibraryProfile(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $imageExts = config('upload_profiles.profiles.image.extensions', []);
        $videoExts = config('upload_profiles.profiles.video.extensions', []);

        if (in_array($ext, $videoExts, true)) {
            return 'video';
        }

        if (in_array($ext, $imageExts, true)) {
            return 'image';
        }

        $detected = $this->detectMimeType($file);
        $imageMimes = config('upload_profiles.profiles.image.mime_types', []);
        $videoMimes = config('upload_profiles.profiles.video.mime_types', []);

        if (in_array($detected, $imageMimes, true)) {
            return 'image';
        }

        if (in_array($detected, $videoMimes, true)) {
            return 'video';
        }

        throw ValidationException::withMessages([
            'file' => [__('messages.invalid_upload_file_type')],
        ]);
    }

    public function hasDangerousExtension(string $filename): bool
    {
        $basename = basename(str_replace(['\\', "\0"], '', $filename));
        $dangerous = array_map('strtolower', config('upload_profiles.dangerous_extensions', []));
        $segments = array_map('strtolower', explode('.', $basename));

        foreach ($segments as $segment) {
            if (in_array($segment, $dangerous, true)) {
                return true;
            }
        }

        return false;
    }

    public function sanitizeFilename(UploadedFile $file, string $profile): string
    {
        if ($profile === 'media_library') {
            $profile = $this->resolveMediaLibraryProfile($file);
        }

        $config = $this->getProfile($profile);
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $config['extensions'], true)) {
            $ext = $config['extensions'][0];
        }

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return Str::slug($base).'-'.time().'.'.$ext;
    }

    public function rulesFor(string $profile, bool $required = true): array
    {
        if ($profile === 'media_library') {
            throw new \InvalidArgumentException('Use validate() for media_library profile instead of rulesFor().');
        }

        $config = $this->getProfile($profile);
        $rules = ['file', 'max:'.$config['max_kb']];

        if ($this->usesExtensionOnlyValidation($profile, $config)) {
            $allowedExtensions = $config['extensions'];
            $rules[] = function ($attribute, $value, $fail) use ($allowedExtensions) {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());

                if (! in_array($ext, $allowedExtensions, true)) {
                    $fail(__('messages.invalid_upload_file_type'));
                }
            };
        } else {
            $rules[] = 'mimes:'.implode(',', $config['extensions']);
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    private function detectMimeType(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($file->getRealPath()) ?: 'application/octet-stream';
    }

    private function mimeMatchesProfile(string $detected, array $allowed, UploadedFile $file, string $profile): bool
    {
        if (in_array($detected, $allowed, true)) {
            return true;
        }

        if ($detected === 'application/octet-stream' && $profile === 'video') {
            return $this->passesVideoMagicBytes($file);
        }

        return false;
    }

    private function usesExtensionOnlyValidation(string $profile, array $config): bool
    {
        if ($profile === 'video') {
            return true;
        }

        return ! in_array('image_decode', $config['content_checks'] ?? [], true);
    }

    private function passesVideoMagicBytes(UploadedFile $file): bool
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 8192);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return false;
        }

        if (str_starts_with($header, "\x1a\x45\xdf\xa3")) {
            return true;
        }

        if (strlen($header) >= 12 && str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'AVI ') {
            return true;
        }

        if (strlen($header) >= 8 && substr($header, 4, 4) === 'ftyp') {
            return true;
        }

        if (str_starts_with($header, 'FLV')) {
            return true;
        }

        if (str_starts_with($header, "\x47")) {
            return true;
        }

        if (str_starts_with($header, "\x00\x00\x01")) {
            return true;
        }

        // Some MP4/MOV files use a variable-size atom before ftyp.
        return str_contains($header, 'ftyp');
    }

    private function runContentChecks(UploadedFile $file, array $contentChecks): bool
    {
        foreach ($contentChecks as $check) {
            $passed = match ($check) {
                'image_decode' => $this->passesImageDecode($file),
                'json_valid' => $this->passesJsonValid($file),
                'text_utf8' => $this->passesTextUtf8($file),
                default => false,
            };

            if (! $passed) {
                return false;
            }
        }

        return true;
    }

    private function passesImageDecode(UploadedFile $file): bool
    {
        $info = @getimagesize($file->getRealPath());

        return $info !== false && isset($info[0], $info[1]) && $info[0] > 0 && $info[1] > 0;
    }

    private function passesJsonValid(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getRealPath());
        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function passesTextUtf8(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getRealPath());

        if (str_contains($content, "\0")) {
            return false;
        }

        return mb_check_encoding($content, 'UTF-8');
    }

    private function getProfile(string $profile): array
    {
        $config = config("upload_profiles.profiles.{$profile}");

        if (! $config) {
            throw new \InvalidArgumentException("Unknown upload profile: {$profile}");
        }

        return $config;
    }

    private function logRejection(
        UploadedFile $file,
        string $profile,
        string $reason,
        ?string $detectedMime = null
    ): void {
        Log::warning('Upload rejected', [
            'profile' => $profile,
            'reason' => $reason,
            'client_filename' => $file->getClientOriginalName(),
            'client_mime' => $file->getClientMimeType(),
            'detected_mime' => $detectedMime,
            'ip' => request()->ip(),
        ]);
    }
}
