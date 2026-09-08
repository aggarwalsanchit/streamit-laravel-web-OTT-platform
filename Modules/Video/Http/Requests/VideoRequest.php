<?php

namespace Modules\Video\Http\Requests;

use App\Rules\ValidUploadProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class VideoRequest extends FormRequest
{
    public function rules()
    {
        $id = request()->id;
        $videoId = $this->route('video');
        // Handle both array and string cases for route parameter
        if (is_array($videoId)) {
            $videoId = $videoId['id'] ?? null;
        }
        $videoId = $videoId ?: $id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('videos', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($videoId)
            ],
            'duration' => ['required'],
            'release_date' => ['required'],
            'access' => 'required',
            'description' => 'required|string',
            'video_upload_type' => 'required',
        ];


        $trailerUrlType = $this->input('trailer_url_type');

        if ($trailerUrlType == 'Embedded') {
            $rules['trailer_embedded'] = ['required', 'regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'“”‘’](.*?)["\'“”‘’][^>]*>[\s\S]*?<\/iframe>/i'];
        } else if ($trailerUrlType == 'Local') {
            $rules['trailer_video'] = ['required'];
        } else if ($trailerUrlType == 'URL' || $trailerUrlType == 'HLS' || $trailerUrlType == 'x265') {
            $rules['trailer_url'] = ['required', 'regex:/^https?:\/\/.+$/'];
        } else if ($trailerUrlType == 'YouTube') {
            $rules['trailer_url'] = ['required', 'regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($trailerUrlType == 'Vimeo') {
            $rules['trailer_url'] = ['required', 'regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
        }

        $videoUrlType = $this->input('video_upload_type');

        if ($videoUrlType == 'Embedded') {
            // Check if this is an update (has route parameter or id in request) or create
            $videoId = $this->route('video');
            // Handle both array and string cases for route parameter
            if (is_array($videoId)) {
                $videoId = $videoId['id'] ?? null;
            }
            // Also check if id is in request (form submission)
            $requestId = $this->input('id');
            $isUpdate = !empty($videoId) || !empty($requestId);
            if ($isUpdate) {
                $rules['video_embedded'] = ['required', 'regex:/<iframe.*?src\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
            } else {
                $rules['embed_code'] = ['required', 'regex:/<iframe.*?src\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
            }
        } else if ($videoUrlType == 'Local') {
            $rules['video_file_input'] = ['required'];
        } else if ($videoUrlType == 'URL' || $videoUrlType == 'HLS' || $videoUrlType == 'x265') {
            $rules['video_url_input'] = ['required', 'regex:/^https?:\/\/.+$/'];
        } else if ($videoUrlType == 'YouTube') {
            $rules['video_url_input'] = ['required', 'regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
        } else if ($videoUrlType == 'Vimeo') {
            $rules['video_url_input'] = ['required', 'regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
        }
        $movieAccess = $this->input('access');

        if ($movieAccess === 'paid') {
            $rules['plan_id'] = 'required';
        } elseif ($movieAccess === 'pay-per-view') {
            $rules['price'] = 'required|numeric';
            $rules['available_for'] = 'required|integer|min:1';
        }

        if ($this->has('enable_subtitle') && $this->enable_subtitle == 1) {
            $rules['subtitles.*.language'] = 'required|string';
            // Check if this is an update request (has ID parameter)
            $isUpdate = $this->has('id') && !empty($this->input('id'));

            if ($isUpdate) {
                // Update form - files are optional (existing files can be kept)
                $rules['subtitles.*.subtitle_file'] = ['nullable', new ValidUploadProfile('subtitle')];
            } else {
                // Create form - require files
                $rules['subtitles.*.subtitle_file'] = ['required', new ValidUploadProfile('subtitle')];
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
                    $rules["clip_url_input.{$index}"] = ['required', 'string', 'regex:/^https?:\/\/.+$/'];
                } elseif ($uploadType == 'YouTube') {
                    $rules["clip_url_input.{$index}"] = ['required', 'string', 'regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
                } elseif ($uploadType == 'Vimeo') {
                    $rules["clip_url_input.{$index}"] = ['required', 'string', 'regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
                } elseif ($uploadType == 'Local') {
                    $rules["clip_file_input.{$index}"] = ['required', 'string'];
                } elseif ($uploadType == 'Embedded') {
                    $rules["clip_embedded.{$index}"] = ['required', 'string', 'regex:/<iframe.*?src\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
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
            $rules['video_url_input_download'] = ['required', 'regex:/^https?:\/\/.+$/'];
        }
        if ($download_status == 1) {
            if ($this->has('enable_download_quality') && $this->enable_download_quality == 1) {
                $rules['quality_video_download_type.*'] = 'required|string';
                $rules['video_download_quality.*'] = 'required|string';

                // Validate download quality content based on upload type
                $downloadQualityTypes = $this->input('quality_video_download_type', []);
                foreach ($downloadQualityTypes as $index => $qualityType) {
                    if ($qualityType == 'URL') {
                        $rules["download_quality_video_url.{$index}"] = ['required', 'regex:/^https?:\/\/.+$/'];
                    } elseif ($qualityType == 'Local') {
                        $rules["download_quality_video.{$index}"] = ['required'];
                    }
                }
            }
        }

        // Validate quality list if enable_quality is enabled
        if ($this->has('enable_quality') && $this->enable_quality == 1) {
            $qualityTypes = $this->input('video_quality_type', []);
            $videoQualities = $this->input('video_quality', []);

            // Only validate if we have quality types
            if (!empty($qualityTypes)) {
                $rules['video_quality_type.*'] = 'required|string';
                $rules['video_quality.*'] = 'required|string';

                $isUpdate = $this->has('id') && !empty($this->input('id'));
                $hasLocalType = false;

                foreach ($qualityTypes as $index => $qualityType) {
                    if ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                        $rules["quality_video_url_input.{$index}"] = ['required', 'regex:/^https?:\/\/.+$/'];
                    } elseif ($qualityType == 'Local') {
                        $hasLocalType = true;
                        // For Local type, we'll validate video_quality_url once after the loop
                    } elseif ($qualityType == 'Embedded') {
                        $rules["quality_video_embed.{$index}"] = ['required', 'regex:/<iframe.*?src\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
                    } elseif ($qualityType == 'YouTube') {
                        $rules["quality_video_url_input.{$index}"] = ['required', 'regex:/^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/'];
                    } elseif ($qualityType == 'Vimeo') {
                        $rules["quality_video_url_input.{$index}"] = ['required', 'regex:/^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^\/]+\/videos\/)?\d+)(\/.*)?$/'];
                    }
                }

                // Validate video_quality_url for each Local type
                if ($hasLocalType) {
                    $videoQualityUrlArray = $this->input("video_quality_url", []);
                    foreach ($qualityTypes as $index => $qualityType) {
                        if ($qualityType == 'Local') {
                            // Check if video_quality_url array has a value for this index
                            $hiddenFileUrl = $videoQualityUrlArray[$index] ?? null;
                            // Check if video_quality_url is empty or just contains whitespace
                            if (empty($hiddenFileUrl) || trim($hiddenFileUrl) === '') {
                                if ($isUpdate) {
                                    // For updates, check if there's an existing file
                                    $fileInput = $this->input("quality_video_file_input");
                                    if (empty($fileInput)) {
                                        $rules["video_quality_url.{$index}"] = ['required'];
                                    }
                                } else {
                                    // For create, require file
                                    $rules["video_quality_url.{$index}"] = ['required'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($this->has('enable_seo') && $this->enable_seo == 1) {
            $videoId = $this->route('video');
            // Handle both array and string cases for route parameter
            if (is_array($videoId)) {
                $videoId = $videoId['id'] ?? null;
            }

            $rules = array_merge($rules, [
                'meta_title' => 'required|string|max:100|unique:videos,meta_title,' . ($videoId ?: 'NULL') . ',id',
                'google_site_verification' => 'required',
                'meta_keywords' => 'required|max:255',
                'canonical_url' => 'required',
                // 'short_description' => 'required|string|max:200',
                'seo_image' => 'required',
            ]);
        }

        return $rules;
    }


    public function messages()
    {
        $messages = [
            'name.required' => __('messages.title_field_required'),
            'name.unique' => __('messages.video_name_already_exists'),
            'duration.required' => __('messages.duration_required'),
            'release_date.required' => __('messages.release_date_required'),

            'discount.required' => 'Discount is required.',
            'discount.min' => 'Discount must be at least 1%.',
            'discount.max' => 'Discount cannot exceed 99%.',
            'access_duration.integer' => __('messages.access_duration_integer'),
            'access_duration.min' => __('messages.access_duration_min'),
            'access_duration.required' => __('messages.access_duration_field_required'),
            'available_for.integer' => __('messages.available_for_integer'),
            'available_for.min' => __('messages.available_for_min'),
            'available_for.required' => __('messages.available_for_field_required'),
            'price.required' => __('messages.price_field_required'),
            'price.numeric' => __('messages.price_numeric'),
            'trailer_url_type.required' => 'Trailer URL Type is required.',
            'trailer_embedded.required' => 'Trailer Embedded is required.',
            'trailer_embedded.regex' => 'The trailer must contain a valid iframe with a src attribute.',
            'trailer_video.required' => 'Trailer Video is required.',
            'trailer_url.required' => 'Trailer URL is required.',
            'trailer_url.regex' => 'The trailer URL format is invalid for the selected type.',
            'embed_code.required' => 'Video Embedded is required.',
            'embed_code.regex' => 'The video must contain a valid iframe with a src attribute.',
            'video_embedded.required' => 'Video Embedded is required.',
            'video_embedded.regex' => 'The video must contain a valid iframe with a src attribute.',
            'video_file_input.required' => 'Video File is required.',
            'video_url_input.required' => __('messages.video_url_input_required'),
            'video_url_input.regex' => __('messages.video_url_input_regex'),
            'video_upload_type.required' => __('messages.video_type_required'),
            'video_upload_type_download.required' => __('messages.video_upload_type_download_required'),
            'video_url_input_download.required' => 'Please upload the local video file for download.',
            'video_file_input_download.required' => 'Please enter a video download URL.',
            'video_file_input_download.regex' => 'Please enter a valid URL (must start with http:// or https://).',
            'enable_download_quality.required' => 'Enable Download Quality is required.',
            'quality_video_download_type.required' => 'Download Quality Type is required.',
            'video_download_quality.required' => 'Download Quality is required.',
            'download_quality_video_url.required' => 'Download Quality URL is required.',
            'download_quality_video.required' => 'Download Quality File is required.',
        ];

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
                $messages["clip_poster_url.{$index}.required"] = __('messages.clip_poster_required', ['number' => $clipNumber]);
                $messages["clip_poster_url.{$index}.string"] = __('messages.clip_poster_string', ['number' => $clipNumber]);
                $messages["clip_tv_poster_url.{$index}.required"] = __('messages.clip_tv_poster_required', ['number' => $clipNumber]);
                $messages["clip_tv_poster_url.{$index}.string"] = __('messages.clip_tv_poster_string', ['number' => $clipNumber]);

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
                $messages["quality_video_download_type.{$index}.required"] = __('messages.download_quality_type_required', ['number' => $qualityNumber]);
                $messages["quality_video_download_type.{$index}.string"] = __('messages.download_quality_type_string', ['number' => $qualityNumber]);
                $messages["video_download_quality.{$index}.required"] = __('messages.download_quality_required', ['number' => $qualityNumber]);
                $messages["video_download_quality.{$index}.string"] = __('messages.download_quality_string', ['number' => $qualityNumber]);

                if ($qualityType == 'URL') {
                    $messages["download_quality_video_url.{$index}.required"] = __('messages.download_quality_url_required', ['number' => $qualityNumber]);
                    $messages["download_quality_video_url.{$index}.string"] = __('messages.download_quality_url_string', ['number' => $qualityNumber]);
                    $messages["download_quality_video_url.{$index}.regex"] = __('messages.download_quality_url_regex', ['number' => $qualityNumber]);
                } elseif ($qualityType == 'Local') {
                    $messages["download_quality_video.{$index}.required"] = __('messages.download_quality_file_required', ['number' => $qualityNumber]);
                    $messages["download_quality_video.{$index}.string"] = __('messages.download_quality_file_string', ['number' => $qualityNumber]);
                }
            }
        }

        // Custom formatting for quality errors
        if ($this->has('enable_quality') && $this->enable_quality == 1) {
            $qualityTypes = $this->input('video_quality_type', []);

            foreach ($qualityTypes as $index => $qualityType) {
                $number = $index + 1;
                $messages["video_quality_type.{$index}.required"] = __('messages.quality_video_type_required', ['number' => $number]);
                $messages["video_quality_type.{$index}.string"] = __('messages.quality_video_type_string', ['number' => $number]);
                $messages["video_quality.{$index}.required"] = __('messages.quality_video_required', ['number' => $number]);
                $messages["video_quality.{$index}.string"] = __('messages.quality_video_string', ['number' => $number]);

                if ($qualityType == 'URL' || $qualityType == 'HLS' || $qualityType == 'x265') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_url_required', ['number' => $number]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_url_regex', ['number' => $number]);
                } elseif ($qualityType == 'Local') {
                    $messages["video_quality_url.{$index}.required"] = __('messages.quality_video_file_required', ['number' => $number]);
                } elseif ($qualityType == 'Embedded') {
                    $messages["quality_video_embed.{$index}.required"] = __('messages.quality_video_embed_required', ['number' => $number]);
                    $messages["quality_video_embed.{$index}.regex"] = __('messages.quality_video_embed_regex', ['number' => $number]);
                } elseif ($qualityType == 'YouTube') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_youtube_required', ['number' => $number]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_youtube_regex', ['number' => $number]);
                } elseif ($qualityType == 'Vimeo') {
                    $messages["quality_video_url_input.{$index}.required"] = __('messages.quality_video_vimeo_required', ['number' => $number]);
                    $messages["quality_video_url_input.{$index}.regex"] = __('messages.quality_video_vimeo_regex', ['number' => $number]);
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
                                    __('messages.subtitle_file_mimes', ['number' => ($index + 1)])
                                );
                            }
                        }
                    }
                }
            }
        });
    }
}
