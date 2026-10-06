<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label_en' => $this->label_en,
            'label_bn' => $this->label_bn,
            'keyword' => $this->keyword,
            'href' => $this->href,
        ];
    }
}
