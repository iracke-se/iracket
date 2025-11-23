<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scraper_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // general, urls, browser, schedule
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default URL settings
        $defaults = [
            [
                'key' => 'url_rankings',
                'value' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
                'type' => 'string',
                'group' => 'urls',
                'description' => 'URL for rankings scraper',
            ],
            [
                'key' => 'url_players',
                'value' => 'https://www.profixio.com/fx/lisens/public_oversikt.php',
                'type' => 'string',
                'group' => 'urls',
                'description' => 'URL for players list scraper',
            ],
            [
                'key' => 'url_transitions',
                'value' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
                'type' => 'string',
                'group' => 'urls',
                'description' => 'URL for transitions scraper',
            ],
            [
                'key' => 'url_series',
                'value' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
                'type' => 'string',
                'group' => 'urls',
                'description' => 'URL for series scraper',
            ],
            [
                'key' => 'url_live_center',
                'value' => 'https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php',
                'type' => 'string',
                'group' => 'urls',
                'description' => 'URL for live center scraper',
            ],
        ];

        foreach ($defaults as $setting) {
            \DB::table('scraper_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_settings');
    }
};
