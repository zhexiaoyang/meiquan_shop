<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use Notifiable, HasMultiAuthApiTokens, HasRoles;

    public function findForPassport($username)
    {
        $credentials['name'] = $username;

        return self::query()->where($credentials)->first();
    }
}