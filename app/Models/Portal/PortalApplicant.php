<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/**
 * An applicant account on the KAMMA portal (kamma_portal_applicants). Members of the public, not KLAES staff, so never
 * dbo.users. The username is the mobile number in local form (0XXXXXXXXXX).
 */
class PortalApplicant extends Model
{
    protected $table = 'portal_applicants';

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** 0XXXXXXXXXX for any Nigerian mobile typed as 080…, +23480… or 23480…; null otherwise. */
    public static function localPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }

        return str_starts_with($digits, '0') && strlen($digits) === 11 ? $digits : null;
    }

    /** The account a sign-in name points at: mobile number (any form), username or email. */
    public static function findForLogin(string $login): ?self
    {
        $login = mb_strtolower(trim($login));
        $phone = self::localPhone($login);

        return self::query()
            ->where(fn ($q) => $q->where('username', $phone ?? $login)->orWhere('email', $login))
            ->orderBy('id')
            ->first();
    }

    /** The shape PortalSession and the portal views use. */
    public function toSession(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_demo' => false,
            'since' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
