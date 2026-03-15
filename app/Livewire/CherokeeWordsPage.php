<?php

namespace App\Livewire;

use App\Models\CherokeeWord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CherokeeWordsPage extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedWordId = null;

    public string $english = '';

    public string $transliteration = '';

    public string $cherokee = '';

    public string $category = 'general';

    public string $confidence = 'needs_review';

    public string $dialect = '';

    public string $notes = '';

    public string $statusMessage = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $firstWord = CherokeeWord::query()->orderBy('english')->first();

        if ($firstWord !== null) {
            $this->loadWord($firstWord->id);
        }
    }

    /**
     * Reset pagination when searching.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Load a word into the edit form.
     */
    public function loadWord(int $wordId): void
    {
        $word = CherokeeWord::query()->findOrFail($wordId);

        $this->selectedWordId = $word->id;
        $this->english = $word->english;
        $this->transliteration = $word->transliteration ?? '';
        $this->cherokee = $word->cherokee;
        $this->category = $word->category;
        $this->confidence = $word->confidence;
        $this->dialect = $word->dialect ?? '';
        $this->notes = $word->notes ?? '';
        $this->statusMessage = '';
    }

    /**
     * Prepare the form for a new manual word.
     */
    public function createNew(): void
    {
        $this->selectedWordId = null;
        $this->english = '';
        $this->transliteration = '';
        $this->cherokee = '';
        $this->category = 'general';
        $this->confidence = 'needs_review';
        $this->dialect = '';
        $this->notes = '';
        $this->statusMessage = '';
    }

    /**
     * Save the current word.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'english' => ['required', 'string', 'max:255'],
            'transliteration' => ['nullable', 'string', 'max:255'],
            'cherokee' => ['required', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'confidence' => ['required', 'in:high,medium,needs_review'],
            'dialect' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($this->selectedWordId !== null) {
            $word = CherokeeWord::query()->findOrFail($this->selectedWordId);

            $word->fill($validated);
            $word->is_user_modified = true;
            $word->save();

            $this->statusMessage = 'Word updated.';

            return;
        }

        $word = CherokeeWord::query()->create([
            ...$validated,
            'seed_id' => 'manual-'.Str::uuid(),
            'ipa' => null,
            'source_key' => 'manual',
            'source_name' => 'Manual entry',
            'source_kind' => 'manual',
            'source_citation' => 'Created from the Livewire word editor.',
            'source_url' => null,
            'source_note' => 'Created from the Livewire word editor.',
            'alternates' => null,
            'is_user_modified' => true,
        ]);

        $this->selectedWordId = $word->id;
        $this->statusMessage = 'Word created.';
    }

    /**
     * Get the paginated words list.
     */
    protected function getWords()
    {
        return CherokeeWord::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner
                        ->where('english', 'like', '%'.$this->search.'%')
                        ->orWhere('transliteration', 'like', '%'.$this->search.'%')
                        ->orWhere('cherokee', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('english')
            ->paginate(12);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.cherokee-words-page', [
            'words' => $this->getWords(),
        ]);
    }
}
