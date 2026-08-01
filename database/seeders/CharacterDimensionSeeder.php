<?php

namespace Database\Seeders;

use App\Models\CharacterDimension;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CharacterDimensionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['code' => 'CAGEUR', 'name' => 'Cageur'],
            ['code' => 'BAGEUR', 'name' => 'Bageur'],
            ['code' => 'BENER', 'name' => 'Bener'],
            ['code' => 'PINTER', 'name' => 'Pinter'],
            ['code' => 'SINGER', 'name' => 'Singer'],
        ];

        foreach ($rows as $row) {
            CharacterDimension::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $row['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
