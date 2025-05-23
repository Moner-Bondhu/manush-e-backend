<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Scale
 * 
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $visible_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Question[] $questions
 *
 * @package App\Models
 */
class Scale extends Model
{
	protected $table = 'scales';

	protected $fillable = [
		'name',
		'description',
		'visible_to'
	];

	public function questions()
	{
		return $this->hasMany(Question::class);
	}
}
