<?php

declare(strict_types=1);

namespace HiEvents\Console\Commands;

use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Models\AccountUser;
use HiEvents\Models\User;
use HiEvents\Services\Application\Handlers\Account\CreateAccountHandler;
use HiEvents\Services\Application\Handlers\Account\DTO\CreateAccountDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureSuperAdminCommand extends Command
{
    protected $signature = 'app:ensure-superadmin
        {--email= : Superadmin email}
        {--password= : Superadmin password}';

    protected $description = 'Create or promote a Super Admin user non-interactively (idempotent).';

    public function handle(CreateAccountHandler $createAccountHandler): int
    {
        $email = trim((string) ($this->option('email') ?: env('SUPERADMIN_EMAIL', '')));
        $password = (string) ($this->option('password') ?: env('SUPERADMIN_PASSWORD', ''));

        if ($email === '' || $password === '') {
            $this->line('SUPERADMIN_EMAIL or SUPERADMIN_PASSWORD not set; skipping.');
            return self::SUCCESS;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->info("Creating Super Admin account for {$email}...");
            $createAccountHandler->handle(new CreateAccountDTO(
                first_name: 'Super',
                last_name: 'Admin',
                email: $email,
                password: $password,
                timezone: 'UTC',
                currency_code: 'USD',
            ));
            $user = User::query()->where('email', $email)->first();
        } else {
            $user->password = Hash::make($password);
            $user->save();
        }

        if (! $user) {
            $this->error("Failed to locate user {$email}.");
            return self::FAILURE;
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        AccountUser::query()
            ->where('user_id', $user->id)
            ->update(['role' => Role::SUPERADMIN->name]);

        $this->info("Super Admin ready: {$email} (User ID: {$user->id})");
        return self::SUCCESS;
    }
}
