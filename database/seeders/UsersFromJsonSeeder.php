<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UsersFromJsonSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/users.json');
        if (!file_exists($path)) {
            $this->command?->warn("users.json not found at: {$path}");
            return;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->command?->error("Unable to read: {$path}");
            return;
        }

        $rows = json_decode($raw, true);
        if (!is_array($rows)) {
            $this->command?->error('Invalid JSON in users.json');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $i => $r) {
            if (!is_array($r)) {
                $skipped++;
                continue;
            }

            $name = trim((string)($r['name'] ?? ''));
            $email = trim((string)($r['email'] ?? ''));
            $roleName = trim((string)($r['role'] ?? ''));

            // JSON uses keys with spaces
            $staffId = trim((string)($r['Staff ID'] ?? ($r['staff_id'] ?? '')));
            $jobTitle = trim((string)($r['Job Title'] ?? ($r['job_title'] ?? '')));
            $number = trim((string)($r['number'] ?? ''));
            $password = (string)($r['password'] ?? '');

            if ($email === '' || $name === '') {
                $this->command?->warn('Row ' . ($i + 1) . ': missing name/email');
                $skipped++;
                continue;
            }

            if ($password === '') {
                // deterministic but not guessable-enough default, so it doesn't break auth flows
                $password = 'ChangeMe_' . Str::random(12);
                $this->command?->warn('Row ' . ($i + 1) . ": missing password for {$email}, generated one.");
            }

            $user = User::query()->where('email', $email)->first();
            $isNew = false;

            if (!$user) {
                $user = new User();
                $user->email = $email;
                $isNew = true;
            }

            // Fill fields if present on the User model/table
            $user->name = $name;
            if ($staffId !== '') {
                $user->staff_id = $staffId;
            }
            if ($jobTitle !== '') {
                $user->job_title = $jobTitle;
            }
            if ($number !== '' && property_exists($user, 'number')) {
                // Only if the model has a 'number' attribute - keep safe.
                $user->number = $number;
            }

            // Only update password when creating
            if ($isNew) {
                $user->password = Hash::make($password);
            }

            $user->save();

            if ($isNew) $created++; else $updated++;

            if ($roleName !== '') {
                $role = Role::query()->firstOrCreate(['name' => $roleName]);

                // Ensure only this role for imported user, to match JSON intent
                $user->syncRoles([$role->name]);
            }
        }

        $this->command?->info("UsersFromJsonSeeder done. created={$created}, updated={$updated}, skipped={$skipped}");
    }
}
