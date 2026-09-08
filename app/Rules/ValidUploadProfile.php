<?php

namespace App\Rules;

use App\Services\Upload\FileUploadValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ValidUploadProfile implements ValidationRule
{
    public function __construct(private string $profile) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('messages.invalid_upload_file_type'));

            return;
        }

        try {
            app(FileUploadValidationService::class)->validate($value, $this->profile);
        } catch (ValidationException $e) {
            $fail($e->validator->errors()->first('file') ?? __('messages.invalid_upload_file_type'));
        }
    }
}
