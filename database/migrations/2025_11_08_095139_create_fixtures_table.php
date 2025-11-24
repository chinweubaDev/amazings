<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixture_id')->unique();
            $table->string('referee')->nullable();
            $table->string('timezone')->nullable();
            $table->dateTime('date')->nullable();
            $table->bigInteger('timestamp')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('status_long')->nullable();
            $table->string('status_short')->nullable();
            $table->integer('elapsed')->nullable();

            // League info
            $table->unsignedBigInteger('league_id')->nullable();
            $table->string('league_name')->nullable();
            $table->string('league_country')->nullable();
            $table->string('league_logo')->nullable();
            $table->string('league_flag')->nullable();
            $table->string('league_round')->nullable();
            $table->string('league_season')->nullable();

            // Teams
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->string('home_team_name')->nullable();
            $table->string('home_team_logo')->nullable();
            $table->boolean('home_team_winner')->nullable();

            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->string('away_team_name')->nullable();
            $table->string('away_team_logo')->nullable();
            $table->boolean('away_team_winner')->nullable();

            // Scores
            $table->integer('goals_home')->nullable();
            $table->integer('goals_away')->nullable();

            $table->integer('halftime_home')->nullable();
            $table->integer('halftime_away')->nullable();
            $table->integer('fulltime_home')->nullable();
            $table->integer('fulltime_away')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
