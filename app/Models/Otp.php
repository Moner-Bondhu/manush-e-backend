<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Otp
 *
 * @property int $id
 * @property int $user_id
 * @property string $otp
 * @property bool $is_valid
 * @property int $attempts_left
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 *
 * @package App\Models
 */
class Otp extends Model
{
    use HasFactory;
	protected $table = 'otps';

	protected $casts = [
		'user_id' => 'int',
		'is_valid' => 'bool',
		'attempts_left' => 'int',
		'expires_at' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'otp',
		'is_valid',
		'attempts_left',
		'expires_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function is_valid_otp()
    {
        if(!$this->is_valid){
            return false;
        }

        if($this->expires_at >= Carbon::now()){
            return false;
        }

        if($this->attempts_left < 1){
            return false;
        }

        return true;
    }
}
