<?php

namespace App\Models;

use App\Enums\HomepageSectionType;
use Database\Factories\HomepageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'type', 'title', 'is_active', 'sort_order', 'content', 'settings', 'starts_at', 'ends_at'])]
class HomepageSection extends Model
{
    /** @use HasFactory<HomepageSectionFactory> */
    use HasFactory;

    #[Scope]
    protected function activeForStorefront(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HomepageSectionType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'content' => 'array',
            'settings' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
