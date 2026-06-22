<?php

namespace App\Filament\Pages;

use App\Models\Faq;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;

class FaqCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'Help & FAQ';
    protected static bool $shouldRegisterNavigation = false; // accessed via user menu
    protected static string $view = 'filament.pages.faq-center';

    public string $search = '';
    public string $activeCategory = 'all';

    public function getMaxContentWidth(): ?MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getFaqs()
    {
        $query = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($this->activeCategory !== 'all') {
            $query->where('category', $this->activeCategory);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('question', 'like', "%{$this->search}%")
                  ->orWhere('answer', 'like', "%{$this->search}%");
            });
        }

        return $query->get()->groupBy('category');
    }

    public function getCategoryCounts(): array
    {
        return Faq::where('is_active', true)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    public function trackView(int $faqId): void
    {
        Faq::where('id', $faqId)->increment('view_count');
    }

    public function updatedSearch(): void
    {
        // re-renders automatically via Livewire reactivity
    }
}