<?php

use App\Models\AdminWatchedContent;
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
        // truncate table
        AdminWatchedContent::truncate();

        Schema::table('admin_watched_contents', function (Blueprint $table) {
            $table->dropForeign('admin_watched_contents_content_id_foreign');
            $table->dropColumn('content_id');

            $table->unsignedBigInteger('content_video_id')->nullable();
            $table->foreign('content_video_id')->references('id')->on('content_videos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_watched_contents', function (Blueprint $table) {
            //
        });
    }
};
