<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create entry for Roles
		DB::table('roles')->insert([
            ['name' => 'Event Manager'],
            ['name' => 'Attendee'],
        ]);
		
        // Create entry for Event Manager
		User::factory()->create([
            'name' => 'Event Manager',
			'role_id' => 1,
            'email' => 'event@manager.com',
            'password' => Hash::make('Password@123'),
        ]);

		// Instantiate Faker for generating fake data
		$faker = Faker::create();

		// Create 20 events
		foreach (range(1, 20) as $index) {
			Event::create([
				'name' => $faker->sentence(3),
				'description' => $faker->paragraph(3),
				'event_date' => Carbon::now()->addDays(rand(10, 120)),
				'location_country' => 'England',
				'seats_available' => rand(50, 200),
				'user_id' => 1,
			]);
		}
    }
}
