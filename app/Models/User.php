<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

/**
 * Staff sign in with their KLAES accounts: dbo.users in the shared database, read through the
 * unprefixed 'klaes' connection. KLAES owns these rows (names, passwords, activation), so this
 * app never writes them. The SQL login has no write grant on dbo.users either.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'klaes';

    protected $table = 'users';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'twofa_secret',
        'otp',
        'email_verification_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn () => throw new LogicException('KLAES users are read-only here: change them in KLAES.'));
        static::deleting(fn () => throw new LogicException('KLAES users are read-only here: change them in KLAES.'));
    }

    /**
     * No "remember me": setting or cycling the token would write dbo.users.
     */
    public function getRememberTokenName()
    {
        return '';
    }

    /**
     * KLAES keeps first and last name apart; the layout shows one name.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name) ?: (string) $this->username);
    }

    /**
     * KLAES Super Admin (klas App\Models\User::isSuperAdmin): dbo.users.type "Super Admin" (or KLAES's "Supper Admin").
     * KLAES also counts a Super Admin role; KAMMA does not read KLAES roles yet, so only the user type counts here.
     */
    public function isSuperAdmin(): bool
    {
        return in_array(strtolower(trim((string) $this->type)), ['super admin', 'supper admin'], true);
    }
}
