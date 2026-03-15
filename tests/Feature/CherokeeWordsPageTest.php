<?php

use App\Livewire\CherokeeWordsPage;
use App\Models\CherokeeWord;
use Livewire\Livewire;

test('words page renders existing words', function () {
    CherokeeWord::query()->create([
        'seed_id' => 'manual-test-word',
        'cherokee' => 'ᎠᎹ',
        'transliteration' => 'ama',
        'ipa' => null,
        'english' => 'water',
        'category' => 'nature.basic',
        'source_key' => 'manual',
        'source_name' => 'Manual entry',
        'source_kind' => 'manual',
        'source_citation' => 'Created in test.',
        'source_url' => null,
        'source_note' => 'Created in test.',
        'confidence' => 'high',
        'dialect' => null,
        'notes' => null,
        'alternates' => null,
        'is_user_modified' => true,
    ]);

    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->get('/words')
        ->assertOk()
        ->assertSee('Word Editor')
        ->assertSee('water')
        ->assertSee('ama')
        ->assertSee('ᎠᎹ');
});

test('words can be updated from the livewire editor', function () {
    $word = CherokeeWord::query()->create([
        'seed_id' => 'manual-edit-word',
        'cherokee' => 'ᎠᎹ',
        'transliteration' => 'ama',
        'ipa' => null,
        'english' => 'water',
        'category' => 'nature.basic',
        'source_key' => 'manual',
        'source_name' => 'Manual entry',
        'source_kind' => 'manual',
        'source_citation' => 'Created in test.',
        'source_url' => null,
        'source_note' => 'Created in test.',
        'confidence' => 'medium',
        'dialect' => null,
        'notes' => null,
        'alternates' => null,
        'is_user_modified' => false,
    ]);

    $user = \App\Models\User::factory()->create();

    Livewire::actingAs($user)
        ->test(CherokeeWordsPage::class)
        ->call('loadWord', $word->id)
        ->set('english', 'fresh water')
        ->set('transliteration', 'ama')
        ->set('cherokee', 'ᎠᎹ')
        ->set('category', 'nature.basic')
        ->set('confidence', 'high')
        ->set('notes', 'Updated from Livewire test.')
        ->call('save')
        ->assertSet('statusMessage', 'Word updated.');

    $word->refresh();

    expect($word->english)->toBe('fresh water')
        ->and($word->confidence)->toBe('high')
        ->and($word->notes)->toBe('Updated from Livewire test.')
        ->and($word->is_user_modified)->toBeTrue();
});
