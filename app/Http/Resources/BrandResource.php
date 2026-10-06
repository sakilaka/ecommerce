<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_path' => $this->logo_path,
            'description' => $this->description,
            'seo' => [
                'title' => $this->seo_title ?: $this->name,
                'description' => $this->seo_description ?: $this->description,
            ],
            'is_official' => $this->is_official,
        ];
    }
}
