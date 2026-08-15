<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@acopio.test'],
            [
                'name' => 'Admin ACOPIO',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole(Role::findByName('admin', 'api'));

        if (Person::count() === 0) {
            Person::factory()->count(40)->create();
            Person::factory()->verified()->count(20)->create();
            Person::factory()->located()->count(10)->create();
        }

        if (SearchReport::count() === 0) {
            SearchReport::factory()->count(15)->create();
        }
    }
}
