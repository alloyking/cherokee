<?php

namespace Database\Seeders;

use App\Models\CherokeePhrase;
use App\Models\CherokeeWord;
use Illuminate\Database\Seeder;
use RuntimeException;

class CherokeeLexiconSeeder extends Seeder
{
    /**
     * Seed the Cherokee word and phrase tables.
     */
    public function run(): void
    {
        $this->seedWords();
        $this->seedPhrases();
    }

    /**
     * Seed the Cherokee words table from the canonical JSON file.
     */
    protected function seedWords(): void
    {
        $dataset = $this->readDataset(storage_path('app/private/cherokee/words.seed.json'));
        $sources = $this->indexSources($dataset);
        $protectedSeedIds = CherokeeWord::query()
            ->where('is_user_modified', true)
            ->pluck('seed_id')
            ->all();

        $rows = array_values(array_filter(array_map(function (array $entry) use ($sources, $protectedSeedIds): ?array {
            if (in_array($entry['id'], $protectedSeedIds, true)) {
                return null;
            }

            $source = $sources[$entry['source']] ?? [];

            return [
                'seed_id' => $entry['id'],
                'cherokee' => $entry['cherokee'],
                'transliteration' => $entry['transliteration'],
                'ipa' => $entry['ipa'],
                'english' => $entry['english'],
                'category' => $entry['category'],
                'source_key' => $entry['source'],
                'source_name' => $source['name'] ?? null,
                'source_kind' => $source['kind'] ?? null,
                'source_citation' => $source['citation'] ?? null,
                'source_url' => $source['url'] ?? null,
                'source_note' => $entry['source_note'],
                'confidence' => $entry['confidence'],
                'dialect' => $entry['dialect'],
                'notes' => $entry['notes'],
                'alternates' => isset($entry['alternates'])
                    ? json_encode($entry['alternates'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                'is_user_modified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $dataset['entries'])));

        if ($rows === []) {
            return;
        }

        CherokeeWord::query()->upsert(
            $rows,
            ['seed_id'],
            [
                'cherokee',
                'transliteration',
                'ipa',
                'english',
                'category',
                'source_key',
                'source_name',
                'source_kind',
                'source_citation',
                'source_url',
                'source_note',
                'confidence',
                'dialect',
                'notes',
                'alternates',
                'is_user_modified',
                'updated_at',
            ]
        );
    }

    /**
     * Seed the Cherokee phrases table from the canonical JSON file.
     */
    protected function seedPhrases(): void
    {
        $dataset = $this->readDataset(storage_path('app/private/cherokee/phrases.seed.json'));
        $sources = $this->indexSources($dataset);
        $protectedSeedIds = CherokeePhrase::query()
            ->where('is_user_modified', true)
            ->pluck('seed_id')
            ->all();

        $rows = array_values(array_filter(array_map(function (array $entry) use ($sources, $protectedSeedIds): ?array {
            if (in_array($entry['id'], $protectedSeedIds, true)) {
                return null;
            }

            $source = $sources[$entry['source']] ?? [];

            return [
                'seed_id' => $entry['id'],
                'cherokee' => $entry['cherokee'],
                'transliteration' => $entry['transliteration'],
                'ipa' => $entry['ipa'],
                'english' => $entry['english'],
                'category' => $entry['category'],
                'source_key' => $entry['source'],
                'source_name' => $source['name'] ?? null,
                'source_kind' => $source['kind'] ?? null,
                'source_citation' => $source['citation'] ?? null,
                'source_url' => $source['url'] ?? null,
                'source_note' => $entry['source_note'],
                'confidence' => $entry['confidence'],
                'dialect' => $entry['dialect'],
                'notes' => $entry['notes'],
                'is_user_modified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $dataset['entries'])));

        if ($rows === []) {
            return;
        }

        CherokeePhrase::query()->upsert(
            $rows,
            ['seed_id'],
            [
                'cherokee',
                'transliteration',
                'ipa',
                'english',
                'category',
                'source_key',
                'source_name',
                'source_kind',
                'source_citation',
                'source_url',
                'source_note',
                'confidence',
                'dialect',
                'notes',
                'is_user_modified',
                'updated_at',
            ]
        );
    }

    /**
     * Read and decode a seed dataset.
     *
     * @return array{sources: array<int, array<string, mixed>>, entries: array<int, array<string, mixed>>}
     */
    protected function readDataset(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Cherokee dataset not found: {$path}");
        }

        $decoded = json_decode(file_get_contents($path), true);

        if (! is_array($decoded) || ! isset($decoded['sources'], $decoded['entries'])) {
            throw new RuntimeException("Cherokee dataset is invalid JSON: {$path}");
        }

        return $decoded;
    }

    /**
     * Index dataset sources by their stable source id.
     *
     * @param  array{sources: array<int, array<string, mixed>>}  $dataset
     * @return array<string, array<string, mixed>>
     */
    protected function indexSources(array $dataset): array
    {
        $indexed = [];

        foreach ($dataset['sources'] as $source) {
            if (isset($source['id']) && is_string($source['id'])) {
                $indexed[$source['id']] = $source;
            }
        }

        return $indexed;
    }
}
