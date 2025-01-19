<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Demography
 *
 * @property int $id
 * @property int $profile_id
 * @property string $dob
 * @property string $gender
 * @property string $grade
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Profile $profile
 *
 * @package App\Models
 */
class Demography extends Model
{
    use HasFactory;
	protected $table = 'demographies';

	protected $casts = [
		'profile_id' => 'int'
	];

	protected $fillable = [
		'profile_id',
		'dob',
		'gender',
		'grade'
	];

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}
}
