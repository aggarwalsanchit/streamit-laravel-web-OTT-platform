<?php

namespace Modules\Entertainment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Validator as LaravelValidator;
use Illuminate\Validation\ValidationException;

class EntertainmentRequest extends FormRequest
{
    /**
     * Decode Base64-encoded embed fields before validation.
     * The JS encodes them with btoa() to bypass LiteSpeed WAF/ModSecurity
     * which blocks raw <iframe> HTML in POST bodies.
     */
    protected function prepareForValidation()
    {
        $merge = [];

        // Decode trailer_embedded
        $trailerEmbedded = $this->input('trailer_embedded');
        if ($trailerEmbedded && !str_contains($trailerEmbedded, '<iframe')) {
            $decoded = base64_decode($trailerEmbedded, true);
            if ($decoded !== false) {
                $merge['trailer_embedded'] = $decoded;
            }
        }

        // Decode video_embedded
        $videoEmbedded = $this->input('video_embedded');
        if ($videoEmbedded && !str_contains($videoEmbedded, '<iframe')) {
            $decoded = base64_decode($videoEmbedded, true);
            if ($decoded !== false) {
                $merge['video_embedded'] = $decoded;
            }
        }

        // Decode quality_video_embed_input[] array
        $qualityEmbeds = $this->input('quality_video_embed_input', []);
        if (is_array($qualityEmbeds)) {
            $decoded = [];
            foreach ($qualityEmbeds as $key => $val) {
                if ($val && !str_contains($val, '<iframe')) {
                    $dec = base64_decode($val, true);
                    $decoded[$key] = ($dec !== false) ? $dec : $val;
                } else {
                    $decoded[$key] = $val;
                }
            }
            $merge['quality_video_embed_input'] = $decoded;
        }

        // Decode clip_embedded[] array
        $clipEmbeds = $this->input('clip_embedded', []);
        if (is_array($clipEmbeds)) {
            $decoded = [];
            foreach ($clipEmbeds as $key => $val) {
                if ($val && !str_contains($val, '<iframe')) {
                    $dec = base64_decode($val, true);
                    $decoded[$key] = ($dec !== false) ? $dec : $val;
                } else {
                    $decoded[$key] = $val;
                }
            }
            $merge['clip_embedded'] = $decoded;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }



    public function rules()
    {
        $id = request()->id;
        $rules = [
            'name' => ['required',Rule::unique('entertainments', 'name')->ignore($id)],
            'trailer_url_type' => ['required'],
            'movie_access' => 'required',
            'language' => ['required'],
            'genres' => ['required'],
            'content_rating' => 'required|string',
            'actors' => ['required'],
            'directors' => ['required'],
            'IMDb_rating' => 'nullable|numeric|min:1|max:10',
            'description' => ['required', 'string'],
        ];

        $movieAccess = $this->input('movie_access');
        
        // Release date is only required when NOT pay-per-view
        if ($movieAccess !== 'pay-per-view') {
            // Release date is only required when NOT pay-per-view
        if ($movieAccess !== 'pay-per-view') {
            $rules['release_date'] = ['required'];
        }
        }
        $trailerUrlType = $this->input('trailer_url_type');

        if ($trailerUrlType == 'Embedded') {
            $rules['trailer_embedded'] = ['required','regex:/<iframe[\s\S]*?src\s*=\s*["\'\x{201c}\x{201d}\x{2018}\x{2019}](.*?)["\'\x{201c}\x{201d}\x{2018}\x{2019}][\s\S]*?<\/iframe>/iu'];
        } else if ($trailerUrlType == 'Local') {
            $rules['trailer_video'] = ['required'];
        } else if ($trailerUrlType == 'URL' || $trailerUrlType == 'HLS' || $trailerUrlType == 'x265') {
            $rules['trailer_url'] = ['required','regex:/^https?:\/\/.+$/'];
        } else if ($trailerUrlType == 'YouTube') {
            $rules['trailer_url'] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($trailerUrlType == 'Vimeo') {
            $rules['trailer_url'] = ['required','regex:/^(https?:\/\/)?(www\.)?vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+(\?.*)?$/'];
        }

        $videoUrlType = $this->input('video_upload_type');

        if ($videoUrlType == 'Embedded') {
            $rules['video_embedded'] = ['required','regex:/<iframe[\s\S]*?src\s*=\s*["\'\x{201c}\x{201d}\x{2018}\x{2019}](.*?)["\'\x{201c}\x{201d}\x{2018}\x{2019}][\s\S]*?<\/iframe>/iu'];
        } else if ($videoUrlType == 'Local') {
            $rules['video_file_input'] = ['required'];
        } else if ($videoUrlType == 'URL' || $videoUrlType == 'HLS' || $videoUrlType == 'x265') {
            $rules['video_url_input'] = ['required','regex:/^https?:\/\/.+$/'];
        } else if ($videoUrlType == 'YouTube') {
            $rules['video_url_input'] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($videoUrlType == 'Vimeo') {
            $rules['video_url_input'] = ['required','regex:/^(https?:\/\/)?(www\.)?vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+(\?.*)?$/'];
        }

        if ($movieAccess == 'paid') {
            $rules['plan_id'] = 'required';
        } elseif ($movieAccess === 'pay-per-view') {
            $rules['price'] = 'required|numeric';
            $rules['purchase_type'] = 'required|string|in:rental,onetime';
            $rules['available_for'] = 'required|integer|min:1';
            
            // Access duration is only required for rental purchases
            $purchaseType = $this->input('purchase_type');
            if ($purchaseType === 'rental') {
                $rules['access_duration'] = 'required|integer|min:1';
            }
        }

        if ($this->input('type') == 'movie') {
            $rules['duration'] = 'required';
            $rules['video_upload_type'] = 'required';
        }

        // Validate quality-wise video URLs based on video_quality_type
        if ($this->has('video_quality_type') && is_array($this->input('video_quality_type'))) {
            $videoQualityTypes = $this->input('video_quality_type', []);
            foreach ($videoQualityTypes as $index => $qualityType) {
                if ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^https?:\/\/.+$/'];
                } else if ($qualityType == 'YouTube') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
                } else if ($qualityType == 'Vimeo') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^(https?:\/\/)?(www\.)?vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+(\?.*)?$/'];
                } else if ($qualityType == 'Embedded') {
                    $rules["quality_video_embed_input.{$index}"] = ['required','regex:/<iframe[\s\S]*?src\s*=\s*["\'\x{201c}\x{201d}\x{2018}\x{2019}](.*?)["\'\x{201c}\x{201d}\x{2018}\x{2019}][\s\S]*?<\/iframe>/iu'];
                } else if ($qualityType == 'Local') {
                    $rules["quality_video.{$index}"] = ['required'];
                }
            }
        }

        // Subtitle validation when enable_subtitle is on
        if ($this->has('enable_subtitle') && $this->enable_subtitle == 1) {
            $rules['subtitles.*.language'] = 'required|string';
            // Check if this is an update request (has ID parameter)
            $isUpdate = $this->has('id') && !empty($this->input('id'));

            if ($isUpdate) {
                // Update form - files are optional (existing files can be kept)
                $rules['subtitles.*.subtitle_file'] = 'nullable|file|max:10240';
            } else {
                // Create form - require files
                $rules['subtitles.*.subtitle_file'] = 'required|file|max:10240';
            }
        }

        if ($this->has('enable_clips') && $this->enable_clips == 1) {
            $rules['clip_title.*'] = 'required|string|max:255';
            $rules['clip_upload_type.*'] = 'required|string';
            $rules['clip_poster_url.*'] = 'required|string';
            $rules['clip_tv_poster_url.*'] = 'required|string';

                // Validate clip content based on upload type
                $clipUploadTypes = $this->input('clip_upload_type', []);
                foreach ($clipUploadTypes as $index => $uploadType) {
                    if ($uploadType == 'URL' || $uploadType == 'HLS' || $uploadType == 'x265') {
                        $rules["clip_url_input.{$index}"] = ['required','string','regex:/^https?:\/\/.+$/'];
                    } elseif ($uploadType == 'YouTube') {
                        $rules["clip_url_input.{$index}"] = ['required','string','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
                    } elseif ($uploadType == 'Vimeo') {
                        $rules["clip_url_input.{$index}"] = ['required','string','regex:/^(https?:\/\/)?(www\.)?vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+(\?.*)?$/'];
                    } elseif ($uploadType == 'Local') {
                        $rules["clip_file_input.{$index}"] = ['required','string'];
                    } elseif ($uploadType == 'Embedded') {
                        $rules["clip_embedded.{$index}"] = ['required','string','regex:/<iframe[\s\S]*?src\s*=\s*["\'\x{201c}\x{201d}\x{2018}\x{2019}](.*?)["\'\x{201c}\x{201d}\x{2018}\x{2019}][\s\S]*?<\/iframe>/iu'];
                    }
                }
        }

        $download_status = $this->input('download_status');
        $download_type = $this->input('video_upload_type_download');

        if ($download_status == 1) {
            $rules['video_upload_type_download'] = ['required'];
        }
        if ($download_status == 1 && $download_type == 'Local') {
            $rules['video_file_input_download'] = ['required'];
        } else if ($download_status == 1 && $download_type == 'URL') {
            $rules['video_url_input_download'] = ['required','regex:/^https?:\/\/.+$/'];
        }
        if($download_status == 1) {
            if ($this->has('enable_download_quality') && $this->enable_download_quality == 1) {
                $rules['quality_video_download_type.*'] = 'required|string';
                $rules['video_download_quality.*'] = 'required|string';

                // Validate download quality content based on upload type
                $downloadQualityTypes = $this->input('quality_video_download_type', []);
                foreach ($downloadQualityTypes as $index => $qualityType) {
                    if ($qualityType == 'URL') {
                        $rules["download_quality_video_url.{$index}"] = ['required','regex:/^https?:\/\/.+$/'];
                    } elseif ($qualityType == 'Local') {
                        $rules["download_quality_video.{$index}"] = ['required'];
                    }
                }
            }
        }

        if ($this->has('enable_seo') && $this->enable_seo == 1) {
            $rules = array_merge($rules, [
                'meta_title' => [
                        'required',
                        'string',
                        'max:100',
                        Rule::unique('entertainments', 'meta_title')->ignore($id),
                    ],
                'google_site_verification' => 'required',
                'meta_keywords' => 'required|max:255',
                'canonical_url' => 'required',
                'short_description' => 'required|string|max:200',
                'seo_image' => 'required',
            ]);
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'name.required' => __('messages.name_required'),
            'name.unique' => __('messages.name_already_exists'),
            'language.required' => __('messages.language_required'),
            'genres.required' => __('messages.genres_required'),
            'actors.required' => __('messages.actors_required'),
            'directors.required' => __('messages.directors_required'),
            'duration.required' => __('messages.duration_required'),
            'video_upload_type.required' => __('messages.video_type_required'),
            'release_date.required' => __('messages.release_date_required'),
            'description.required' => __('messages.description_required'),
            'IMDb_rating.required' => __('messages.imdb_rating_required'),
            'IMDb_rating.numeric' => __('messages.imdb_rating_must_be_number'),
            'IMDb_rating.min' => __('messages.imdb_rating_min'),
            'IMDb_rating.max' => __('messages.imdb_rating_max'),
            'description.string' => __('messages.description_must_be_string'),
            'discount.required' => __('messages.discount_required'),
            'discount.min' => __('messages.discount_min'),
            'discount.max' => __('messages.discount_max'),
            'access_duration.required' => __('messages.access_duration_required'),
            'access_duration.integer' => __('messages.access_duration_must_be_number'),
            'access_duration.min' => __('messages.access_duration_min'),
            'available_for.integer' => __('messages.available_for_must_be_number'),
            'available_for.min' => __('messages.available_for_min'),
            'purchase_type.required' => __('messages.purchase_type_required'),
            'purchase_type.in' => __('messages.purchase_type_must_be_rental_or_onetime'),
            'price.required' => __('messages.price_required'),
            'price.numeric' => __('messages.price_must_be_number'),
            'trailer_url_type.required' => __('messages.trailer_url_type_required'),
            'trailer_embedded.required' => __('messages.trailer_embedded_required'),
            'trailer_embedded.regex' => __('messages.trailer_embedded_regex'),
            'trailer_video.required' => __('messages.trailer_video_required'),
            'trailer_url.required' => __('messages.trailer_url_required'),
            'trailer_url.regex' => __('messages.trailer_url_regex'),
            'embedded.required' => __('messages.video_embedded_required'),
            'embedded.regex' => __('messages.video_embedded_regex'),
            'video_file_input.required' => __('messages.video_file_required'),
            'video_url_input.required' => __('messages.video_url_required'),
            'video_url_input.regex' => __('messages.video_url_regex'),
            'video_upload_type_download.required' => __('messages.download_video_type_required'),
            'video_url_input_download.required' => __('messages.download_video_file_required'),
            'video_file_input_download.required' => __('messages.download_video_url_required'),
            'video_file_input_download.regex' => __('messages.download_video_url_regex'),
            'enable_download_quality.required' => __('messages.enable_download_quality_required'),
            'quality_video_download_type.required' => __('messages.download_quality_type_required'),
            'video_download_quality.required' => __('messages.download_quality_required'),
            'download_quality_video_url.required' => __('messages.download_quality_url_required'),
            'download_quality_video.required' => __('messages.download_quality_file_required'),
            'content_rating.required' => __('messages.content_rating_required'),
            'plan_id.required' => __('messages.plan_id_required'),
            'movie_access.required' => __('messages.movie_access_required'),
        ];

        // Custom formatting for quality video URL errors
        if ($this->has('video_quality_type') && is_array($this->input('video_quality_type'))) {
            $videoQualityTypes = $this->input('video_quality_type', []);
            foreach ($videoQualityTypes as $index => $qualityType) {
                $qualityNumber = $index + 1;
                if ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_url_required', ['number' => $qualityNumber]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_url_regex', ['number' => $qualityNumber]);
                } else if ($qualityType == 'YouTube') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_url_required', ['number' => $qualityNumber]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_youtube_regex', ['number' => $qualityNumber]);
                } else if ($qualityType == 'Vimeo') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_url_required', ['number' => $qualityNumber]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_vimeo_regex', ['number' => $qualityNumber]);
                } else if ($qualityType == 'Embedded') {
                    $messages["quality_video_embed_input.{$index}.required"] = __('messages.quality_video_embed_required', ['number' => $qualityNumber]);
                    $messages["quality_video_embed_input.{$index}.regex"] = __('messages.quality_video_embed_regex', ['number' => $qualityNumber]);
                } else if ($qualityType == 'Local') {
                    $messages["quality_video.{$index}.required"] = __('messages.quality_video_file_required', ['number' => $qualityNumber]);
                }
            }
        }

        // Custom formatting for subtitle errors
        if ($this->has('enable_subtitle') && $this->enable_subtitle == 1) {
            $subtitles = $this->input('subtitles', []);
            foreach ($subtitles as $index => $subtitle) {
                $subtitleNumber = $index + 1;
                $messages["subtitles.{$index}.language.required"] = __('messages.subtitle_language_required', ['number' => $subtitleNumber]);
                $messages["subtitles.{$index}.language.string"] = __('messages.subtitle_language_string', ['number' => $subtitleNumber]);
                $messages["subtitles.{$index}.subtitle_file.required"] = __('messages.subtitle_file_required', ['number' => $subtitleNumber]);
                $messages["subtitles.{$index}.subtitle_file.file"] = __('messages.subtitle_file_file', ['number' => $subtitleNumber]);
                $messages["subtitles.{$index}.subtitle_file.mimes"] = __('messages.subtitle_file_mimes', ['number' => $subtitleNumber]);
                $messages["subtitles.{$index}.subtitle_file.max"] = __('messages.subtitle_file_max', ['number' => $subtitleNumber]);
            }
        }

        if ($this->has('enable_clips') && $this->enable_clips == 1) {
            $clipTitles = $this->input('clip_title', []);
            $clipUploadTypes = $this->input('clip_upload_type', []);

            foreach ($clipTitles as $index => $title) {
                $clipNumber = $index + 1;
                $messages["clip_title.{$index}.required"] = __('messages.clip_title_required', ['number' => $clipNumber]);
                $messages["clip_title.{$index}.string"] = __('messages.clip_title_string', ['number' => $clipNumber]);
                $messages["clip_title.{$index}.max"] = __('messages.clip_title_max', ['number' => $clipNumber]);
            }

            foreach ($clipUploadTypes as $index => $uploadType) {
                $clipNumber = $index + 1;
                $messages["clip_upload_type.{$index}.required"] = __('messages.clip_upload_type_required', ['number' => $clipNumber]);
                $messages["clip_upload_type.{$index}.string"] = __('messages.clip_upload_type_string', ['number' => $clipNumber]);
                $messages["clip_poster_url.{$index}.required"] = __('messages.clip_poster_url_required', ['number' => $clipNumber]);
                $messages["clip_poster_url.{$index}.string"] = __('messages.clip_poster_url_string', ['number' => $clipNumber]);
                $messages["clip_tv_poster_url.{$index}.required"] = __('messages.clip_tv_poster_url_required', ['number' => $clipNumber]);
                $messages["clip_tv_poster_url.{$index}.string"] = __('messages.clip_tv_poster_url_string', ['number' => $clipNumber]);

                if ($uploadType == 'URL' || $uploadType == 'HLS' || $uploadType == 'x265') {
                    $messages["clip_url_input.{$index}.required"] = __('messages.clip_url_required', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.string"] = __('messages.clip_url_string', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.regex"] = __('messages.clip_url_regex', ['number' => $clipNumber]);
                } elseif ($uploadType == 'YouTube') {
                    $messages["clip_url_input.{$index}.required"] = __('messages.clip_url_required', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.string"] = __('messages.clip_url_string', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.regex"] = __('messages.clip_youtube_regex', ['number' => $clipNumber]);
                } elseif ($uploadType == 'Vimeo') {
                    $messages["clip_url_input.{$index}.required"] = __('messages.clip_url_required', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.string"] = __('messages.clip_url_string', ['number' => $clipNumber]);
                    $messages["clip_url_input.{$index}.regex"] = __('messages.clip_vimeo_regex', ['number' => $clipNumber]);
                } elseif ($uploadType == 'Local') {
                    $messages["clip_file_input.{$index}.required"] = __('messages.clip_file_required', ['number' => $clipNumber]);
                    $messages["clip_file_input.{$index}.string"] = __('messages.clip_file_string', ['number' => $clipNumber]);
                } elseif ($uploadType == 'Embedded') {
                    $messages["clip_embedded.{$index}.required"] = __('messages.clip_embedded_required', ['number' => $clipNumber]);
                    $messages["clip_embedded.{$index}.string"] = __('messages.clip_embedded_string', ['number' => $clipNumber]);
                    $messages["clip_embedded.{$index}.regex"] = __('messages.clip_embedded_regex', ['number' => $clipNumber]);
                }
            }
        }

        // Custom formatting for download quality errors
        if ($this->has('enable_download_quality') && $this->enable_download_quality == 1) {
            $downloadQualityTypes = $this->input('quality_video_download_type', []);

            foreach ($downloadQualityTypes as $index => $qualityType) {
                $qualityNumber = $index + 1;
                $messages["quality_video_download_type.{$index}.required"] = __('messages.download_quality_type_required_dynamic', ['number' => $qualityNumber]);
                $messages["quality_video_download_type.{$index}.string"] = __('messages.download_quality_type_string', ['number' => $qualityNumber]);
                $messages["video_download_quality.{$index}.required"] = __('messages.download_quality_required_dynamic', ['number' => $qualityNumber]);
                $messages["video_download_quality.{$index}.string"] = __('messages.download_quality_string', ['number' => $qualityNumber]);

                if ($qualityType == 'URL') {
                    $messages["download_quality_video_url.{$index}.required"] = __('messages.download_quality_url_required_dynamic', ['number' => $qualityNumber]);
                    $messages["download_quality_video_url.{$index}.string"] = __('messages.download_quality_url_string', ['number' => $qualityNumber]);
                    $messages["download_quality_video_url.{$index}.regex"] = __('messages.download_quality_url_regex', ['number' => $qualityNumber]);
                } elseif ($qualityType == 'Local') {
                    $messages["download_quality_video.{$index}.required"] = __('messages.download_quality_file_required_dynamic', ['number' => $qualityNumber]);
                    $messages["download_quality_video.{$index}.string"] = __('messages.download_quality_file_string', ['number' => $qualityNumber]);
                }
            }
        }

        return $messages;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // Validate subtitle file extensions
            if ($this->has('enable_subtitle') && $this->enable_subtitle == 1) {
                // Get all files from the request
                $allFiles = $this->allFiles();

                // Check for subtitle files specifically
                if (isset($allFiles['subtitles']) && is_array($allFiles['subtitles'])) {
                    foreach ($allFiles['subtitles'] as $index => $subtitleData) {
                        if (isset($subtitleData['subtitle_file']) && $subtitleData['subtitle_file']) {
                            $file = $subtitleData['subtitle_file'];

                            // Get file extension from original name
                            $originalName = $file->getClientOriginalName();
                            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                            // Only allow srt and vtt extensions
                            if (!in_array($extension, ['srt', 'vtt'])) {
                                $validator->errors()->add(
                                    "subtitles.{$index}.subtitle_file",
                                    __('messages.subtitle_file_format', ['number' => $index + 1])
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        // If request expects JSON or is AJAX, return JSON response
        if ($this->expectsJson() || $this->ajax() || $this->wantsJson()) {
            throw new ValidationException($validator);
        }

        // Otherwise, use default behavior (redirect back with errors)
        parent::failedValidation($validator);
    }
}
