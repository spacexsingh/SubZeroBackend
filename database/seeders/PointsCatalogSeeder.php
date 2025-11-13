<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointAction;
use App\Models\Merchandise;

class PointsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            [
                'code' => 'WATCH_MAIN_STAGE',
                'name' => 'Watch a Talk on Main Stage',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/IxOBh'],
            ],
            [
                'code' => 'WATCH_SUB0_STAGE',
                'name' => 'Watch a Talk sub0 Stage',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/tzMEu'],
            ],
            [
                'code' => 'BALL_PIT',
                'name' => 'Go into the ball pit',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/r2F8S'],
            ],
            [
                'code' => 'PUDGY_PENGUINS_GAME',
                'name' => 'Play the Pudgy Penguins Game',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/MfhvB'],
            ],
            [
                'code' => 'HYDRATION_BEER',
                'name' => 'Have a Hydration Beer',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/UbQFn'],
            ],
            [
                'code' => 'YOGA_ON_ROOF',
                'name' => 'Do Yoga on the Roof',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/Xtgz1'],
            ],
            [
                'code' => 'JOIN_HACKERSPACE',
                'name' => 'Join the Hackers at the Hackerspace',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/LTSlZ'],
            ],
            [
                'code' => 'PING_PONG_ARKIV',
                'name' => 'Play Ping Pong with Arkiv',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/8hcBM'],
            ],
            [
                'code' => 'FREESTYLE_FUNGI_FLOWS',
                'name' => 'Freestyle with Fungi Flows',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/DSQPF'],
            ],
            [
                'code' => 'NOVA_SHOTS_CSGO',
                'name' => 'Join the Nova Shots Counter Strike Contest',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/rFld7'],
            ],
            [
                'code' => 'PBA_QUIZ_MACHINE',
                'name' => 'Play the PBA Quiz Machine',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/ck2DM'],
            ],
            [
                'code' => 'PARTY_IN_BASEMENT',
                'name' => 'Party in the Basement',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/KfxGb'],
            ],
            [
                'code' => 'CHECK_PODCAST_ROOM',
                'name' => 'Check out the Podcast room',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/oJbEx'],
            ],
            [
                'code' => 'GET_TATTOO',
                'name' => 'Get a Tattoo',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/3HWVv'],
            ],
            [
                'code' => 'GET_NAILS_DONE',
                'name' => 'Get your nails done at the salon',
                'points' => 20,
                'meta' => ['identifier' => 'http://sl.sub0.gg/tNeKo'],
            ],
        ];


        foreach ($actions as $a) {
            PointAction::updateOrCreate(['code' => $a['code']], $a);
        }

        // 10x merchandise costs
        $merch = [
            ['code' => 'CHIPPED_NAILS', 'name' => 'Chipped Nails', 'points_cost' => 120],
            ['code' => 'RACING_JACKET', 'name' => 'Racing Jacket', 'points_cost' => 100],
            ['code' => 'LABUBUS', 'name' => 'Labubus', 'points_cost' => 60],
            ['code' => 'TSHIRT', 'name' => 'Tshirt', 'points_cost' => 40],
            ['code' => 'LONGSLEEVE_SHIRT', 'name' => 'Longsleeve Shirt', 'points_cost' => 40],
            ['code' => 'BEACH_SHIRT', 'name' => 'Beach Shirt', 'points_cost' => 40],
            ['code' => 'BUMBAG', 'name' => 'Bumbag', 'points_cost' => 40],
            ['code' => 'SHORTS', 'name' => 'Shorts', 'points_cost' => 40],
            ['code' => 'SPECIAL_COCKTAIL', 'name' => 'Special Cocktail', 'points_cost' => 40],
            ['code' => 'UNDERWEAR', 'name' => 'Underwear', 'points_cost' => 20],
            ['code' => 'SOCKS', 'name' => 'Socks', 'points_cost' => 20],
        ];
        foreach ($merch as $m) {
            Merchandise::updateOrCreate(['code' => $m['code']], $m);
        }
    }
}
