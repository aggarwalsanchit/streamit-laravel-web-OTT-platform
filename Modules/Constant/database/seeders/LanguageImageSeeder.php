<?php

namespace Modules\Constant\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Modules\Constant\Models\Constant;

class LanguageImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedLanguageImages();
    }

    private function seedLanguageImages(): void
    {
        $languageImages = [
            'english' => 'English.png',
            'hindi' => 'Hindi.png',
            'tamil' => 'Tamil.png',
            'telugu' => 'Telugu.png',
            'malayalam' => 'Malyalam.png',
            'spanish' => 'Spanish.png',
            'french' => 'Freanch.png',
            'arabic' => 'Arebic.png',
            'german' => 'German.png',
        ];

        foreach ($languageImages as $languageValue => $imageFileName) {
            $constant = Constant::where('type', 'movie_language')
                ->where('value', $languageValue)
                ->first();

            if ($constant) {
                $imagePath = 'images/' . $imageFileName;
                $uploadedUrl = $this->uploadToSpaces($imagePath);
                if ($uploadedUrl) {
                    $constant->language_image = extractFileNameFromUrl($uploadedUrl, 'constant');
                }
                $constant->save();
            }
        }
    }

    private function uploadToSpaces($publicPath)
    {
    $localFilePath = public_path($publicPath);
    $remoteFilePath = 'constant/image/' . basename($publicPath);

        if (file_exists($localFilePath)) {
            $disk = env('ACTIVE_STORAGE', 'local');

            if ($disk === 'local') {
                Storage::disk($disk)->put('public/' . $remoteFilePath, file_get_contents($localFilePath));
                return asset('storage/' . $remoteFilePath);
            } else {
                Storage::disk($disk)->put($remoteFilePath, file_get_contents($localFilePath));
                return Storage::disk($disk)->url($remoteFilePath);
            }
        }

        return false;
    }
}
