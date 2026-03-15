# Cherokee Lexicon & Corpus Project

Laravel 12 app for a Cherokee vocabulary list (with pronunciation and English gloss), phrase list, and sentence-level corpus tooling. Built with an eye toward eventual model training and a browsable/editable word list in the app.

**Audience:** This README is written so another AI agent (or developer) can resume work without prior context.

---

## Goals (current state)

- **Lexicon:** Curated Cherokee words and short phrases with syllabary, Latin transliteration (pronunciation), optional IPA, English gloss, and provenance.
- **Corpus:** Cleaned Cherokee–English sentence pairs with domain labels, train/valid/test splits, and instruction-tuning JSONL exports for translation models.
- **App:** Database as canonical store for words/phrases; Livewire page at `/words` to browse and edit words; user edits protected from reseed.

---

## Stack

- **Laravel 12**, **Livewire 4**, **Tailwind CSS**, **SQLite**
- **PHP 8.x**, **Node** for frontend build
- **Python 3** for corpus pipeline (`scripts/analyze_en_chr.py`)

---

## Data architecture (three shapes)

| Shape    | Purpose                          | Canonical source                    | DB table           |
|----------|----------------------------------|-------------------------------------|--------------------|
| **Words**   | Single lexical items, lookup      | `data/cherokee/words.seed.json`   | `cherokee_words`   |
| **Phrases** | Short fixed expressions           | `data/cherokee/phrases.seed.json` | `cherokee_phrases` |
| **Corpus**  | Sentence pairs for translation    | `data/en-chr.csv` (raw)             | none (files only)   |

- **Words** and **phrases** are seeded into the DB; the app displays/edits words. Phrases are in DB but not yet surfaced in the UI.
- **Corpus** stays in `data/` as CSV/JSONL; the pipeline script generates all derived artifacts. See **Data pipeline** below and `data/README.md` for details.

---

## Key files (quick map)

### Lexicon (words & phrases)

- `data/cherokee/words.seed.json` — canonical word list (~108 entries); add new vocabulary here.
- `data/cherokee/phrases.seed.json` — phrase list (~12 entries).
- `data/cherokee/words.schema.json` — JSON schema for word entries.
- `data/cherokee/phrases.schema.json` — JSON schema for phrase entries.

### Corpus pipeline

- `data/en-chr.csv` — raw parallel corpus (do not overwrite without reason).
- `scripts/analyze_en_chr.py` — single entry point: clean, repair, domain-bucket, build strict/everyday subsets, splits, instruction JSONL.
- `data/README.md` — full description of pipeline steps and generated artifacts.

### Laravel app

- `routes/web.php` — home `/`, words page `/words` (Livewire).
- `app/Livewire/CherokeeWordsPage.php` — words list, search, pagination, create/edit, save (sets `is_user_modified`).
- `resources/views/livewire/cherokee-words-page.blade.php` — UI (dark theme, lime accent).
- `resources/views/layouts/app.blade.php` — layout used by words page.
- `resources/css/app.css` — Tailwind + dark theme CSS variables.
- `app/Models/CherokeeWord.php`, `app/Models/CherokeePhrase.php` — Eloquent models.
- `database/migrations/2026_03_11_000100_create_cherokee_words_table.php` — words table.
- `database/migrations/2026_03_11_000110_create_cherokee_phrases_table.php` — phrases table.
- `database/migrations/2026_03_11_000120_add_user_modified_flags_to_cherokee_lexicon_tables.php` — `is_user_modified` on both tables.
- `database/seeders/CherokeeLexiconSeeder.php` — reads JSON seeds, upserts into DB; **skips rows where `is_user_modified` is true** so user edits are not overwritten.
- `database/seeders/DatabaseSeeder.php` — calls `CherokeeLexiconSeeder`.

### Tests

- `tests/Feature/CherokeeWordsPageTest.php` — feature tests for words page (render, update).

---

## Database conventions

- **Words:** `seed_id` is unique and comes from the `id` field in `data/cherokee/words.seed.json`. Upserts key on `seed_id`. `alternates` is JSON; seeder stores it as a JSON string for SQLite compatibility.
- **User edits:** Any save from the Livewire words page sets `is_user_modified = true` for that row. The seeder **never overwrites** rows with `is_user_modified = true`.
- **Reseed after changing seed JSON:** `php artisan db:seed --class=CherokeeLexiconSeeder` (or `php artisan db:seed`). New/changed seed entries are upserted; user-modified rows are left as-is.

---

## Commands to run

```bash
# Corpus pipeline (from project root)
python3 scripts/analyze_en_chr.py

# Laravel: migrate, seed lexicon, run app
php artisan migrate
php artisan db:seed --class=CherokeeLexiconSeeder
php artisan serve

# Optional: run tests
php artisan test
```

Words page: **http://localhost:8000/words**

---

## Data pipeline (summary)

1. Read `data/en-chr.csv`.
2. Repair malformed rows (e.g. multiple Cherokee fragments in extra columns).
3. Reject bad rows (duplicates, English on wrong side, source tags, etc.); output cleaned JSONL and optional bad/repaired CSVs.
4. Assign coarse domains: `general_parallel`, `story_narrative`, `news`, `reference`, etc.
5. Build **strict-training** subset (fewer noisy/entity-heavy/news rows).
6. Build **everyday-training** subset (shorter, more practical, less archaic).
7. Deterministic train/valid/test splits (e.g. via `zlib.crc32` on content).
8. Export instruction-tuning JSONL (Cherokee→English and English→Cherokee) for both subsets.

All generated filenames and semantics are documented in `data/README.md`. Do not edit generated artifacts by hand; change `scripts/analyze_en_chr.py` and rerun.

---

## Provenance and sources

- **Words:** Entries reference a `source` key (e.g. `ced_first500`, `shiyo_months`). A `sources` object in `data/cherokee/words.seed.json` maps keys to name, kind, citation, url. `confidence` and `notes` are optional.
- **Phrases:** Same idea; sources like `shiyo`, `ced_dont_cry`.
- **Corpus:** Raw CSV is external; pipeline does not add provenance beyond domain labels.

---

## Suggested next steps (for an AI or human)

1. **Expand words:** Add more entries to `data/cherokee/words.seed.json` (e.g. from cherokeedictionary.net First 500 or other vetted sources), then reseed.
2. **Phrases UI:** Add a Livewire page (or section) for phrases similar to `/words`.
3. **IPA:** Populate `ipa` in word entries where desired; schema already supports it.
4. **Training:** Use `data/en-chr.everyday-training.*.jsonl` or `strict-training` instruction JSONL for translation model experiments; see `data/README.md`.
5. **Validation:** Optionally validate `data/cherokee/words.seed.json` / `data/cherokee/phrases.seed.json` against the JSON schemas in CI or pre-seed.

---

## Past gotchas (for maintainers)

- **SQLite + `upsert`:** The seeder must `json_encode()` the `alternates` array before upsert; Eloquent casts don’t run on raw upsert.
- **User edits:** Only the words page sets `is_user_modified`; any other edit path that should be preserved from reseed needs to set this flag too.
- **Corpus script:** Splits use `zlib.crc32` for determinism; duplicate (normalized) sentence pairs are deduplicated earlier in the pipeline.

---

## Reference

- **Corpus and lexicon data logic:** `data/README.md`
- **Transcript of prior work:** See project’s agent-transcripts if available (e.g. conversation that created this README).
