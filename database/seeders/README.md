# Database Seeder Documentation

## Overview

This project includes comprehensive database seeders for populating the database with fixture data, including countries, leagues, fixtures, and odds.

## Files Created

### Factories

1. **CountryFactory.php** - Generates realistic country data
2. **LeagueFactory.php** - Generates top European league data
3. **FixtureFactory.php** - Generates football match fixtures with various states
4. **OddFactory.php** - Generates betting odds data

### Seeders

1. **FixtureSeeder.php** - Main seeder that orchestrates the seeding process

## Usage

### Seed All Data

To seed the entire database including users and fixtures:

```bash
php artisan db:seed
```

### Seed Only Fixtures

To seed only fixture-related data (countries, leagues, fixtures, odds):

```bash
php artisan db:seed --class=FixtureSeeder
```

### Fresh Migration with Seeding

To reset the database and seed fresh data:

```bash
php artisan migrate:fresh --seed
```

## What Gets Seeded

### Countries (5 records)
- England (GB)
- Spain (ES)
- Germany (DE)
- Italy (IT)
- France (FR)

### Leagues (5 records)
- Premier League (England)
- La Liga (Spain)
- Bundesliga (Germany)
- Serie A (Italy)
- Ligue 1 (France)

### Fixtures (45 records)
- **20 finished fixtures** - Completed matches with final scores
- **15 upcoming fixtures** - Scheduled future matches
- **10 mixed status fixtures** - Various match states (live, halftime, etc.)

### Odds (Variable, ~126 records)
- Betting odds from various bookmakers
- Different bet types (Match Winner, Over/Under, Both Teams Score, etc.)
- Automatically associated with fixtures

## Factory Usage Examples

### Create Custom Fixtures

```php
use App\Models\Fixture;

// Create a finished fixture
$fixture = Fixture::factory()->finished()->create();

// Create an upcoming fixture
$fixture = Fixture::factory()->upcoming()->create();

// Create a fixture with odds
$fixture = Fixture::factory()->withOdds()->create();

// Create multiple fixtures
Fixture::factory()->count(10)->create();
```

### Create Custom Leagues

```php
use App\Models\League;

// Create a top league
$league = League::factory()->topLeague()->create();

// Create a specific league
$league = League::factory()
    ->league(39, 'Premier League', 'GB')
    ->create();
```

### Create Custom Odds

```php
use App\Models\Odd;

// Create odds for a specific fixture
$odds = Odd::factory()
    ->forFixture($fixtureId)
    ->count(5)
    ->create();

// Create match winner odds
$odd = Odd::factory()->matchWinner()->create();
```

## Data Structure

### Fixture Data Includes:
- Match details (date, venue, referee)
- League information
- Home and away team data
- Match status and scores
- Optional odds, head-to-head, and statistics

### Realistic Team Data:
The seeder includes real team names and IDs from top European leagues:
- Manchester United, Liverpool, Arsenal, Chelsea (Premier League)
- Barcelona, Real Madrid, Atletico Madrid (La Liga)
- Bayern Munich, Borussia Dortmund (Bundesliga)
- AC Milan, Juventus, Napoli (Serie A)
- Paris Saint Germain, Monaco (Ligue 1)

## Customization

You can modify the seeder to create more or fewer records by editing `database/seeders/FixtureSeeder.php`:

```php
// Change the number of fixtures created
Fixture::factory()->count(50)->finished()->create(); // Instead of 20
```

## Notes

- All fixtures are created with unique `fixture_id` values
- Odds are randomly associated with fixtures (70% of fixtures get odds)
- League data includes realistic logos and flags from API-Sports
- The seeder uses `firstOrCreate` for countries and leagues to avoid duplicates
