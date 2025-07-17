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
 * Class Option
 *
 * @property int $id
 * @property string $text
 * @property bool $is_image
 * @property int $value
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Response[] $responses
 *
 * @package App\Models
 */
class Option extends Model
{
    use HasFactory;
	protected $table = 'options';

	protected $casts = [
		'is_image' => 'bool',
		'value' => 'int',
		'order' => 'int',
        'question_id' => 'int'
	];

	protected $fillable = [
		'text',
		'is_image',
		'value',
		'order',
        'question_id'
	];

    public function question()
	{
		return $this->belongsTo(Question::class);
	}

	public function responses()
	{
		return $this->hasMany(Response::class);
	}
}
