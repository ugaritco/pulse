<?php

namespace Tests;

use Heritage\Database\Eloquent\Factories\Factory;
use Heritage\Database\Eloquent\Factories\HasFactory;
use Heritage\Foundation\Auth\User as AuthUser;
use Heritage\Support\Facades\Hash;

class User extends AuthUser
{
    use HasFactory;

    protected static function newFactory()
    {
        return new class extends Factory
        {
            protected $model = User::class;

            public function definition()
            {
                return [
                    'name' => $this->faker->name(),
                    'email' => $this->faker->safeEmail(),
                    'password' => Hash::make('password'),
                ];
            }
        };
    }
}
