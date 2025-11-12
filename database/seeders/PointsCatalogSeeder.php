<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointAction;
use App\Models\Merchandise;

class PointsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 10x earn points (each 20 -> 200)
        $actions = [
            ['code' => 'WATCH_MAIN_STAGE', 'name' => 'Watch a Talk on Main Stage', 'points' => 200],
            ['code' => 'WATCH_SUB0_STAGE', 'name' => 'Watch a Talk sub0 Stage', 'points' => 200],
            ['code' => 'BALL_PIT', 'name' => 'Go into the ball pit', 'points' => 200],
            ['code' => 'PUDGY_PENGUINS_GAME', 'name' => 'Play the Pudgy Penguins Game', 'points' => 200],
            ['code' => 'HYDRATION_BEER', 'name' => 'Have a Hydration Beer', 'points' => 200],
            ['code' => 'YOGA_ON_ROOF', 'name' => 'Do Yoga on the Roof', 'points' => 200],
            ['code' => 'JOIN_HACKERSPACE', 'name' => 'Join the Hackers at the Hackerspace', 'points' => 200],
            ['code' => 'PING_PONG_ARKIV', 'name' => 'Play Ping Pong with Arkiv', 'points' => 200],
            ['code' => 'FREESTYLE_FUNGI_FLOWS', 'name' => 'Freestyle with Fungi Flows', 'points' => 200],
            ['code' => 'NOVA_SHOTS_CSGO', 'name' => 'Join the Nova Shots Counter Strike Contest', 'points' => 200],
            ['code' => 'PBA_QUIZ_MACHINE', 'name' => 'Play the PBA Quiz Machine', 'points' => 200],
            ['code' => 'PARTY_IN_BASEMENT', 'name' => 'Party in the Basement', 'points' => 200],
            ['code' => 'CHECK_PODCAST_ROOM', 'name' => 'Check out the Podcast room', 'points' => 200],
            ['code' => 'GET_TATTOO', 'name' => 'Get a Tattoo', 'points' => 200],
            ['code' => 'GET_NAILS_DONE', 'name' => 'Get your nails done at the salon', 'points' => 200],
        ];
        foreach ($actions as $a) {
            PointAction::updateOrCreate(['code' => $a['code']], $a);
        }

        // 10x merchandise costs
        $merch = [
            ['code' => 'CHIPPED_NAILS', 'name' => 'Chipped Nails', 'points_cost' => 1200],
            ['code' => 'RACING_JACKET', 'name' => 'Racing Jacket', 'points_cost' => 1000],
            ['code' => 'LABUBUS', 'name' => 'Labubus', 'points_cost' => 600],
            ['code' => 'TSHIRT', 'name' => 'Tshirt', 'points_cost' => 400],
            ['code' => 'LONGSLEEVE_SHIRT', 'name' => 'Longsleeve Shirt', 'points_cost' => 400],
            ['code' => 'BEACH_SHIRT', 'name' => 'Beach Shirt', 'points_cost' => 400],
            ['code' => 'BUMBAG', 'name' => 'Bumbag', 'points_cost' => 400],
            ['code' => 'SHORTS', 'name' => 'Shorts', 'points_cost' => 400],
            ['code' => 'SPECIAL_COCKTAIL', 'name' => 'Special Cocktail', 'points_cost' => 400],
            ['code' => 'UNDERWEAR', 'name' => 'Underwear', 'points_cost' => 200],
            ['code' => 'SOCKS', 'name' => 'Socks', 'points_cost' => 200],
        ];
        foreach ($merch as $m) {
            Merchandise::updateOrCreate(['code' => $m['code']], $m);
        }
    }
}
