<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Response
 *
 * @property int $id
 * @property int $profile_id
 * @property int $question_id
 * @property int $option_id
 * @property string|null $text_answer
 * @property int|null $numeric_answer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Option $option
 * @property Profile $profile
 * @property Question $question
 *
 * @package App\Models
 */
class Response extends Model
{
    use HasFactory;
	protected $table = 'responses';

	protected $casts = [
		'profile_id' => 'int',
		'question_id' => 'int',
		'option_id' => 'int',
		'numeric_answer' => 'int'
	];

	protected $fillable = [
		'profile_id',
		'question_id',
		'option_id',
		'text_answer',
		'numeric_answer'
	];

	public function option()
	{
		return $this->belongsTo(Option::class);
	}

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}

	public function question()
	{
		return $this->belongsTo(Question::class);
	}
}
