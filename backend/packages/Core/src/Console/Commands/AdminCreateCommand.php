<?php

declare(strict_types=1);

namespace Omersia\Core\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminCreateCommand extends Command
{
    protected $signature = 'admin:create
        {--email= : Admin email address}
        {--password= : Admin password (generated if not provided)}
        {--name=Administrator : Admin display name}';

    protected $description = 'Create a super-admin user for the application';

    public function handle(): int
    {
        $this->info('Omersia Admin User Creation');
        $this->newLine();

        try {
            // 1. Check if super-admin role exists
            if (! $this->checkSuperAdminRole()) {
                return Command::FAILURE;
            }

            // 2. Get or ask for email
            $email = $this->getEmail();
            if (! $email) {
                return Command::FAILURE;
            }

            // 3. Check if user already exists
            if (User::where('email', $email)->exists()) {
                $this->error("A user with email '{$email}' already exists.");

                return Command::FAILURE;
            }

            // 4. Get or generate password
            $password = $this->getPassword();

            // 5. Get name
            $name = $this->option('name') ?? 'Administrator';

            // 6. Create the user
            $this->info('Creating super-admin user...');

            $user = User::create([
                'firstname' => $this->extractFirstname($name),
                'lastname' => $this->extractLastname($name),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // 7. Assign super-admin role
            $superAdminRole = DB::table('roles')
                ->where('name', 'super-admin')
                ->first();

            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $superAdminRole->id,
            ]);

            // 8. Display success message
            $this->newLine();
            $this->info('✓ Super-admin user created successfully!');
            $this->newLine();
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->line('Admin Credentials:');
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->line("Email:    {$email}");
            $this->line("Password: {$password}");
            $this->line("Name:     {$name}");
            $this->line('Role:     super-admin');
            $this->line('─────────────────────────────────────────────────────────────────');
            $this->newLine();

            if (! $this->option('password')) {
                $this->warn('⚠️  IMPORTANT: Save these credentials securely!');
                $this->warn('⚠️  The password was auto-generated. Please change it after first login.');
            } else {
                $this->info('You can now login to the admin panel with these credentials.');
            }

            $this->newLine();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function checkSuperAdminRole(): bool
    {
        $roleExists = DB::table('roles')
            ->where('name', 'super-admin')
            ->exists();

        if (! $roleExists) {
            $this->error('The "super-admin" role does not exist in the database.');
            $this->newLine();
            $this->warn('Please run the roles and permissions seeder first:');
            $this->line('  php artisan db:seed --class=RolesAndPermissionsSeeder');
            $this->newLine();

            return false;
        }

        return true;
    }

    protected function getEmail(): ?string
    {
        $email = $this->option('email');

        // Interactive mode if no email provided
        if (! $email) {
            $email = $this->ask('What is the admin email address?');

            if (! $email) {
                $this->error('Email is required.');

                return null;
            }
        }

        // Validate email format
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email address: '.$validator->errors()->first('email'));

            return null;
        }

        return $email;
    }

    protected function getPassword(): string
    {
        $password = $this->option('password');

        if ($password) {
            // Validate password strength
            if (strlen($password) < 8) {
                $this->warn('Warning: Password is less than 8 characters. Consider using a stronger password.');
            }

            return $password;
        }

        // Interactive mode - ask if user wants to provide password
        if ($this->confirm('Do you want to set a custom password?', false)) {
            $password = $this->secret('Enter password (min 8 characters)');
            $passwordConfirm = $this->secret('Confirm password');

            if ($password !== $passwordConfirm) {
                $this->error('Passwords do not match. Generating random password instead.');

                return $this->generateSecurePassword();
            }

            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters. Generating random password instead.');

                return $this->generateSecurePassword();
            }

            return $password;
        }

        // Generate random password
        $this->info('Generating random secure password...');

        return $this->generateSecurePassword();
    }

    protected function generateSecurePassword(int $length = 16): string
    {
        // Generate a secure random password with mixed case, numbers, and symbols
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}';

        $all = $uppercase.$lowercase.$numbers.$symbols;

        // Ensure at least one of each type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    protected function extractFirstname(string $name): string
    {
        $parts = explode(' ', trim($name));

        return $parts[0];
    }

    protected function extractLastname(string $name): string
    {
        $parts = explode(' ', trim($name));

        if (count($parts) > 1) {
            array_shift($parts);

            return implode(' ', $parts);
        }

        return '';
    }
}
