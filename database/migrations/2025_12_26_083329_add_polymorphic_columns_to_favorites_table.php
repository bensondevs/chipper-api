<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table(
            'favorites',
            fn (Blueprint $table) => $table->morphs('favoritable'),
        );

        DB::table('favorites')
            ->whereNotNull('post_id')
            ->update([
                'favoritable_id' => DB::raw('post_id'),
                'favoritable_type' => Post::class,
            ]);

        Schema::table(
            'favorites',
            fn (Blueprint $table) => $table->dropColumn('post_id'),
        );
    }

    public function down(): void
    {
        //
    }
};
