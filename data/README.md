# Cherokee Data Pipeline

This directory contains the sentence-level Cherokee-English corpus and the artifacts generated from it.

## Two Different Data Shapes

This project currently keeps Cherokee language data in two different forms:

- `storage/app/private/cherokee/words.seed.json`
  Structured word-level lexicon data with pronunciation, gloss, provenance, and review notes.
- `storage/app/private/cherokee/phrases.seed.json`
  Structured short-phrase data kept separate from words so phrases do not get mixed into the lexicon silently.
- `data/en-chr.csv`
  Raw sentence-level Cherokee-English parallel corpus used for corpus cleaning and future model training.

The main reason to separate them is that words, phrases, and full sentence pairs serve different goals.

- Word and phrase seed files are best for lookup, review, browsing, and future dictionary-style features.
- The `en-chr` corpus is best for translation-style model training and sentence mining.

## Source Of Truth

- Do not edit generated `data/en-chr.*` artifacts by hand.
- If you want different outputs, edit `scripts/analyze_en_chr.py` and rerun it.
- The raw corpus input is `data/en-chr.csv`.

## Corpus Pipeline

Run the pipeline with:

```bash
python3 scripts/analyze_en_chr.py
```

The script performs these steps:

1. Read the raw CSV corpus.
2. Attempt to repair malformed rows when multiple Cherokee fragments were split into extra CSV columns.
3. Reject obviously bad rows such as duplicate pairs, English-left rows, source-tagged rows, or malformed leftovers.
4. Keep accepted rows in `data/en-chr.cleaned.jsonl`.
5. Label accepted rows into coarse domains such as `general_parallel`, `story_narrative`, `news`, and `reference`.
6. Build a stricter training subset that removes rows likely to hurt translation quality, such as named-entity-heavy or news-heavy pairs.
7. Build an even narrower `everyday` subset that prefers shorter, more practical, less archaic-looking sentence pairs.
8. Create deterministic train/valid/test splits from the strict and everyday subsets.
9. Export instruction-tuning JSONL files in both directions: Cherokee to English and English to Cherokee.

## Generated Files

### Raw And Review Artifacts

- `en-chr.csv`
  Raw imported corpus.
- `en-chr.report.json`
  Main summary report with counts, repair stats, rejection reasons, split sizes, and output paths.
- `en-chr.domain-summary.json`
  Higher-level summary focused on domain buckets and training subset sizes.
- `en-chr.bad-rows.csv`
  Rows that were rejected or could not be repaired cleanly.
- `en-chr.repaired-rows.csv`
  Rows that were malformed in the raw CSV but were reconstructed by the script.

### Cleaned Corpus Artifacts

- `en-chr.cleaned.jsonl`
  Accepted corpus rows after structural cleanup.
- `en-chr.cleaned.preview.jsonl`
  Small preview sample of the accepted corpus.
- `en-chr.buckets/general_parallel.jsonl`
  Mostly ordinary sentence pairs.
- `en-chr.buckets/story_narrative.jsonl`
  Story-like or quoted-speech rows.
- `en-chr.buckets/news.jsonl`
  News or headline-like content.
- `en-chr.buckets/reference.jsonl`
  Reference or explanatory content.
- `en-chr.buckets/scripture.jsonl`
  Present for completeness; currently many scripture-like rows are excluded before this bucket is populated.

### Training Subsets

- `en-chr.strict-training.jsonl`
  Cleaner translation subset intended for broad sentence-pair training.
- `en-chr.strict-training.train.jsonl`
- `en-chr.strict-training.valid.jsonl`
- `en-chr.strict-training.test.jsonl`

- `en-chr.everyday-training.jsonl`
  Narrower subset intended to favor more practical everyday-style language.
- `en-chr.everyday-training.train.jsonl`
- `en-chr.everyday-training.valid.jsonl`
- `en-chr.everyday-training.test.jsonl`

### Instruction-Tuning Exports

- `en-chr.strict-training.instructions.jsonl`
- `en-chr.strict-training.instructions.train.jsonl`
- `en-chr.strict-training.instructions.valid.jsonl`
- `en-chr.strict-training.instructions.test.jsonl`

- `en-chr.everyday-training.instructions.jsonl`
- `en-chr.everyday-training.instructions.train.jsonl`
- `en-chr.everyday-training.instructions.valid.jsonl`
- `en-chr.everyday-training.instructions.test.jsonl`

Each instruction example is exported twice:

- Cherokee -> English
- English -> Cherokee syllabary

## Why There Are Multiple Training Sets

The same corpus can teach different styles depending on what you keep.

- `cleaned`
  Best for broad inspection and corpus mining.
- `strict-training`
  Best when you want a larger translation dataset but still want to remove a lot of obvious noise.
- `everyday-training`
  Best when you want a model to sound more practical and less formal or archaic.

The `everyday` subset intentionally excludes many rows that are still technically valid but are likely to bias the model toward:

- archaic English phrasing
- long formal sentences
- quoted speech
- question-heavy examples
- story-heavy material

## Important Trade-Off

Smaller is not automatically better, and larger is not automatically better.

- A larger corpus gives more coverage.
- A narrower corpus usually gives a cleaner voice.

That is why this project keeps both `strict` and `everyday` outputs.

## Lexicon And Phrase Logic

Sentence-level corpus files in `data/` are not the same thing as the curated seed datasets in `storage/app/private/cherokee/`.

- Use `words.seed.json` when the unit is a single lexical item.
- Use `phrases.seed.json` when the unit is a short fixed expression.
- Use `en-chr` corpus files when the unit is a sentence pair.

This separation makes it easier later to:

- build a searchable word list
- browse phrases separately from words
- train translation models on sentence pairs
- keep pronunciation and provenance attached to curated entries

## Practical Recommendation

If you are experimenting with training:

- start with `en-chr.everyday-training.train.jsonl` for a smaller, more practical translation set
- use `en-chr.everyday-training.valid.jsonl` and `en-chr.everyday-training.test.jsonl` for evaluation
- keep `en-chr.strict-training.*.jsonl` around for broader later experiments

If you are expanding learner-facing content:

- add new vocabulary to `storage/app/private/cherokee/words.seed.json`
- add new expressions to `storage/app/private/cherokee/phrases.seed.json`
