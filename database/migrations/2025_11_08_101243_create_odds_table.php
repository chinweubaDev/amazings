<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('odds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id');
            $table->unsignedBigInteger('bookmaker_id')->nullable();
            $table->string('bookmaker_name')->nullable();
            $table->string('bet_name')->nullable(); // e.g. Match Winner
            $table->string('bet_value')->nullable(); // e.g. Home, Away, Draw
            $table->decimal('odd', 6, 2)->nullable();
            $table->timestamps();

            $table->foreign('fixture_id')->references('fixture_id')->on('fixtures')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
