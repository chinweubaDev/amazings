<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class UpdateLiveFixtures extends Command
{
    protected $signature = 'fixtures:update-live';
    protected $description = 'Update live fixtures status and scores from API-Football';

    public function handle()
    {
        $apiKey = env('FOOTBALL_API_KEY');
        
        if (!$apiKey) {
            $this->error('❌ FOOTBALL_API_KEY not set in .env file');
            return 1;
        }

        $today = Carbon::today()->toDateString();
        
        $this->info("🔄 Checking today's fixtures ({$today}) that are not finished...");

        // Get today's fixtures that are not finished
        $fixtures = Fixture::whereDate('date', $today)
            ->whereNotIn('status_short', ['FT', 'AET', 'PEN', 'CANC', 'ABD', 'AWD', 'WO'])
            ->get();

        if ($fixtures->isEmpty()) {
            $this->info('ℹ️ No unfinished fixtures found for today.');
            return 0;
        }

        $this->info("📊 Found {$fixtures->count()} unfinished fixture(s) for today");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($fixtures as $fixture) {
            try {
                $this->info("🔍 Fetching update for fixture #{$fixture->fixture_id}: {$fixture->home_team_name} vs {$fixture->away_team_name}");

                // Fetch specific fixture data from API
                $response = Http::withHeaders([
                    'x-apisports-key' => $apiKey,
                ])->get('https://v3.football.api-sports.io/fixtures', [
                    'id' => $fixture->fixture_id,
                ]);

                $data = $response->json();

                if (empty($data['response']) || !isset($data['response'][0])) {
                    $this->warn("⚠️ No data returned for fixture #{$fixture->fixture_id}");
                    $errorCount++;
                    continue;
                }

                $item = $data['response'][0];
                $fixtureData = $item['fixture'];
                $teams = $item['teams'];
                $goals = $item['goals'];
                $score = $item['score'];

                // Update the fixture
                $fixture->update([
                    'status_long' => $fixtureData['status']['long'] ?? null,
                    'status_short' => $fixtureData['status']['short'] ?? null,
                    'elapsed' => $fixtureData['status']['elapsed'] ?? null,
                    'goals_home' => $goals['home'],
                    'goals_away' => $goals['away'],
                    'halftime_home' => $score['halftime']['home'],
                    'halftime_away' => $score['halftime']['away'],
                    'fulltime_home' => $score['fulltime']['home'],
                    'fulltime_away' => $score['fulltime']['away'],
                    'home_team_winner' => $teams['home']['winner'],
                    'away_team_winner' => $teams['away']['winner'],
                ]);

                $statusIcon = $this->getStatusIcon($fixtureData['status']['short']);
                $this->info("✅ Updated: {$fixture->home_team_name} {$goals['home']}-{$goals['away']} {$fixture->away_team_name} {$statusIcon} ({$fixtureData['status']['short']})");
                
                $updatedCount++;

                // Small delay to respect API rate limits
                sleep(1);

            } catch (\Exception $e) {
                $this->error("❌ Error updating fixture #{$fixture->fixture_id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("🎯 Summary:");
        $this->info("   ✅ Successfully updated: {$updatedCount}");
        if ($errorCount > 0) {
            $this->warn("   ⚠️ Errors: {$errorCount}");
        }
        
        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Get status icon for display
     */
    private function getStatusIcon($statusShort)
    {
        return match($statusShort) {
            '1H' => '⚽ 1st Half',
            'HT' => '⏸️ Halftime',
            '2H' => '⚽ 2nd Half',
            'ET' => '⏱️ Extra Time',
            'BT' => '⏸️ Break',
            'P' => '🎯 Penalties',
            'FT' => '🏁 Full Time',
            'AET' => '🏁 After ET',
            'PEN' => '🏁 After Pen',
            'SUSP' => '⏸️ Suspended',
            'INT' => '⏸️ Interrupted',
            'LIVE' => '🔴 Live',
            default => $statusShort,
        };
    }
}
