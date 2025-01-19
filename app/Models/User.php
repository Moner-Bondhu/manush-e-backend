<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property string|null $experiment_tag
 * @property string $user_type
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Otp[] $otps
 * @property Collection|Profile[] $profiles
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
	protected $table = 'users';

	protected $hidden = [
		'remember_token'
	];

	protected $fillable = [
		'name',
		'phone_number',
		'experiment_tag',
		'user_type',
		'remember_token',
        'is_onboarded'
	];

    protected $casts = [
		'is_onboarded' => 'bool'
	];

	public function otps()
	{
		return $this->hasMany(Otp::class);
	}

    public function get_valid_otp(){
        $valid_otp = Otp::where('user_id', $this->id)->where('is_valid', 1)->where('attempts_left', '>', 0)->where('expires_at', '>', now())->get()->first();

        if(!$valid_otp){
            return false;
        }

        return $valid_otp;
    }

	public function profiles()
	{
		return $this->hasMany(Profile::class);
	}
}
