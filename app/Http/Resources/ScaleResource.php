<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'visible_to' => $this->visible_to,
            'domain' => $this->domain,
            'constructs' => $this->constructs,
            'evidence_type' => $this->evidence_type,
            'cadence' => $this->cadence,
            'estimated_minutes' => $this->estimated_minutes,
            'minimum_age' => $this->minimum_age,
            'maximum_age' => $this->maximum_age,
            'instrument_status' => $this->instrument_status,
            'source_name' => $this->source_name,
            'interpretation_note' => $this->interpretation_note,
            'questions' => QuestionResource::collection($this->whenLoaded('questions'))
        ];
    }
}
