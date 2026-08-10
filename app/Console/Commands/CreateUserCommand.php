<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('user:create {login} {password} {--name=Admin}')]
#[Description('Create a user (public registration is disabled, so this is the only way in)')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $validator = Validator::make([
            'login' => $this->argument('login'),
            'password' => $this->argument('password'),
            'name' => $this->option('name'),
        ], [
            'login' => ['required', 'string', 'min:3', 'max:255', 'unique:users,login'],
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
            'login' => $data['login'],
            'password' => $data['password'],
        ]);

        $this->info("User created: {$data['login']}");

        return self::SUCCESS;
    }
}
