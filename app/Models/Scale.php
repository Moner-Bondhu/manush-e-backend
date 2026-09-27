<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

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
    use HasFactory;
	protected $table = 'scales';

	protected $fillable = [
		'code',
		'name',
		'description',
		'visible_to',
		'domain',
		'constructs',
		'evidence_type',
		'cadence',
		'estimated_minutes',
		'minimum_age',
		'maximum_age',
		'instrument_status',
		'source_name',
		'interpretation_note',
		'is_published',
	];

	protected $casts = [
		'constructs' => 'array',
		'estimated_minutes' => 'integer',
		'minimum_age' => 'integer',
		'maximum_age' => 'integer',
		'is_published' => 'boolean',
	];

	public function questions()
	{
		return $this->hasMany(Question::class);
	}
}
