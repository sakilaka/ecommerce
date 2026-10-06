<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HomepageSectionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminHomepageSectionResource;
use App\Models\AuditLog;
use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HomepageSectionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(HomepageSectionType::class)],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $sections = HomepageSection::query()
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('key', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['type']), fn (Builder $query): Builder => $query->where('type', $validated['type']))
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminHomepageSectionResource::collection($sections);
    }

    public function store(Request $request): AdminHomepageSectionResource
    {
        $validated = $this->validatedData($request);

        $section = HomepageSection::query()->create($validated);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'homepage_section.created',
            'auditable_type' => HomepageSection::class,
            'auditable_id' => $section->id,
            'old_values' => [],
            'new_values' => $this->auditValues($section),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminHomepageSectionResource::make($section);
    }

    public function show(HomepageSection $homepageSection): AdminHomepageSectionResource
    {
        return AdminHomepageSectionResource::make($homepageSection);
    }

    public function update(Request $request, HomepageSection $homepageSection): AdminHomepageSectionResource
    {
        $validated = $this->validatedData($request, $homepageSection);
        $before = $this->auditValues($homepageSection);

        $homepageSection->update($validated);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'homepage_section.updated',
            'auditable_type' => HomepageSection::class,
            'auditable_id' => $homepageSection->id,
            'old_values' => $before,
            'new_values' => $this->auditValues($homepageSection),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminHomepageSectionResource::make($homepageSection->refresh());
    }

    public function reorder(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'sections' => ['required', 'array', 'min:1', 'max:50'],
            'sections.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists('homepage_sections', 'id')],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['sections'] as $section) {
                HomepageSection::query()
                    ->whereKey($section['id'])
                    ->update(['sort_order' => $section['sort_order']]);
            }
        });

        $sections = HomepageSection::query()
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'homepage_section.reordered',
            'auditable_type' => HomepageSection::class,
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => ['sections' => $validated['sections']],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminHomepageSectionResource::collection($sections);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?HomepageSection $section = null): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:120', Rule::unique('homepage_sections', 'key')->ignore($section)],
            'type' => ['required', Rule::enum(HomepageSectionType::class)],
            'title' => ['nullable', 'string', 'max:160'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'content' => ['required', 'array'],
            'content.en' => ['required', 'array'],
            'content.bn' => ['required', 'array'],
            'settings' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(HomepageSection $section): array
    {
        return [
            'key' => $section->key,
            'type' => $section->type?->value ?? $section->type,
            'title' => $section->title,
            'is_active' => $section->is_active,
            'sort_order' => $section->sort_order,
            'starts_at' => $section->starts_at?->toISOString(),
            'ends_at' => $section->ends_at?->toISOString(),
        ];
    }
}
