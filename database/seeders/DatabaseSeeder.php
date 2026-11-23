<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Users are not seeded: staff sign in with their KLAES accounts (dbo.users), which this app never writes.
     */
    public function run(): void
    {
        //
    }
}
