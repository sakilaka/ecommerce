<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'image_path' => $this->image_path,
            'description' => $this->description,
            'seo' => [
                'title' => $this->seo_title ?: $this->name,
                'description' => $this->seo_description ?: $this->description,
            ],
            'sort_order' => $this->sort_order,
        ];
    }
}
