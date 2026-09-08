<?php
namespace Modules\Banner\database\seeders;
use Illuminate\Database\Seeder;
use Modules\Banner\Models\Banner;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('banners')->delete();

        $banners = array (
            0 =>
            array (
                'id' => 1,
                'title' => NULL,
                'file_url' => '/dummy-images/entertainment/movie/player/web-home-player.jpg',
                'poster_url' => '/dummy-images/entertainment/movie/player/app-home-player.jpg',
                'type' => 'movie',
                'type_id' => '27',
                'type_name' => 'The Daring Player',
                'status' => 1,
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'created_at' => '2024-10-08 05:18:48',
                'updated_at' => '2024-10-08 05:18:48',
                'deleted_at' => NULL,
                'banner_for' => 'home',
                'poster_tv_url' => '/dummy-images/entertainment/movie/player/tv-home-player.jpg',
            ),
            1 =>
            array (
                'id' => 2,
                'title' => NULL,
                'poster_url' => '/dummy-images/entertainment/movie/smilingdoll/app-home.jpg',
                'file_url' => '/dummy-images/entertainment/movie/smilingdoll/web-home.jpg',
                'type' => 'tvshow',
                'type_id' => '1',
                'type_name' => 'The Smiling Doll',
                'status' => 1,
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'created_at' => '2024-10-08 05:19:29',
                'updated_at' => '2024-10-08 05:19:29',
                'deleted_at' => NULL,
                'banner_for' => 'home',
                'poster_tv_url' => '/dummy-images/entertainment/movie/smilingdoll/tv-home.jpg',
            ),
            2 =>
            array (
                'id' => 3,
                'title' => NULL,
                'poster_url' => '/dummy-images/entertainment/movie/bluey/app-home-bluey.jpg',
                'file_url' => '/dummy-images/entertainment/movie/bluey/web-home-bluey.jpg',
                'type' => 'movie',
                'type_id' => '92',
                'type_name' => 'Bluey',
                'status' => 1,
                'created_by' => 2,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2024-10-08 05:20:16',
                'updated_at' => '2025-04-22 10:55:00',
                'deleted_at' => NULL,
                'banner_for' => 'movie',
                'poster_tv_url' => '/dummy-images/entertainment/movie/bluey/tv-home-bluey.jpg',
            ),
            3 =>
            array (
                'id' => 4,
                'title' => NULL,
                'poster_url' => '/dummy-images/entertainment/movie/black/app-movie-black.jpg',
                'file_url' => '/dummy-images/entertainment/movie/black/web-movie-black.jpg',
                'type' => 'movie',
                'type_id' => '29',
                'type_name' => 'Men in black',
                'status' => 1,
                'created_by' => 2,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2024-10-08 05:20:53',
                'updated_at' => '2025-04-22 10:54:48',
                'deleted_at' => NULL,
                'banner_for' => 'movie',
                'poster_tv_url' => '/dummy-images/entertainment/movie/black/tv-movie-black.jpg',
            ),
            4 =>
            array (
                'id' => 5,
                'title' => NULL,
                'file_url' => '/dummy-images/video/heal/web-videos-heal.jpg',
                'poster_url' => '/dummy-images/video/heal/app-videos-heal.jpg',
                'type' => 'video',
                'type_id' => '2',
                'type_name' => 'Heal Your Mind Before You Fix Your Life',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:54:11',
                'updated_at' => '2025-04-22 10:54:11',
                'deleted_at' => NULL,
                'banner_for' => 'video',
                'poster_tv_url' => '/dummy-images/video/heal/tv-videos-heal.jpg',
            ),
            5 =>
            array (
                'id' => 6,
                'title' => NULL,
                'file_url' => '/dummy-images/video/mind/web-videos-mind.jpg',
                'poster_url' => '/dummy-images/video/mind/app-videos-mind.jpg',
                'type' => 'video',
                'type_id' => '5',
                'type_name' => 'Spiritual Habits for a Calm Mind',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:54:25',
                'updated_at' => '2025-04-22 10:57:41',
                'deleted_at' => NULL,
                'banner_for' => 'video',
                'poster_tv_url' => '/dummy-images/video/mind/tv-videos-mind.jpg',
            ),
            6 =>
            array (
                'id' => 7,
                'title' => NULL,
                'file_url' => '/dummy-images/video/victoryvibes/web-home-vibe.jpg',
                'poster_url' => '/dummy-images/video/victoryvibes/app-home-vibe.jpg',
                'type' => 'video',
                'type_id' => '7',
                'type_name' => 'Victory Vibes',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:54:36',
                'updated_at' => '2025-04-22 10:54:36',
                'deleted_at' => NULL,
                'banner_for' => 'home',
                'poster_tv_url' => '/dummy-images/video/victoryvibes/tv-home-vibe.jpg', 
            ),
            7 =>
            array (
                'id' => 8,
                'title' => NULL,
                'poster_url' => '/dummy-images/entertainment/tvshow/game/app-show-game.jpg',
                'file_url' => '/dummy-images/entertainment/tvshow/game/web-show-game.jpg',
                'type' => 'tvshow',
                'type_id' => '14',
                'type_name' => 'Game of Thrones',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:18',
                'updated_at' => '2025-04-22 10:55:18',
                'deleted_at' => NULL,
                'banner_for' => 'tv_show',
                'poster_tv_url' => '/dummy-images/entertainment/tvshow/game/tv-show-game.jpg',
            ),
            8 =>
            array (
                'id' => 9,
                'title' => NULL,
                'poster_url' => '/dummy-images/entertainment/tvshow/factor/app-show-factor.jpg',
                'file_url' => '/dummy-images/entertainment/tvshow/factor/web-show-factor.jpg',
                'type' => 'tvshow',
                'type_id' => '20',
                'type_name' => 'Fear Factor',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'tv_show',
                'poster_tv_url' => '/dummy-images/entertainment/tvshow/factor/tv-show-factor.jpg',
            ),
            9 =>
            array (
                'id' => 10,
                'title' => 'Discover Unlimited Entertainment',
                'poster_url' => '/dummy-images/ads/banner1.png',
                'type' => null,
                'type_id' => null,
                'type_name' => null,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'promotional',
            ),
            10 =>
            array (
                'id' => 11,
                'title' => 'Watch Anytime, On Any Device',
                'poster_url' => '/dummy-images/ads/banner2.png',
                'type' => null,
                'type_id' => null,
                'type_name' => null,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'promotional',
            ),
            11 =>
            array (
                'id' => 12,
                'title' => 'Explore Movies, Shows & Sports',
                'poster_url' => '/dummy-images/ads/banner3.png',
                'type' => null,
                'type_id' => null,
                'type_name' => null,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'promotional',
            ),
            12 =>
            array (
                'id' => 13,
                'title' => 'Subscribe to Get Unlimited Access',
                'poster_url' => '/dummy-images/ads/banner4.png',
                'type' => null,
                'type_id' => null,
                'type_name' => null,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'promotional',
            ),
            13 =>
            array (
                'id' => 14,
                'title' => 'Aaj Kal LIVE TV',
                'poster_url' => '/dummy-images/livetv/channel/aaj_kal_live_tv.png',
                'poster_tv_url' => '/dummy-images/livetv/channel/aaj_kal_live_tv.png',
                'file_url' => '/dummy-images/livetv/channel/aaj_kal_live_tv.png',
                'type' => 'livetv',
                'type_id' => 1,
                'type_name' => 'Aaj Kal LIVE TV',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'livetv',
            ),
            14 =>
            array (
                'id' => 15,
                'title' => 'ABP Sports',
                'poster_url' => '/dummy-images/livetv/channel/abp_sports.png',
                'poster_tv_url' => '/dummy-images/livetv/channel/abp_sports.png',
                'file_url' => '/dummy-images/livetv/channel/abp_sports.png',
                'type' => 'livetv',
                'type_id' => 2,
                'type_name' => 'ABP Sports',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'livetv',
            ),
            15 =>
            array (
                'id' => 16,
                'title' => 'DN TV',
                'poster_url' => '/dummy-images/livetv/channel/dn_tv.png',
                'poster_tv_url' => '/dummy-images/livetv/channel/dn_tv.png',
                'file_url' => '/dummy-images/livetv/channel/dn_tv.png',
                'type' => 'livetv',
                'type_id' => 3,
                'type_name' => 'DN TV',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'livetv',
            ),
            16 =>
            array (
                'id' => 17,
                'title' => '9xm',
                'poster_url' => '/dummy-images/livetv/channel/9xm.png',
                'poster_tv_url' => '/dummy-images/livetv/channel/9xm.png',
                'file_url' => '/dummy-images/livetv/channel/9xm.png',
                'type' => 'livetv',
                'type_id' => 4,
                'type_name' => '9xm',
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-04-22 10:55:31',
                'updated_at' => '2025-04-22 10:55:31',
                'deleted_at' => NULL,
                'banner_for' => 'livetv',
            ),
        );

        if (env('IS_DUMMY_DATA')) {
            foreach ($banners as $bannerData) {
                $filePath = $bannerData['file_url'] ?? null;
                $posterPath = $bannerData['poster_url'] ?? null;
                $posterTvPath = $bannerData['poster_tv_url'] ?? null;

                $banner = Banner::create(Arr::except($bannerData, ['file_url', 'poster_url', 'poster_tv_url']));

                if (isset($filePath)) {
                    $fileUrl = $this->uploadToSpaces($filePath);
                    if ($fileUrl) {
                        $banner->file_url = extractFileNameFromUrl($fileUrl, 'banner');
                    }
                }

                if (isset($posterPath)) {
                    $posterUrl = $this->uploadToSpaces($posterPath);
                    if ($posterUrl) {
                        $banner->poster_url = extractFileNameFromUrl($posterUrl, 'banner');
                    }
                }

                if (isset($posterTvPath)) {
                    $posterTvUrl = $this->uploadToSpaces($posterTvPath);
                    if ($posterTvUrl) {
                        $banner->poster_tv_url = extractFileNameFromUrl($posterTvUrl, 'banner');
                    }
                }

                $banner->save();
            }

            Schema::enableForeignKeyConstraints();
        }
    }

    private function uploadToSpaces($publicPath)
    {
        $localFilePath = public_path($publicPath);
        $remoteFilePath = 'banner/image/' . basename($publicPath);

        if (file_exists($localFilePath)) {
            // Get the active storage disk from the environment
            $disk = env('ACTIVE_STORAGE', 'local');

            if ($disk === 'local') {
                // Store in the public directory for local storage
                Storage::disk($disk)->put('public/' . $remoteFilePath, file_get_contents($localFilePath));
                return asset('storage/' . $remoteFilePath);
            } else {
                // Upload to the specified storage disk
                Storage::disk($disk)->put($remoteFilePath, file_get_contents($localFilePath));
                return Storage::disk($disk)->url($remoteFilePath);
            }
        }

        return false;
    }

}


