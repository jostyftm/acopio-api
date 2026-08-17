<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationType;
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
            MunicipalitySeeder::class,
            RolePermissionSeeder::class,
            AffectationStatusSeeder::class,
            OrganizationTypeSeeder::class,
            FacilityTypeSeeder::class,
            PropertyTypeSeeder::class,
            IncidentTypeSeeder::class,
            NeedsSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@acopio.test'],
            [
                'name' => 'Admin ACOPIO',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole(Role::findByName('admin', 'api'));

        $orgType = OrganizationType::query()->where('code', 'fundacion')->firstOrFail();
        $organization = Organization::query()->firstOrCreate(
            ['name' => 'Fundación Esperanza'],
            [
                'organization_type_id' => $orgType->id,
                'description' => 'Organización de apoyo a personas afectadas por emergencias.',
            ]
        );

        $orgAdmin = User::query()->firstOrCreate(
            ['email' => 'org.admin@fundacion.test'],
            [
                'name' => 'Admin Fundación Esperanza',
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
            ]
        );
        $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));

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
