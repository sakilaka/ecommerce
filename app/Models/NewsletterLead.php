<?php

namespace App\Models;

use Database\Factories\NewsletterLeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'phone', 'source', 'locale', 'interest', 'consented_at', 'last_subscribed_at', 'is_active', 'admin_note', 'meta'])]
class NewsletterLead extends Model
{
    /** @use HasFactory<NewsletterLeadFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'last_subscribed_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
