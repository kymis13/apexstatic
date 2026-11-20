<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/demo.json');

        if (! file_exists($path)) {
            $this->command?->error('Не найден demo.json. Запустите: node scripts/generate-backend.mjs');

            return;
        }

        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        Schema::disableForeignKeyConstraints();

        foreach (['audit_logs', 'verification_requests', 'match_records', 'legend_stats', 'player_profiles', 'legends', 'users'] as $table) {
            DB::table($table)->truncate();
        }

        foreach ($data['users'] as &$user) {
            $user['password'] = $user['password_hash'];
            unset($user['password_hash']);
        }
        unset($user);

        $legendIds = collect($data['legends'])->pluck('id', 'slug');

        foreach ($data['profiles'] as &$profile) {
            $profile['main_legend_id'] = $legendIds[$profile['main_legend_slug']] ?? null;
            unset($profile['main_legend_slug']);
        }
        unset($profile);

        DB::table('users')->insert($data['users']);
        DB::table('legends')->insert($data['legends']);
        DB::table('player_profiles')->insert($data['profiles']);
        DB::table('legend_stats')->insert(array_map(function (array $row) {
            unset($row['legend_slug']);

            return $row;
        }, $data['legend_stats']));
        DB::table('match_records')->insert(array_map(function (array $row) {
            unset($row['legend_slug']);

            return $row;
        }, $data['matches']));
        DB::table('verification_requests')->insert($data['requests']);
        DB::table('audit_logs')->insert($data['audit']);

        Schema::enableForeignKeyConstraints();

        $this->command?->info(sprintf(
            'Загружено: %d пользователей, %d легенд, %d профилей, %d матчей.',
            count($data['users']),
            count($data['legends']),
            count($data['profiles']),
            count($data['matches']),
        ));
    }
}
