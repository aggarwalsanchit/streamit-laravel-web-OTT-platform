<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates spreadsheet import uploads by extension instead of guessed MIME type.
 * The built-in mimes rule often rejects valid CSV / Excel uploads when finfo reports
 * text/plain, application/octet-stream, or application/zip (xlsx) depending on OS and libmagic.
 */
class ImportSpreadsheetFile implements ValidationRule
{
    /** @param  list<string>  $extensions  Lowercase extensions without dots */
    public function __construct(
        protected array $extensions = ['csv', 'xlsx', 'xls'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        if (! $value->isValid()) {
            return;
        }

        $ext = strtolower($value->getClientOriginalExtension());
        if ($ext === '' && method_exists($value, 'getClientOriginalName')) {
            $name = strtolower((string) $value->getClientOriginalName());
            $ext = pathinfo($name, PATHINFO_EXTENSION);
        }

        if (! in_array($ext, $this->extensions, true)) {
            $fail(__('validation.mimes', [
                'attribute' => $attribute,
                'values' => implode(', ', $this->extensions),
            ]));
        }
    }
}
