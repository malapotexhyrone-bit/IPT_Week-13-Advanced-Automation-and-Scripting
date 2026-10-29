<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $offices = collect([
            ['code' => 'REGISTRAR', 'name' => 'Registrar', 'sort_order' => 10],
            ['code' => 'LIBRARY', 'name' => 'Library', 'sort_order' => 20],
            ['code' => 'ACCOUNTING', 'name' => 'Accounting', 'sort_order' => 30],
            ['code' => 'STUDENT_AFFAIRS', 'name' => 'Student Affairs', 'sort_order' => 40],
        ])->mapWithKeys(function (array $data): array {
            $office = Office::query()->updateOrCreate(
                ['code' => $data['code']],
                [...$data, 'is_active' => true],
            );

            return [$office->code => $office];
        });

        AcademicTerm::query()->updateOrCreate(
            ['code' => '2026-2027-1'],
            ['name' => 'First Semester 2026-2027', 'is_active' => true],
        );

        $generatedPassword = Hash::make(Str::random(40));

        User::query()->updateOrCreate(
            ['email' => 'student.demo@example.test'],
            [
                'name' => 'Ari Santos',
                'role' => User::ROLE_STUDENT,
                'office_id' => null,
                'password' => $generatedPassword,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'library.reviewer@example.test'],
            [
                'name' => 'Morgan Reyes',
                'role' => User::ROLE_OFFICE,
                'office_id' => $offices['LIBRARY']->id,
                'password' => $generatedPassword,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'clearance.admin@example.test'],
            [
                'name' => 'Taylor Cruz',
                'role' => User::ROLE_ADMIN,
                'office_id' => null,
                'password' => $generatedPassword,
            ],
        );
    }
}
