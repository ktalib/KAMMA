<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * A stand-in for KLAES's dbo.users (only the columns this app uses), on the test-only
 * in-memory 'klaes' connection set up in Tests\TestCase. Laravel calls setUpKlaesUsers() itself
 * for every test that uses this trait ("setUp" + trait name), so tests must not call it again.
 */
trait KlaesUsers
{
    protected function setUpKlaesUsers(): void
    {
        Schema::connection('klaes')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('rank')->nullable();
            $table->string('password');
            $table->integer('is_active')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_otp_verified')->nullable();
            $table->string('otp_channel')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Insert a KLAES account directly (the User model is read-only) and load it.
     */
    protected function klaesUser(array $attributes = []): User
    {
        $id = DB::connection('klaes')->table('users')->insertGetId(array_merge([
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'username' => 'abello',
            'email' => 'abello@example.test',
            'password' => Hash::make('secret-pass'),
            'is_active' => 1,
            'email_verified_at' => now(),
        ], $attributes));

        return User::findOrFail($id);
    }
}
