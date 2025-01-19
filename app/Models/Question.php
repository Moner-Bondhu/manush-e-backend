<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Question
 * 
 * @property int $id
 * @property int $scale_id
 * @property string $text
 * @property string|null $subtext
 * @property string|null $image
 * @property string $type
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Scale $scale
 * @property Collection|Response[] $responses
 *
 * @package App\Models
 */
class Question extends Model
{
	protected $table = 'questions';

	protected $casts = [
		'scale_id' => 'int',
		'order' => 'int'
	];

	protected $fillable = [
		'scale_id',
		'text',
		'subtext',
		'image',
		'type',
		'order'
	];

	public function scale()
	{
		return $this->belongsTo(Scale::class);
	}

	public function responses()
	{
		return $this->hasMany(Response::class);
	}
}
