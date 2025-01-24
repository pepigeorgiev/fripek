<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        for ($i = 1; $i <= 33; $i++) {
            Company::updateOrCreate(
                ['id' => $i],
                ['name' => "Company $i"]
            );
        }
    }
}