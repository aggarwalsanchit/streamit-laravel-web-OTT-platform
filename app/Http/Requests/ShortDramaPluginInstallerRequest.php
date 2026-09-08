<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class ShortDramaPluginInstallerRequest extends FormRequest
{
    /** Laravel `max` rule value in kilobytes (50 MB). */
    public const MAX_ZIP_KB = 51200;

    public const MAX_ZIP_BYTES = 50 * 1024 * 1024;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'addon_zip' => [
                'required',
                'file',
                'mimes:zip',
                'max:'.self::MAX_ZIP_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'addon_zip.required' => __('messages.short_drama_plugin_zip_required'),
            'addon_zip.mimes' => __('messages.short_drama_plugin_zip_mimes'),
            'addon_zip.max' => __('messages.short_drama_plugin_zip_max', ['max' => '50 MB']),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('addon_zip')) {
                return;
            }

            if (! $this->isMethod('POST')) {
                return;
            }

            $uploadError = $_FILES['addon_zip']['error'] ?? UPLOAD_ERR_NO_FILE;

            if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $validator->errors()->add('addon_zip', $this->phpUploadLimitMessage());

                return;
            }

            $contentLength = (int) $this->server('CONTENT_LENGTH', 0);

            if ($contentLength > 0 && $contentLength > $this->serverPostMaxBytes()) {
                $validator->errors()->add('addon_zip', $this->phpUploadLimitMessage());
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->ajax()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first('addon_zip'),
                'errors' => $validator->errors(),
            ], 422));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    private function phpUploadLimitMessage(): string
    {
        $uploadMax = ini_get('upload_max_filesize') ?: 'unknown';
        $postMax = ini_get('post_max_size') ?: 'unknown';

        return __('messages.short_drama_plugin_upload_php_limit', [
            'upload_max' => $uploadMax,
            'post_max' => $postMax,
            'app_max' => '50 MB',
        ]);
    }

    private function serverPostMaxBytes(): int
    {
        return $this->parseIniSize(ini_get('post_max_size'));
    }

    private function parseIniSize(string|false $value): int
    {
        if ($value === false || $value === '') {
            return PHP_INT_MAX;
        }

        $value = trim((string) $value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
