<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns
        Schema::table('entertainments', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('duration');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('enable_clips')->default(false);
            $table->text('slug')->nullable();
            $table->text('bunny_trailer_url')->nullable();
            $table->text('bunny_video_url')->nullable();
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('duration');
            $table->time('end_time')->nullable()->after('start_time');
            $table->text('slug')->nullable();
            $table->text('bunny_trailer_url')->nullable();
            $table->text('bunny_video_url')->nullable();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('duration');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('enable_clips')->default(false);
            $table->text('slug')->nullable();
            $table->text('bunny_video_url')->nullable();
        });

            Schema::table('live_tv_channel', function (Blueprint $table) {
                $table->text('slug')->nullable();
            });

            Schema::table('live_tv_category', function (Blueprint $table) {
                $table->text('slug')->nullable();
            });

            Schema::table('seasons', function (Blueprint $table) {
                $table->text('slug')->nullable();
            });


        Schema::table('mobile_settings', function (Blueprint $table) {
            $table->string('type')->nullable();
        });






        $entertainments = DB::table('entertainments')->get();

        foreach ($entertainments as $item) {
            if (!empty($item->name)) {
                DB::table('entertainments')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }

        $episodes = DB::table('episodes')->get();

        foreach ($episodes as $item) {
            if (!empty($item->name)) {
                DB::table('episodes')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }

        $videos = DB::table('videos')->get();

        foreach ($videos as $item) {
            if (!empty($item->name)) {
                DB::table('videos')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }

        $live_tv_channels = DB::table('live_tv_channel')->get();

        foreach ($live_tv_channels as $item) {
            if (!empty($item->name)) {
                DB::table('live_tv_channel')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }

        $live_tv_category = DB::table('live_tv_category')->get();

        foreach ($live_tv_category as $item) {
            if (!empty($item->name)) {
                DB::table('live_tv_category')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }

        $seasons = DB::table('seasons')->get();

        foreach ($seasons as $item) {
            if (!empty($item->name)) {
                DB::table('seasons')
                    ->where('id', $item->id)
                    ->update(['slug' => Str::slug($item->name)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entertainments', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'enable_clips', 'slug', 'bunny_trailer_url', 'bunny_video_url']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'slug', 'bunny_trailer_url', 'bunny_video_url']);
        });

        Schema::table('videos', function (Blueprint $table) {
          $table->dropColumn(['start_time', 'end_time', 'enable_clips', 'slug', 'bunny_video_url']);
        });

        Schema::table('mobile_settings', function (Blueprint $table) {
            $table->dropColumn(['type']);
        });

        Schema::table('live_tv_channel', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
        Schema::table('seasons', function (Blueprint $table) {
            $table->text('slug')->nullable();
        });

        Schema::table('live_tv_category', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
};
