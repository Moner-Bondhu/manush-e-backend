<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'full_name' => $this->full_name,
            'type' => $this->type,
            'relation_type' => $this->relation_type,
            'demography' => $this->demography ? new DemographyResource($this->demography) : false,
            'user' => new UserResource($this->whenLoaded('user')),

        ];
    }
}
