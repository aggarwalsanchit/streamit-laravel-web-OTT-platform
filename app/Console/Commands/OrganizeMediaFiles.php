<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\EntertainmentStreamContentMapping;
use Modules\Entertainment\Models\EntertainmnetDownloadMapping;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;
use Modules\Episode\Models\EpisodeDownloadMapping;
use Modules\Episode\Models\EpisodeStreamContentMapping;
use Modules\Genres\Models\Genres;
use Modules\Video\Models\Video;
use Modules\Video\Models\VideoDownloadMapping;
use Modules\Video\Models\VideoStreamContentMapping;
use Modules\LiveTV\Models\LiveTvCategory;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\CastCrew\Models\CastCrew;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Banner\Models\Banner;

class OrganizeMediaFiles extends Command
{
    protected $signature = 'media:organize';
    protected $description = 'Copy all media files into image/video subfolders dynamically and update database paths';

    public function handle()
    {
        $oldPath = storage_path('app/public/streamit-laravel');

        $models = [

            [
                'name' => 'genres',
                'class' => Genres::class,
                'columns' => ['file_url']
            ],
            [
                'name' => 'movie',
                'class' => Entertainment::class,
                'columns' => ['poster_url', 'thumbnail_url', 'trailer_url', 'video_url_input','download_url','poster_tv_url','seo_image']
            ],

            [
                'name' => 'movie',
                'class' => EntertainmentStreamContentMapping::class,
                'columns' => ['type', 'url']
            ],


            [
                'name' => 'movie',
                'class' => EntertainmnetDownloadMapping::class,
                'columns' => ['type', 'url']
            ],

            [
                'name' => 'tvshow',
                'class' => Entertainment::class,
                'columns' => ['poster_url', 'thumbnail_url', 'trailer_url','poster_tv_url','seo_image']
            ],

            [
                'name' => 'season',
                'class' => Season::class,
                'columns' => ['poster_url','trailer_url','poster_tv_url','seo_image']
            ],

            [
                'name' => 'episode',
                'class' => Episode::class,
                'columns' => ['poster_url','trailer_url','poster_tv_url','video_url_input','download_url','seo_image']
            ],
            [
                'name' => 'episode',
                'class' => EpisodeDownloadMapping::class,
                'columns' => ['type','url']
            ],

            [
                'name' => 'episode',
                'class' => EpisodeStreamContentMapping::class,
                'columns' => ['type','url']
            ],

            [
                'name' => 'video',
                'class' => Video::class,
                'columns' => ['poster_url','trailer_url', 'video_url_input','download_url','poster_tv_url','seo_image']
            ],
            [
                'name' => 'video',
                'class' => VideoDownloadMapping::class,
                'columns' => ['type','url']
            ],

            [
                'name' => 'video',
                'class' => VideoStreamContentMapping::class,
                'columns' => ['type','url']
            ],

            [
                'name' => 'livetv',
                'class' => LiveTvCategory::class,
                'columns' => ['file_url']
            ],

            [
                'name' => 'livetv',
                'class' => LiveTvChannel::class,
                'columns' => ['poster_url','thumb_url','poster_tv_url']
            ],

            [
                'name' => 'castcrew',
                'class' => CastCrew::class,
                'columns' => ['file_url']
            ],
            [
                'name' => 'ads',
                'class' => CustomAdsSetting::class,
                'columns' => ['media']
            ],

            [
                'name' => 'users',
                'class' => User::class,
                'columns' => ['file_url']
            ],

            [
                'name' => 'banner',
                'class' => Banner::class,
                'columns' => ['file_url','poster_url','poster_tv_url']
            ],

        ];

        foreach ($models as $modelInfo) {
            $records = $modelInfo['class']::all();
            $type= $modelInfo['name'];

            if( $type =='episode'){

                $type='tvshow/episode';

            }

            if($type == 'season'){

                $type='tvshow/season';
            }

            foreach ($records as $record) {
                foreach ($modelInfo['columns'] as $column) {
                    $fileName = $record->$column;
                    if (!$fileName) continue;

                    if ($column === 'trailer_url' && $record->trailer_url_type != 'local') continue;
                    if ($column === 'video_url_input' && $record->video_upload_type != 'local') continue;
                    if ($column === 'download_url' && $record->video_upload_type != 'local') continue;
                    if ($column === 'type' && $record->video_upload_type != 'local') continue;
                    if ($column === 'media' && $record->url_type != 'local') continue;

                    $oldFile = $oldPath . '/' . $fileName;

                    if (!File::exists($oldFile)) {
                        $this->warn("Source file not found: {$oldFile}");
                        continue;
                    }

                    $extension = strtolower(File::extension($oldFile));
                    $isVideo = in_array($extension, ['mp4', 'mkv', 'mov', 'avi', 'webm']);
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);

                    if (!$isVideo && !$isImage) continue;

                    $mediaType = $isVideo ? 'video' : 'image';
                    $newDir =storage_path("app/public/{$type}/{$mediaType}/");
                    File::ensureDirectoryExists($newDir);

                    $newFile = $newDir . '/' . $fileName;
                    File::copy($oldFile, $newFile);

                    $this->info("Copied: {$fileName} → {$mediaType}/{$type}");
                }
            }
        }


        if (File::exists($oldPath)) {
            File::deleteDirectory($oldPath);
            $this->info("🗑️ Deleted old folder: {$oldPath}");
        }

        $publicStorage = public_path('storage');
        if (File::exists($publicStorage) || is_link($publicStorage)) {
            File::delete($publicStorage);
            $this->info("🔗 Removed old storage link");
        }

        Artisan::call('storage:link');

        $this->info("✅ New storage link created successfully!");

        $this->info('🎉 All media files organized and cleanup completed successfully!');


    }
}
