<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\NewsletterLeadResource;
use App\Models\NewsletterLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NewsletterLeadController extends Controller
{
    public function store(Request $request): NewsletterLeadResource
    {
        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', Rule::in(['en', 'bn'])],
            'interest' => ['nullable', 'string', 'max:120'],
            'consent' => ['accepted'],
        ]);

        $validator->after(function ($validator): void {
            if (blank($validator->getData()['email'] ?? null) && blank($validator->getData()['phone'] ?? null)) {
                $validator->errors()->add('email', 'Please provide an email address or phone number.');
                $validator->errors()->add('phone', 'Please provide an email address or phone number.');
            }
        });

        $validated = $validator->validate();

        $identity = filled($validated['email'] ?? null)
            ? ['email' => $validated['email']]
            : ['phone' => $validated['phone']];

        $lead = NewsletterLead::query()->updateOrCreate(
            $identity,
            [
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'source' => $validated['source'] ?? 'homepage',
                'locale' => $validated['locale'] ?? 'en',
                'interest' => $validated['interest'] ?? null,
                'consented_at' => now(),
                'last_subscribed_at' => now(),
                'is_active' => true,
                'meta' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ],
        );

        return NewsletterLeadResource::make($lead);
    }
}
