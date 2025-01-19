<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Profile
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $full_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 * @property Collection|Demography[] $demographies
 * @property Collection|Response[] $responses
 *
 * @package App\Models
 */
class Profile extends Model
{
    use HasFactory;

	protected $table = 'profiles';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'type',
		'full_name',
        'relation_type'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function demography()
	{
		return $this->hasOne(Demography::class);
	}

	public function responses()
	{
		return $this->hasMany(Response::class);
	}
}
