<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->json('head2head')->nullable()->after('odds');
            $table->json('statistics')->nullable()->after('head2head');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropColumn(['head2head', 'statistics']);
        });
    }
};
