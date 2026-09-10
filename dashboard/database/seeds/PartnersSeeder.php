<?php

use App\Models\Partner;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PartnersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $faker->addProvider(new \Bezhanov\Faker\Provider\Commerce($faker));

        for ($i = 0; $i < 10; $i++) {
            Partner::create([
                'website' => $faker->domainName,
                'email' => $faker->email,
                'phone' => $faker->e164PhoneNumber,
                'logo' => 'https://loremflickr.com/320/240?q=' . microtime(),
                'description' => $faker->text,
            ]);
        }
    }
}
