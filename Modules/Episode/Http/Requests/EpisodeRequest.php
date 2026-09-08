<?php

namespace Modules\Episode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class EpisodeRequest extends FormRequest
{
   public function rules()
    {
        $id = request()->id;
        $seasonId = $this->input('season_id');
        $rules = [
            'name' => ['required', Rule::unique('episodes', 'name')->ignore($id)],
            'entertainment_id'=> ['required'],
            'content_rating'=>'required|string',
            'description' => 'required|string',
            'access' => 'required',
            'IMDb_rating' => 'nullable|numeric|min:1|max:10',
            'season_id'=> ['required'],
            'episode_number' => [
                'nullable',
                'numeric',
                'min:1',
                Rule::unique('episodes', 'episode_number')
                    ->where('season_id', $seasonId)
                    ->ignore($id)
            ],
            'duration'=> ['required'],
            'video_upload_type' => ['required'],
            'trailer_url_type' => ['required'],
        ];
        $movieAccess = $this->input('access');

        $trailerUrlType = $this->input('trailer_url_type');

        if ($trailerUrlType == 'Embedded') {
            if ($id === null) {
                $rules['trailer_embedded'] = [
                    'required','regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'“”‘’](.*?)["\'“”‘’][^>]*>[\s\S]*?<\/iframe>/i'
                ];
            } else {
                $rules['trailer_url_embedded'] = [
                    'required','regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'“”‘’](.*?)["\'“”‘’][^>]*>[\s\S]*?<\/iframe>/i'
                ];
            }
        } else if ($trailerUrlType == 'Local') {
            $rules['trailer_video'] = ['required'];
        } else if ($trailerUrlType == 'URL' || $trailerUrlType == 'HLS' || $trailerUrlType == 'x265') {
            $rules['trailer_url'] = ['required','regex:/^https?:\/\/.+$/'];
        } else if ($trailerUrlType == 'YouTube') {
            $rules['trailer_url'] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($trailerUrlType == 'Vimeo') {
            $rules['trailer_url'] = ['required','regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
        }

        $videoUrlType = $this->input('video_upload_type');

        if ($videoUrlType == 'Embedded') {
            if ($id === null) {
                $rules['embedded'] = [
                    'required','regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'“”‘’](.*?)["\'“”‘’][^>]*>[\s\S]*?<\/iframe>/i'
                ];
            } else {
                $rules['video_url_embedded'] = [
                    'required','regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'“”‘’](.*?)["\'“”‘’][^>]*>[\s\S]*?<\/iframe>/i'
                ];
            }
        } else if ($videoUrlType == 'Local') {
            $rules['video_file_input'] = ['required'];
        } else if ($videoUrlType == 'URL' || $videoUrlType == 'HLS' || $videoUrlType == 'x265') {
            $rules['video_url_input'] = ['required','regex:/^https?:\/\/.+$/'];
        } else if ($videoUrlType == 'YouTube') {
            $rules['video_url_input'] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($videoUrlType == 'Vimeo') {
            $rules['video_url_input'] = ['required','regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
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

        $download_status = $this->input('download_status');
        $download_type = $this->input('video_upload_type_download');

        if ($download_status == 1) {
            $rules['video_upload_type_download'] = ['required'];
        }
        $video_download_type = $this->input('video_upload_type_download');

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

        // Quality video validation
        if ($this->has('enable_quality') && $this->enable_quality == 1) {
            $qualityTypes = $this->input('video_quality_type', []);
            $qualityVideos = $this->input('quality_video_url_input', []);

            foreach ($qualityTypes as $index => $qualityType) {
                if ($qualityType == 'Embedded') {
                    $rules["quality_video_embed_input.{$index}"] = ['required','regex:/<iframe.*?src\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
                } elseif ($qualityType == 'Local') {
                    $rules["quality_video.{$index}"] = ['required'];
                } elseif ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^https?:\/\/.+$/'];
                } elseif ($qualityType == 'YouTube') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
                } elseif ($qualityType == 'Vimeo') {
                    $rules["quality_video_url_input.{$index}"] = ['required','regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
                }
            }
        }

        if ($movieAccess == 'paid') {
            $rules['plan_id'] = 'required';
            $rules['release_date'] = 'required';
        } elseif ($movieAccess == 'pay-per-view') {
            $rules['price'] = 'required|numeric';
            $rules['available_for'] = 'required|integer|min:1';
        } else {
            // For 'free' access
            $rules['release_date'] = 'required';
        }

        if ($this->has('enable_seo') && $this->enable_seo == 1) {
            $episodeId = $this->route('episode');
            // Handle both array and string cases for route parameter
            if (is_array($episodeId)) {
                $episodeId = $episodeId['id'] ?? null;
            }

        $rules = array_merge($rules, [
            'meta_title' => 'required|string|max:100|unique:episodes,meta_title,' . ($episodeId ?: 'NULL') . ',id',
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
        return [
            'name.required' => __('messages.name_required'),
            'name.unique' => __('messages.name_unique'),

            'entertainment_id.required' => __('messages.entertainment_id_required'),
            'season_id.required' => __('messages.season_id_required'),
            'episode_number.numeric' => __('messages.episode_number_must_be_numeric'),
            'episode_number.min' => __('messages.episode_number_min'),
            'episode_number.unique' => __('messages.episode_number_unique'),
            'duration.required' => __('messages.duration_required'),

            'IMDb_rating.required' => __('messages.imdb_required'),
            'IMDb_rating.numeric' => __('messages.imdb_numeric'),
            'IMDb_rating.min' => __('messages.imdb_min'),
            'IMDb_rating.max' => __('messages.imdb_max'),

            'release_date.required' => __('messages.release_date_required'),

            'discount.required' => __('messages.discount_required'),
            'discount.min' => __('messages.discount_min'),
            'discount.max' => __('messages.discount_max'),

            'access_duration.integer' => __('messages.access_duration_integer'),
            'access_duration.min' => __('messages.access_duration_min'),

            'available_for.integer' => __('messages.available_for_integer'),
            'available_for.min' => __('messages.available_for_min'),

            'price.required' => __('messages.price_required'),
            'price.numeric' => __('messages.price_numeric'),

            'meta_description.required' => __('messages.meta_description_required'),

            'trailer_url_type.required' => __('messages.trailer_url_type_required'),
            'trailer_embedded.required' => __('messages.trailer_embedded_required'),
            'trailer_embedded.regex' => __('messages.trailer_embedded_regex'),

            'trailer_url_embedded.required' => __('messages.trailer_embedded_required'),
            'trailer_url_embedded.regex' => __('messages.trailer_embedded_regex'),

            'trailer_video.required' => __('messages.trailer_video_required'),
            'trailer_url.required' => __('messages.trailer_url_required'),
            'trailer_url.regex' => __('messages.trailer_url_regex'),

            'embedded.required' => __('messages.embedded_required'),
            'embedded.regex' => __('messages.embedded_regex'),

            'video_url_embedded.required' => __('messages.embedded_required'),
            'video_url_embedded.regex' => __('messages.embedded_regex'),

            'video_file_input.required' => __('messages.video_file_input_required'),
            'video_url_input.required' => __('messages.video_url_input_required'),
            'video_url_input.regex' => __('messages.video_url_input_regex'),

            'video_upload_type_download.required' => __('messages.video_upload_type_download_required'),
            'video_url_input_download.required' => __('messages.video_url_input_download_required'),
            'video_url_input_download.regex' => __('messages.video_url_input_download_regex'),
            'video_file_input_download.required' => __('messages.video_file_input_download_required'),
        ];

        // Quality video error messages - set to empty to prevent Laravel from auto-adding field names
        // We'll handle all messages in withValidator instead
        if ($this->has('enable_quality') && $this->enable_quality == 1) {
            $qualityTypes = $this->input('video_quality_type', []);

            foreach ($qualityTypes as $index => $qualityType) {
                if ($qualityType == 'Embedded') {
                    $messages["quality_video_embed_input.{$index}.required"] = "";
                    $messages["quality_video_embed_input.{$index}.regex"] = "";
                } elseif ($qualityType == 'Local') {
                    $messages["quality_video.{$index}.required"] = "";
                } elseif (in_array($qualityType, ['URL', 'YouTube', 'Vimeo', 'HLS', 'x265'])) {
                    $messages["quality_video_url_input.{$index}.required"] = "";
                    $messages["quality_video_url_input.{$index}.regex"] = "";
                }
            }
        }

        // Custom formatting for subtitle errors
        if ($this->has('enable_subtitle') && $this->enable_subtitle == 1) {
            $subtitles = $this->input('subtitles', []);
            foreach ($subtitles as $index => $subtitle) {
                $messages["subtitles.{$index}.language.required"] = __('messages.subtitle_language_required', ['number' => $index + 1]);
                $messages["subtitles.{$index}.language.string"]   = __('messages.subtitle_language_string', ['number' => $index + 1]);

                $messages["subtitles.{$index}.subtitle_file.required"] = __('messages.subtitle_file_required', ['number' => $index + 1]);
                $messages["subtitles.{$index}.subtitle_file.file"]     = __('messages.subtitle_file_file', ['number' => $index + 1]);
                $messages["subtitles.{$index}.subtitle_file.mimes"]    = __('messages.subtitle_file_mimes', ['number' => $index + 1]);
                $messages["subtitles.{$index}.subtitle_file.max"]      = __('messages.subtitle_file_max', ['number' => $index + 1]);
            }
        }

       if ($this->has('enable_download_quality') && $this->enable_download_quality == 1) {

            $downloadQualityTypes = $this->input('quality_video_download_type', []);

            foreach ($downloadQualityTypes as $index => $qualityType) {

                $messages["quality_video_download_type.{$index}.required"] = __('messages.download_quality_type_required', ['number' => $index + 1]);
                $messages["quality_video_download_type.{$index}.string"]   = __('messages.download_quality_type_string', ['number' => $index + 1]);

                $messages["video_download_quality.{$index}.required"] = __('messages.download_quality_required', ['number' => $index + 1]);
                $messages["video_download_quality.{$index}.string"]   = __('messages.download_quality_string', ['number' => $index + 1]);


                if ($qualityType == 'URL') {

                    $messages["download_quality_video_url.{$index}.required"] = __('messages.download_quality_url_required', ['number' => $index + 1]);
                    $messages["download_quality_video_url.{$index}.string"]   = __('messages.download_quality_url_string', ['number' => $index + 1]);
                    $messages["download_quality_video_url.{$index}.regex"]    = __('messages.download_quality_url_regex', ['number' => $index + 1]);

                } elseif ($qualityType == 'Local') {

                    $messages["download_quality_video.{$index}.required"] = __('messages.download_quality_file_required', ['number' => $index + 1]);
                    $messages["download_quality_video.{$index}.string"]   = __('messages.download_quality_file_string', ['number' => $index + 1]);
                }
            }
        }

        return $messages;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        $attributes = [];

        // Set empty attribute names for quality video fields to prevent Laravel from auto-adding field names
        if ($this->has('enable_quality') && $this->enable_quality == 1) {
            $qualityTypes = $this->input('video_quality_type', []);

            foreach ($qualityTypes as $index => $qualityType) {
                if ($qualityType == 'Local') {
                    $attributes["quality_video.{$index}"] = '';
                } elseif ($qualityType == 'Embedded') {
                    $attributes["quality_video_embed_input.{$index}"] = '';
                } elseif (in_array($qualityType, ['URL', 'YouTube', 'Vimeo', 'HLS', 'x265'])) {
                    $attributes["quality_video_url_input.{$index}"] = '';
                }
            }
        }

        return $attributes;
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
            // If episode is paid, its plan level must be >= TV show plan level
            if ($this->input('access') === 'paid') {
                $planId = $this->input('plan_id');
                $entertainmentId = $this->input('entertainment_id');

                if ($planId && $entertainmentId) {
                    $episodePlan = Plan::find($planId);
                    $tvshow = Entertainment::with('plan')->find($entertainmentId);

                    if ($episodePlan && $tvshow && $tvshow->plan) {
                        $episodeLevel = (int) $episodePlan->level;
                        $tvshowLevel = (int) $tvshow->plan->level;

                        if ($episodeLevel < $tvshowLevel) {
                            $validator->errors()->add(
                                'plan_id',
                                __('messages.episode_plan_must_be_same_or_higher_than_tvshow_plan')
                            );
                        }
                    }
                }
            }

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
                                    "Subtitle " . ($index + 1) . ": Subtitle file must be in .srt or .vtt format."
                                );
                            }
                        }
                    }
                }
            }

            // Custom error messages for video URL based on upload type
            $videoUrlType = $this->input('video_upload_type');

            // Check if video_url_input has validation errors (specifically regex errors)
            if ($validator->errors()->has('video_url_input')) {
                $errors = $validator->errors()->get('video_url_input');
                $hasRegexError = false;

                // Check if any error is related to regex/format validation
                foreach ($errors as $error) {
                    $errorMessage = is_array($error) ? ($error[0] ?? '') : (string)$error;
                    if (stripos($errorMessage, 'regex') !== false ||
                        stripos($errorMessage, 'valid URL') !== false ||
                        stripos($errorMessage, 'format') !== false) {
                        $hasRegexError = true;
                        break;
                    }
                }

                if ($hasRegexError) {
                    // Remove all existing errors for this field
                    $validator->errors()->forget('video_url_input');

                    // Add specific error message based on video upload type
                    if ($videoUrlType == 'URL' || $videoUrlType == 'HLS' || $videoUrlType == 'x265') {
                        $validator->errors()->add(
                            'video_url_input',
                            __('messages.valid_url_starting_with_http')
                        );
                    } elseif ($videoUrlType == 'YouTube') {
                        $validator->errors()->add(
                            'video_url_input',
                            'Please enter a valid YouTube URL (e.g., https://www.youtube.com/watch?v=... or https://youtu.be/...).'
                        );
                    } elseif ($videoUrlType == 'Vimeo') {
                        $validator->errors()->add(
                            'video_url_input',
                            'Please enter a valid Vimeo URL (e.g., https://vimeo.com/123456789).'
                        );
                    } else {
                        $validator->errors()->add(
                            'video_url_input',
                            'Please enter a valid URL format for the selected video type.'
                        );
                    }
                }
            }

            // Validate trailer file input when upload type is Local
            $trailerUrlType = $this->input('trailer_url_type');
            $trailerVideoInput = $this->input('trailer_video');

            if ($trailerUrlType == 'Local') {
                // Remove any existing validation errors for this field first
                $validator->errors()->forget('trailer_video');

                // Check if there's a new file upload (direct file upload)
                $hasFileUpload = false;
                try {
                    $hasFileUpload = $this->hasFile('trailer_video') && $this->file('trailer_video')->isValid();
                } catch (\Exception $e) {
                    // File upload check failed - not a direct file upload
                }

                // Check if trailer_video has a value (from file manager)
                $hasValue = !empty($trailerVideoInput) && trim($trailerVideoInput) !== '';

                // Check if the value is a URL (invalid for Local type)
                $isUrl = false;
                $isValidLocalFile = false;

                if ($hasValue) {
                    $trimmedValue = trim($trailerVideoInput);

                    // Check if it's an external URL (YouTube, Vimeo, or iframe embed)
                    // These are the only URLs we want to reject for Local upload type
                    $isExternalUrl = preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com|dailymotion\.com|facebook\.com)/i', $trimmedValue) ||
                                     preg_match('/<iframe/i', $trimmedValue);

                    // If it's not an external URL, consider it a valid local file
                    // This covers:
                    // - Local file URLs: http://domain.com/storage/tvshow/episode/trailer/filename.mp4
                    // - Relative paths: /storage/tvshow/episode/trailer/filename.mp4
                    // - File paths: storage/tvshow/episode/trailer/filename.mp4
                    // - Filenames: filename.mp4
                    // - Any other non-external URL format
                    if (!$isExternalUrl) {
                        $isValidLocalFile = true;
                    }
                }

                // For Local type, we need either:
                // 1. A new file upload, OR
                // 2. An existing valid local file path (not a URL)

                if (!$hasFileUpload && !$isValidLocalFile) {
                    // No valid file - show error
                    $isUpdate = $this->has('id') && !empty($this->input('id'));
                    if (!$hasValue) {
                        $errorMessage = $isUpdate
                            ? 'Please upload a trailer video file. No existing file is available for this episode.'
                            : 'Trailer video file is required when upload type is Local.';
                    } else {
                        // Has value but it's a URL
                        $errorMessage = 'Please upload a trailer video file. The current value is a URL, which is not valid for Local upload type.';
                    }

                    $validator->errors()->add('trailer_video', $errorMessage);
                }
            }

            // Validate video file input when upload type is Local
            $videoFileInput = $this->input('video_file_input');

            if ($videoUrlType == 'Local') {
                // Remove any existing validation errors for this field first
                $validator->errors()->forget('video_file_input');

                // Check if there's a new file upload (direct file upload)
                $hasFileUpload = false;
                try {
                    $hasFileUpload = $this->hasFile('video_file_input') && $this->file('video_file_input')->isValid();
                } catch (\Exception $e) {
                    // File upload check failed - not a direct file upload
                }

                // Check if video_file_input has a value (from file manager)
                $hasValue = !empty($videoFileInput) && trim($videoFileInput) !== '';

                // Check if the value is a URL (invalid for Local type)
                $isUrl = false;
                $isValidLocalFile = false;

                if ($hasValue) {
                    $trimmedValue = trim($videoFileInput);

                    // Check if it's an external URL (YouTube, Vimeo, or iframe embed)
                    // These are the only URLs we want to reject for Local upload type
                    $isExternalUrl = preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com|dailymotion\.com|facebook\.com)/i', $trimmedValue) ||
                                     preg_match('/<iframe/i', $trimmedValue);

                    // If it's not an external URL, consider it a valid local file
                    // This covers:
                    // - Local file URLs: http://domain.com/storage/tvshow/episode/video/filename.mp4
                    // - Relative paths: /storage/tvshow/episode/video/filename.mp4
                    // - File paths: storage/tvshow/episode/video/filename.mp4
                    // - Filenames: filename.mp4
                    // - Any other non-external URL format
                    if (!$isExternalUrl) {
                        $isValidLocalFile = true;
                    }
                }

                // For Local type, we need either:
                // 1. A new file upload, OR
                // 2. An existing valid local file path (not a URL)

                if (!$hasFileUpload && !$isValidLocalFile) {
                    // No valid file - show error
                    if (!$hasValue) {
                        $errorMessage = $isUpdate
                            ? 'Please upload a video file. No existing file is available for this episode.'
                            : 'Video file is required when upload type is Local.';
                    } else {
                        // Has value but it's a URL
                        $errorMessage = 'Please upload a video file. The current value is a URL, which is not valid for Local upload type.';
                    }

                    $validator->errors()->add('video_file_input', $errorMessage);
                }
            }

            // Custom error messages for download URL based on download type
            $downloadType = $this->input('video_upload_type_download');
            $downloadStatus = $this->input('download_status');

            // Validate main download URL
            if ($downloadStatus == 1 && $downloadType == 'URL') {
                if ($validator->errors()->has('video_url_input_download')) {
                    $errors = $validator->errors()->get('video_url_input_download');
                    $hasRegexError = false;

                    // Check if any error is related to regex/format validation
                    foreach ($errors as $error) {
                        $errorMessage = is_array($error) ? ($error[0] ?? '') : (string)$error;
                        if (stripos($errorMessage, 'regex') !== false ||
                            stripos($errorMessage, 'valid URL') !== false ||
                            stripos($errorMessage, 'format') !== false) {
                            $hasRegexError = true;
                            break;
                        }
                    }

                    if ($hasRegexError) {
                        // Remove all existing errors for this field
                        $validator->errors()->forget('video_url_input_download');

                        // Add specific error message
                        $validator->errors()->add(
                            'video_url_input_download',
                            'Please enter a valid download URL starting with http:// or https://.'
                        );
                    }
                }
            }

            // Validate download quality URLs
            if ($downloadStatus == 1 && $this->has('enable_download_quality') && $this->enable_download_quality == 1) {
                $downloadQualityTypes = $this->input('quality_video_download_type', []);

                foreach ($downloadQualityTypes as $index => $qualityType) {
                    if ($qualityType == 'URL') {
                        $fieldName = "download_quality_video_url.{$index}";

                        if ($validator->errors()->has($fieldName)) {
                            $errors = $validator->errors()->get($fieldName);
                            $hasRegexError = false;

                            // Check if any error is related to regex/format validation
                            foreach ($errors as $error) {
                                $errorMessage = is_array($error) ? ($error[0] ?? '') : (string)$error;
                                if (stripos($errorMessage, 'regex') !== false ||
                                    stripos($errorMessage, 'valid URL') !== false ||
                                    stripos($errorMessage, 'format') !== false) {
                                    $hasRegexError = true;
                                    break;
                                }
                            }

                            if ($hasRegexError) {
                                $qualityNumber = $index + 1;
                                // Remove all existing errors for this field
                                $validator->errors()->forget($fieldName);

                                // Add specific error message
                                $validator->errors()->add(
                                    $fieldName,
                                    __('messages.download_quality_url_regex', ['number' => $qualityNumber])
                                );
                            }
                        }
                    }
                }
            }

            // Validate quality video URLs (Quality Info section)
            if ($this->has('enable_quality') && $this->enable_quality == 1) {
                $qualityTypes = $this->input('video_quality_type', []);

                foreach ($qualityTypes as $index => $qualityType) {
                    // Handle Local type - video file required
                    if ($qualityType == 'Local') {
                        $fieldName = "quality_video.{$index}";
                        // Get all error keys first
                        $allKeys = $validator->errors()->keys();
                        $hadError = false;

                        // Remove all errors for this field (including any variations)
                        foreach ($allKeys as $key) {
                            if (strpos($key, "quality_video.{$index}") === 0 ||
                                preg_match("/quality_video\.{$index}/", $key) ||
                                $key === $fieldName) {
                                $hadError = true;
                                $validator->errors()->forget($key);
                            }
                        }

                        // Also check all error messages for "Quality Video" pattern and remove them
                        foreach ($validator->errors()->all() as $errorKey => $errorMessages) {
                            foreach ($errorMessages as $errorMsg) {
                                if (is_string($errorMsg) &&
                                    (stripos($errorMsg, "Quality Video.{$index}") !== false ||
                                     stripos($errorMsg, "quality_video.{$index}") !== false)) {
                                    $validator->errors()->forget($errorKey);
                                    $hadError = true;
                                    break 2;
                                }
                            }
                        }

                        // Add clean error message if there was an error
                        if ($hadError) {
                            $validator->errors()->add(
                                $fieldName,
                                "Video Quality: Video Quality file is required."
                            );
                        }
                    }
                    // Handle Embedded type
                    elseif ($qualityType == 'Embedded') {
                        $fieldName = "quality_video_embed_input.{$index}";
                        // Remove all errors for this field
                        $allKeys = $validator->errors()->keys();
                        foreach ($allKeys as $key) {
                            if (strpos($key, "quality_video_embed_input.{$index}") === 0 ||
                                preg_match("/quality_video_embed_input\.{$index}/", $key)) {
                                $validator->errors()->forget($key);
                            }
                        }
                        if (in_array($fieldName, $allKeys) || $validator->errors()->has($fieldName)) {
                            $errors = $validator->errors()->get($fieldName);
                            $errorText = stripos(implode(' ', $errors), 'required') !== false
                                ? "Video Quality: Embedded video is required."
                                : "Video Quality: The video must contain a valid iframe with a src attribute.";
                            $validator->errors()->add($fieldName, $errorText);
                        }
                    }
                    // Handle URL types
                    elseif (in_array($qualityType, ['URL', 'YouTube', 'Vimeo', 'HLS', 'x265'])) {
                        $fieldName = "quality_video_url_input.{$index}";

                        // Remove all errors for this field
                        $allKeys = $validator->errors()->keys();
                        $hadError = false;
                        $errors = [];
                        foreach ($allKeys as $key) {
                            if (strpos($key, "quality_video_url_input.{$index}") === 0 ||
                                preg_match("/quality_video_url_input\.{$index}/", $key)) {
                                if (!$hadError) {
                                    $errors = $validator->errors()->get($key);
                                    $hadError = true;
                                }
                                $validator->errors()->forget($key);
                            }
                        }

                        if ($hadError || $validator->errors()->has($fieldName)) {
                            $hasRegexError = false;
                            $hasRequiredError = false;

                            // Check if any error is related to regex/format validation
                            foreach ($errors as $error) {
                                $errorMessage = is_array($error) ? ($error[0] ?? '') : (string)$error;
                                if (stripos($errorMessage, 'regex') !== false ||
                                    stripos($errorMessage, 'valid URL') !== false ||
                                    stripos($errorMessage, 'format') !== false) {
                                    $hasRegexError = true;
                                    break;
                                }
                                if (stripos($errorMessage, 'required') !== false) {
                                    $hasRequiredError = true;
                                    break;
                                }
                            }

                            // Add specific error message based on quality type
                            if ($hasRequiredError) {
                                $validator->errors()->add(
                                    $fieldName,
                                    "Video Quality: Video URL is required."
                                );
                            } elseif ($hasRegexError) {
                                if ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                                    $validator->errors()->add(
                                        $fieldName,
                                        __('messages.quality_video_url_regex', ['number' => $index + 1])
                                    );
                                } elseif ($qualityType == 'YouTube') {
                                    $validator->errors()->add(
                                        $fieldName,
                                        "Video Quality: Please enter a valid YouTube URL (e.g., https://www.youtube.com/watch?v=... or https://youtu.be/...)."
                                    );
                                } elseif ($qualityType == 'Vimeo') {
                                    $validator->errors()->add(
                                        $fieldName,
                                        "Video Quality: Please enter a valid Vimeo URL (e.g., https://vimeo.com/123456789)."
                                    );
                                }
                            } else {
                                // Fallback for any other error
                                $validator->errors()->add(
                                    $fieldName,
                                    "Video Quality: Video URL is required."
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
