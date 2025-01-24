<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreadType;

class BreadTypeSeeder extends Seeder
{
    public function run()
    {
        $breadTypes = [
            'Леб без квасец',
            'Ржан леб',
            'Француски леб',
            'Планински леб',
            'Италијански леб'
        ];

        foreach ($breadTypes as $type) {
            BreadType::updateOrCreate(['name' => $type]);
        }
    }
}