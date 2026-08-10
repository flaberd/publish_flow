<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('user:create {email} {password} {--name=Admin}')]
#[Description('Create a user (public registration is disabled, so this is the only way in)')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $validator = Validator::make([
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'name' => $this->option('name'),
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $this->info("User created: {$data['email']}");

        return self::SUCCESS;
    }
}
