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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->integer('league_id')->unique(); // API ID
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('logo')->nullable();
    
            $table->string('country_name')->nullable();
            $table->string('country_code')->nullable();
            $table->string('country_flag')->nullable();
    
            $table->boolean('is_top_league')->default(false); // ✅ mark as top league
            $table->json('seasons')->nullable(); // store all seasons info as JSON
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
