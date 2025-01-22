<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'text' => $this->text,
            'subtext' => $this->subtext,
            'image' => $this->image,
            'type' => $this->type,
            'order' => $this->order,
            'scale' => new ScaleResource($this->whenLoaded('scale')),
            'options' => OptionResource::collection($this->whenLoaded('options'))
        ];
    }
}
