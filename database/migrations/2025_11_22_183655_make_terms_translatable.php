<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get existing data
        $terms = DB::table('terms')->get();

        // Add new JSON columns
        Schema::table('terms', function (Blueprint $table) {
            $table->json('title_new')->nullable()->after('id');
            $table->json('content_new')->nullable()->after('slug');
        });

        // Migrate existing data to JSON format (using 'en' as default locale)
        foreach ($terms as $term) {
            DB::table('terms')
                ->where('id', $term->id)
                ->update([
                    'title_new' => json_encode(['en' => $term->title]),
                    'content_new' => json_encode(['en' => $term->content]),
                ]);
        }

        // Drop old columns and rename new ones
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['title', 'content']);
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->renameColumn('title_new', 'title');
            $table->renameColumn('content_new', 'content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get existing data
        $terms = DB::table('terms')->get();

        // Add old columns back
        Schema::table('terms', function (Blueprint $table) {
            $table->string('title_old')->nullable()->after('id');
            $table->longText('content_old')->nullable()->after('slug');
        });

        // Migrate data back to string format
        foreach ($terms as $term) {
            $titleData = json_decode($term->title, true);
            $contentData = json_decode($term->content, true);

            DB::table('terms')
                ->where('id', $term->id)
                ->update([
                    'title_old' => $titleData['en'] ?? '',
                    'content_old' => $contentData['en'] ?? '',
                ]);
        }

        // Drop JSON columns and rename old ones back
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['title', 'content']);
        });

        Schema::table('terms', function (Blueprint $table) {
            $table->renameColumn('title_old', 'title');
            $table->renameColumn('content_old', 'content');
        });
    }
};
