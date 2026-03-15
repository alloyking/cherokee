#!/usr/bin/env python3
"""Analyze, repair, and export artifacts for the Cherokee-English corpus."""

from __future__ import annotations

import argparse
import csv
import json
import re
import statistics
import zlib
from collections import Counter
from pathlib import Path


SCRIPTURE_REF_RE = re.compile(
    r'^\s*"?'
    r'('
    r'(?:[1-3]\s*)?'
    r'(?:Genesis|Exodus|Leviticus|Numbers|Deuteronomy|Joshua|Judges|Ruth|'
    r'Samuel|Kings|Chronicles|Ezra|Nehemiah|Esther|Job|Psalms?|Proverbs|'
    r'Ecclesiastes|Song(?:\s+of\s+Solomon)?|Isaiah|Jeremiah|Lamentations|'
    r'Ezekiel|Daniel|Hosea|Joel|Amos|Obadiah|Jonah|Micah|Nahum|Habakkuk|'
    r'Zephaniah|Haggai|Zechariah|Malachi|Matthew|Mark|Luke|John|Acts|'
    r'Romans|Corinthians|Galatians|Ephesians|Philippians|Colossians|'
    r'Thessalonians|Timothy|Titus|Philemon|Hebrews|James|Peter|Jude|Revelation)'
    r')\s+\d+[:;]\d+',
    re.IGNORECASE,
)
SOURCE_TAG_RE = re.compile(r"\[[^\]]{2,120}\]")
WHITESPACE_RE = re.compile(r"\s+")
TITLE_TOKEN_RE = re.compile(r"^[A-Z][a-z]+(?:[’'-][A-Z]?[a-z]+)*$")
ALL_CAPS_TOKEN_RE = re.compile(r"^[A-Z]{2,}$")
LATIN_TOKEN_RE = re.compile(r"[A-Za-z]+")
NEWS_HINTS = {
    "curator",
    "museum",
    "scholarship",
    "festival",
    "premieres",
    "premiere",
    "editor",
    "reporter",
    "director",
    "market",
    "program",
    "executive",
    "staff",
    "opens",
    "re-opens",
    "recipient",
    "youth",
    "competition",
    "exhibit",
}
REFERENCE_HINTS = {
    "dictionary",
    "constitution",
    "beginning cherokee",
    "picture book",
    "cherokee voices",
    "syllabary",
    "grammar",
}
STORY_HINTS = {
    "once",
    "one day",
    "i was",
    "i had",
    "i went",
    "we were",
    "she said",
    "he said",
    "when night",
    "long ago",
}
ARCHAIC_ENGLISH_HINTS = {
    "thou",
    "thee",
    "thy",
    "thine",
    "ye",
    "unto",
    "behold",
    "saith",
    "hath",
    "doth",
    "shalt",
    "wilt",
    "whither",
    "thereof",
    "wherein",
    "lest",
    "verily",
    "brethren",
    "publicans",
    "palsy",
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Analyze and clean the Cherokee-English corpus."
    )
    parser.add_argument(
        "--input",
        default="data/en-chr.csv",
        help="Path to the raw CSV corpus.",
    )
    parser.add_argument(
        "--preview-size",
        type=int,
        default=200,
        help="Number of accepted rows to write to the preview JSONL.",
    )
    parser.add_argument(
        "--train-ratio",
        type=float,
        default=0.90,
        help="Train split ratio for the strict subset.",
    )
    parser.add_argument(
        "--valid-ratio",
        type=float,
        default=0.05,
        help="Validation split ratio for the strict subset.",
    )
    return parser.parse_args()


def normalize_text(text: str) -> str:
    return WHITESPACE_RE.sub(" ", text.strip())


def cherokee_ratio(text: str) -> float:
    chars = [ch for ch in text if not ch.isspace()]
    if not chars:
        return 0.0
    cherokee_chars = sum(0x13A0 <= ord(ch) <= 0x13FF for ch in chars)
    return cherokee_chars / len(chars)


def english_letter_ratio(text: str) -> float:
    chars = [ch for ch in text if not ch.isspace()]
    if not chars:
        return 0.0
    english_chars = sum(("A" <= ch <= "Z") or ("a" <= ch <= "z") for ch in chars)
    return english_chars / len(chars)


def digit_ratio(text: str) -> float:
    chars = [ch for ch in text if not ch.isspace()]
    if not chars:
        return 0.0
    return sum(ch.isdigit() for ch in chars) / len(chars)


def has_scripture_reference(text: str) -> bool:
    return bool(SCRIPTURE_REF_RE.search(text))


def has_source_tag(text: str) -> bool:
    return bool(SOURCE_TAG_RE.search(text))


def extract_source_tags(text: str) -> list[str]:
    return [match.group()[1:-1].strip() for match in SOURCE_TAG_RE.finditer(text)]


def tokenize_latin(text: str) -> list[str]:
    return LATIN_TOKEN_RE.findall(text)


def title_token_ratio(text: str) -> float:
    tokens = tokenize_latin(text)
    if not tokens:
        return 0.0
    return sum(bool(TITLE_TOKEN_RE.match(token)) for token in tokens) / len(tokens)


def acronym_count(text: str) -> int:
    return sum(bool(ALL_CAPS_TOKEN_RE.match(token)) for token in tokenize_latin(text))


def looks_like_headline(text: str) -> bool:
    stripped = text.strip()
    if not stripped:
        return False
    terminal_punctuation = stripped[-1] in ".?!"
    ratio = title_token_ratio(stripped)
    tokens = tokenize_latin(stripped)
    return (
        len(tokens) >= 3
        and len(stripped) <= 120
        and ratio >= 0.45
        and not terminal_punctuation
    )


def is_named_entity_heavy(left: str, right: str) -> bool:
    right_tokens = tokenize_latin(right)
    if not right_tokens:
        return False
    title_tokens = sum(bool(TITLE_TOKEN_RE.match(token)) for token in right_tokens)
    latin_left_tokens = tokenize_latin(left)
    return (
        title_tokens >= 4
        or acronym_count(right) >= 2
        or len(latin_left_tokens) >= 3
    )


def classify_domain(left: str, right: str) -> str:
    lower_right = right.casefold()
    lower_left = left.casefold()

    if has_scripture_reference(left) or has_scripture_reference(right):
        return "scripture"
    if has_source_tag(left) or has_source_tag(right):
        return "reference"
    if looks_like_headline(right):
        return "news"
    if any(hint in lower_right for hint in NEWS_HINTS) and is_named_entity_heavy(left, right):
        return "news"
    if any(hint in lower_right for hint in STORY_HINTS) or '"' in right or "“" in right:
        return "story_narrative"
    if any(hint in lower_right for hint in REFERENCE_HINTS) or any(
        hint in lower_left for hint in REFERENCE_HINTS
    ):
        return "reference"
    return "general_parallel"


def training_exclusion_reasons(left: str, right: str, domain: str) -> list[str]:
    reasons: list[str] = []
    if domain not in {"general_parallel", "story_narrative"}:
        reasons.append(f"domain_{domain}")
    if looks_like_headline(right):
        reasons.append("headline_like")
    if is_named_entity_heavy(left, right):
        reasons.append("named_entity_heavy")
    if digit_ratio(left) > 0.10 or digit_ratio(right) > 0.10:
        reasons.append("digit_heavy_training")
    if english_letter_ratio(left) > 0.12:
        reasons.append("latin_heavy_left")
    if acronym_count(right) > 0:
        reasons.append("acronym_right")
    return reasons


def contains_archaic_english(text: str) -> bool:
    lower = text.casefold()
    return any(re.search(rf"\b{re.escape(word)}\b", lower) for word in ARCHAIC_ENGLISH_HINTS)


def everyday_exclusion_reasons(row: dict[str, object]) -> list[str]:
    reasons: list[str] = []
    english = str(row["english"])
    cherokee = str(row["cherokee"])
    domain = str(row["domain"])

    if domain != "general_parallel":
        reasons.append(f"domain_{domain}")
    if contains_archaic_english(english):
        reasons.append("archaic_english")
    if ";" in english or ":" in english:
        reasons.append("complex_punctuation")
    if len(tokenize_latin(english)) > 22:
        reasons.append("long_english")
    if any(ch in english for ch in "\"“”"):
        reasons.append("quoted_speech")
    if cherokee.endswith("?") or english.endswith("?"):
        reasons.append("question_form")
    return reasons


def classify_row(left: str, right: str, seen_pairs: set[tuple[str, str]]) -> list[str]:
    reasons: list[str] = []
    left_cherokee = cherokee_ratio(left)
    right_cherokee = cherokee_ratio(right)
    left_english = english_letter_ratio(left)
    right_english = english_letter_ratio(right)
    max_len = max(len(left), len(right))

    if max_len < 10:
        reasons.append("too_short")
    if max_len > 300:
        reasons.append("too_long")
    if left_cherokee < 0.35:
        reasons.append("low_cherokee_left")
    if right_cherokee > 0.10:
        reasons.append("cherokee_right")
    if left_english > 0.45 and left_cherokee < 0.15:
        reasons.append("english_left")
    if right_english < 0.30:
        reasons.append("low_english_right")
    if digit_ratio(left) > 0.25 or digit_ratio(right) > 0.25:
        reasons.append("digit_heavy")
    if has_scripture_reference(left) or has_scripture_reference(right):
        reasons.append("scripture_reference")
    if has_source_tag(left) or has_source_tag(right):
        reasons.append("source_tag")
    if left.casefold() == right.casefold():
        reasons.append("identical_sides")

    normalized_pair = (left.casefold(), right.casefold())
    if normalized_pair in seen_pairs:
        reasons.append("duplicate_exact")

    return reasons


def repair_malformed_row(raw_columns: list[str]) -> tuple[str, str] | None:
    cleaned_columns = [normalize_text(column) for column in raw_columns if normalize_text(column)]
    if len(cleaned_columns) < 2:
        return None
    right = cleaned_columns[-1]
    left = normalize_text(", ".join(cleaned_columns[:-1]))
    if not left or not right:
        return None
    return left, right


def choose_split(row_id: str, train_ratio: float, valid_ratio: float) -> str:
    bucket = zlib.crc32(row_id.encode("utf-8")) / float(2**32)
    if bucket < train_ratio:
        return "train"
    if bucket < train_ratio + valid_ratio:
        return "valid"
    return "test"


def build_instruction_examples(row: dict[str, object]) -> list[dict[str, object]]:
    cherokee = str(row["cherokee"])
    english = str(row["english"])
    source_id = str(row["id"])

    return [
        {
            "id": f"{source_id}-chr_to_en",
            "source_id": source_id,
            "direction": "chr_to_en",
            "messages": [
                {
                    "role": "system",
                    "content": "You translate Cherokee into natural English while preserving meaning.",
                },
                {
                    "role": "user",
                    "content": f"Translate this Cherokee text to English:\n{cherokee}",
                },
                {
                    "role": "assistant",
                    "content": english,
                },
            ],
        },
        {
            "id": f"{source_id}-en_to_chr",
            "source_id": source_id,
            "direction": "en_to_chr",
            "messages": [
                {
                    "role": "system",
                    "content": "You translate English into Cherokee syllabary while preserving meaning.",
                },
                {
                    "role": "user",
                    "content": f"Translate this English text to Cherokee syllabary:\n{english}",
                },
                {
                    "role": "assistant",
                    "content": cherokee,
                },
            ],
        },
    ]


def safe_stem(path: Path) -> str:
    return path.stem


def write_json(path: Path, payload: dict) -> None:
    path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )


def write_jsonl(path: Path, rows: list[dict[str, object]]) -> None:
    path.write_text("", encoding="utf-8")
    with path.open("w", encoding="utf-8") as handle:
        for row in rows:
            handle.write(json.dumps(row, ensure_ascii=False) + "\n")


def main() -> None:
    args = parse_args()
    input_path = Path(args.input)
    base = safe_stem(input_path)
    report_path = input_path.with_name(f"{base}.report.json")
    cleaned_path = input_path.with_name(f"{base}.cleaned.jsonl")
    preview_path = input_path.with_name(f"{base}.cleaned.preview.jsonl")
    bad_rows_path = input_path.with_name(f"{base}.bad-rows.csv")
    repaired_rows_path = input_path.with_name(f"{base}.repaired-rows.csv")
    strict_path = input_path.with_name(f"{base}.strict-training.jsonl")
    strict_train_path = input_path.with_name(f"{base}.strict-training.train.jsonl")
    strict_valid_path = input_path.with_name(f"{base}.strict-training.valid.jsonl")
    strict_test_path = input_path.with_name(f"{base}.strict-training.test.jsonl")
    everyday_path = input_path.with_name(f"{base}.everyday-training.jsonl")
    everyday_train_path = input_path.with_name(f"{base}.everyday-training.train.jsonl")
    everyday_valid_path = input_path.with_name(f"{base}.everyday-training.valid.jsonl")
    everyday_test_path = input_path.with_name(f"{base}.everyday-training.test.jsonl")
    instructions_path = input_path.with_name(f"{base}.strict-training.instructions.jsonl")
    instructions_train_path = input_path.with_name(
        f"{base}.strict-training.instructions.train.jsonl"
    )
    instructions_valid_path = input_path.with_name(
        f"{base}.strict-training.instructions.valid.jsonl"
    )
    instructions_test_path = input_path.with_name(
        f"{base}.strict-training.instructions.test.jsonl"
    )
    everyday_instructions_path = input_path.with_name(
        f"{base}.everyday-training.instructions.jsonl"
    )
    everyday_instructions_train_path = input_path.with_name(
        f"{base}.everyday-training.instructions.train.jsonl"
    )
    everyday_instructions_valid_path = input_path.with_name(
        f"{base}.everyday-training.instructions.valid.jsonl"
    )
    everyday_instructions_test_path = input_path.with_name(
        f"{base}.everyday-training.instructions.test.jsonl"
    )
    domain_report_path = input_path.with_name(f"{base}.domain-summary.json")
    buckets_dir = input_path.with_name(f"{base}.buckets")

    raw_rows = 0
    malformed_rows_total = 0
    malformed_rows_unrepaired: list[dict[str, object]] = []
    repaired_rows: list[dict[str, object]] = []
    accepted_rows: list[dict[str, object]] = []
    rejected_rows: list[dict[str, object]] = []
    strict_training_rows: list[dict[str, object]] = []
    everyday_training_rows: list[dict[str, object]] = []
    strict_split_rows: dict[str, list[dict[str, object]]] = {
        "train": [],
        "valid": [],
        "test": [],
    }
    everyday_split_rows: dict[str, list[dict[str, object]]] = {
        "train": [],
        "valid": [],
        "test": [],
    }
    instruction_rows: list[dict[str, object]] = []
    everyday_instruction_rows: list[dict[str, object]] = []
    instruction_split_rows: dict[str, list[dict[str, object]]] = {
        "train": [],
        "valid": [],
        "test": [],
    }
    everyday_instruction_split_rows: dict[str, list[dict[str, object]]] = {
        "train": [],
        "valid": [],
        "test": [],
    }
    seen_pairs: set[tuple[str, str]] = set()
    reason_counts: Counter[str] = Counter()
    source_tag_counts: Counter[str] = Counter()
    domain_counts: Counter[str] = Counter()
    strict_exclusion_counts: Counter[str] = Counter()
    everyday_exclusion_counts: Counter[str] = Counter()
    bucket_rows: dict[str, list[dict[str, object]]] = {
        "general_parallel": [],
        "story_narrative": [],
        "news": [],
        "reference": [],
        "scripture": [],
    }
    left_lengths: list[int] = []
    right_lengths: list[int] = []

    with input_path.open("r", encoding="utf-8", newline="") as handle:
        header = handle.readline().rstrip("\n")
        reader = csv.reader(handle, skipinitialspace=True)
        for line_number, row in enumerate(reader, start=2):
            raw_rows += 1
            repair_applied = False
            raw_columns: list[str] | None = None

            if len(row) != 2:
                malformed_rows_total += 1
                repaired = repair_malformed_row(row)
                if repaired is None:
                    malformed_rows_unrepaired.append(
                        {
                            "line_number": line_number,
                            "status": "malformed_unrepaired",
                            "reasons": ["malformed_csv"],
                            "raw_columns": row,
                        }
                    )
                    reason_counts["malformed_csv"] += 1
                    continue
                left, right = repaired
                repair_applied = True
                raw_columns = row
            else:
                left = normalize_text(row[0])
                right = normalize_text(row[1])

            left_lengths.append(len(left))
            right_lengths.append(len(right))

            reasons = classify_row(left, right, seen_pairs)
            for tag in extract_source_tags(left):
                source_tag_counts[tag] += 1
            for tag in extract_source_tags(right):
                source_tag_counts[tag] += 1

            row_record = {
                "id": f"{base}-{len(accepted_rows) + len(rejected_rows) + 1:06d}",
                "source_line": line_number,
                "cherokee": left,
                "english": right,
                "repair_applied": repair_applied,
            }

            if reasons:
                rejected_rows.append(
                    {
                        **row_record,
                        "status": "rejected",
                        "reasons": reasons,
                    }
                )
                reason_counts.update(reasons)
                if repair_applied:
                    repaired_rows.append(
                        {
                            "line_number": line_number,
                            "status": "rejected_after_repair",
                            "reasons": reasons,
                            "repaired_cherokee": left,
                            "repaired_english": right,
                            "raw_columns": raw_columns or [],
                        }
                    )
                continue

            domain = classify_domain(left, right)
            training_reasons = training_exclusion_reasons(left, right, domain)
            accepted_row = {
                **row_record,
                "domain": domain,
                "training_exclusion_reasons": training_reasons,
                "status": "accepted",
            }
            accepted_rows.append(accepted_row)
            seen_pairs.add((left.casefold(), right.casefold()))
            domain_counts[domain] += 1
            bucket_rows[domain].append(accepted_row)
            strict_exclusion_counts.update(training_reasons)

            if repair_applied:
                repaired_rows.append(
                    {
                        "line_number": line_number,
                        "status": "accepted_after_repair",
                        "reasons": [],
                        "repaired_cherokee": left,
                        "repaired_english": right,
                        "domain": domain,
                        "training_exclusion_reasons": training_reasons,
                        "raw_columns": raw_columns or [],
                    }
                )

            if not training_reasons:
                strict_training_rows.append(accepted_row)

    for row in strict_training_rows:
        split = choose_split(str(row["id"]), args.train_ratio, args.valid_ratio)
        strict_split_rows[split].append(row)
        examples = build_instruction_examples(row)
        instruction_rows.extend(examples)
        instruction_split_rows[split].extend(examples)
        row_everyday_reasons = everyday_exclusion_reasons(row)
        everyday_exclusion_counts.update(row_everyday_reasons)
        if not row_everyday_reasons:
            everyday_row = {
                **row,
                "everyday_exclusion_reasons": [],
            }
            everyday_training_rows.append(everyday_row)
            everyday_split_rows[split].append(everyday_row)
            everyday_examples = build_instruction_examples(everyday_row)
            everyday_instruction_rows.extend(everyday_examples)
            everyday_instruction_split_rows[split].extend(everyday_examples)

    header_count = None
    header_match = re.search(r"(\d+)", header)
    if header_match:
        header_count = int(header_match.group(1))

    buckets_dir.mkdir(exist_ok=True)

    write_jsonl(cleaned_path, accepted_rows)
    write_jsonl(strict_path, strict_training_rows)
    write_jsonl(everyday_path, everyday_training_rows)
    write_jsonl(preview_path, accepted_rows[: args.preview_size])
    write_jsonl(strict_train_path, strict_split_rows["train"])
    write_jsonl(strict_valid_path, strict_split_rows["valid"])
    write_jsonl(strict_test_path, strict_split_rows["test"])
    write_jsonl(everyday_train_path, everyday_split_rows["train"])
    write_jsonl(everyday_valid_path, everyday_split_rows["valid"])
    write_jsonl(everyday_test_path, everyday_split_rows["test"])
    write_jsonl(instructions_path, instruction_rows)
    write_jsonl(instructions_train_path, instruction_split_rows["train"])
    write_jsonl(instructions_valid_path, instruction_split_rows["valid"])
    write_jsonl(instructions_test_path, instruction_split_rows["test"])
    write_jsonl(everyday_instructions_path, everyday_instruction_rows)
    write_jsonl(everyday_instructions_train_path, everyday_instruction_split_rows["train"])
    write_jsonl(everyday_instructions_valid_path, everyday_instruction_split_rows["valid"])
    write_jsonl(everyday_instructions_test_path, everyday_instruction_split_rows["test"])

    for name, rows in bucket_rows.items():
        write_jsonl(buckets_dir / f"{name}.jsonl", rows)

    with bad_rows_path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(["line_number", "status", "reasons", "left_or_columns", "right"])
        for row in malformed_rows_unrepaired:
            writer.writerow(
                [
                    row["line_number"],
                    row["status"],
                    "|".join(row["reasons"]),
                    " || ".join(row["raw_columns"]),  # type: ignore[arg-type]
                    "",
                ]
            )
        for row in rejected_rows:
            writer.writerow(
                [
                    row["source_line"],
                    row["status"],
                    "|".join(row["reasons"]),
                    row["cherokee"],
                    row["english"],
                ]
            )

    with repaired_rows_path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(
            [
                "line_number",
                "status",
                "reasons",
                "domain",
                "training_exclusion_reasons",
                "repaired_cherokee",
                "repaired_english",
                "raw_columns",
            ]
        )
        for row in repaired_rows:
            writer.writerow(
                [
                    row["line_number"],
                    row["status"],
                    "|".join(row["reasons"]),
                    row.get("domain", ""),
                    "|".join(row.get("training_exclusion_reasons", [])),
                    row["repaired_cherokee"],
                    row["repaired_english"],
                    " || ".join(row["raw_columns"]),
                ]
            )

    report = {
        "input_file": str(input_path),
        "header": header,
        "header_declared_count": header_count,
        "raw_rows_seen": raw_rows,
        "parsed_two_column_rows": len(accepted_rows) + len(rejected_rows),
        "malformed_rows_total": malformed_rows_total,
        "malformed_rows_unrepaired": len(malformed_rows_unrepaired),
        "malformed_rows_repaired": len(repaired_rows),
        "repaired_rows_accepted": sum(
            1 for row in repaired_rows if row["status"] == "accepted_after_repair"
        ),
        "repaired_rows_rejected": sum(
            1 for row in repaired_rows if row["status"] == "rejected_after_repair"
        ),
        "accepted_rows": len(accepted_rows),
        "rejected_rows": len(rejected_rows),
        "preview_rows_written": min(args.preview_size, len(accepted_rows)),
        "output_files": {
            "report": str(report_path),
            "cleaned_jsonl": str(cleaned_path),
            "strict_training_jsonl": str(strict_path),
            "strict_training_train_jsonl": str(strict_train_path),
            "strict_training_valid_jsonl": str(strict_valid_path),
            "strict_training_test_jsonl": str(strict_test_path),
            "everyday_training_jsonl": str(everyday_path),
            "everyday_training_train_jsonl": str(everyday_train_path),
            "everyday_training_valid_jsonl": str(everyday_valid_path),
            "everyday_training_test_jsonl": str(everyday_test_path),
            "instruction_jsonl": str(instructions_path),
            "instruction_train_jsonl": str(instructions_train_path),
            "instruction_valid_jsonl": str(instructions_valid_path),
            "instruction_test_jsonl": str(instructions_test_path),
            "everyday_instruction_jsonl": str(everyday_instructions_path),
            "everyday_instruction_train_jsonl": str(everyday_instructions_train_path),
            "everyday_instruction_valid_jsonl": str(everyday_instructions_valid_path),
            "everyday_instruction_test_jsonl": str(everyday_instructions_test_path),
            "cleaned_preview_jsonl": str(preview_path),
            "bad_rows_csv": str(bad_rows_path),
            "repaired_rows_csv": str(repaired_rows_path),
            "domain_summary_json": str(domain_report_path),
            "buckets_dir": str(buckets_dir),
        },
        "split_ratio": {
            "train": args.train_ratio,
            "valid": args.valid_ratio,
            "test": round(1.0 - args.train_ratio - args.valid_ratio, 4),
        },
        "length_stats": {
            "left_mean": round(statistics.mean(left_lengths), 2) if left_lengths else 0,
            "right_mean": round(statistics.mean(right_lengths), 2) if right_lengths else 0,
            "left_median": statistics.median(left_lengths) if left_lengths else 0,
            "right_median": statistics.median(right_lengths) if right_lengths else 0,
        },
        "reason_counts": dict(reason_counts.most_common()),
        "source_tag_counts": dict(source_tag_counts.most_common(25)),
        "domain_counts": dict(domain_counts.most_common()),
        "strict_training_rows": len(strict_training_rows),
        "everyday_training_rows": len(everyday_training_rows),
        "strict_split_counts": {
            split: len(rows) for split, rows in strict_split_rows.items()
        },
        "everyday_split_counts": {
            split: len(rows) for split, rows in everyday_split_rows.items()
        },
        "instruction_rows": len(instruction_rows),
        "everyday_instruction_rows": len(everyday_instruction_rows),
        "instruction_split_counts": {
            split: len(rows) for split, rows in instruction_split_rows.items()
        },
        "everyday_instruction_split_counts": {
            split: len(rows) for split, rows in everyday_instruction_split_rows.items()
        },
        "strict_exclusion_counts": dict(strict_exclusion_counts.most_common()),
        "everyday_exclusion_counts": dict(everyday_exclusion_counts.most_common()),
        "accepted_samples": accepted_rows[:5],
        "rejected_samples": rejected_rows[:5],
        "malformed_samples": malformed_rows_unrepaired[:5],
        "repaired_samples": repaired_rows[:5],
    }
    write_json(report_path, report)

    domain_summary = {
        "bucket_counts": dict(domain_counts.most_common()),
        "strict_training_rows": len(strict_training_rows),
        "strict_training_ratio": round(
            len(strict_training_rows) / len(accepted_rows), 4
        )
        if accepted_rows
        else 0,
        "everyday_training_rows": len(everyday_training_rows),
        "everyday_training_ratio": round(
            len(everyday_training_rows) / len(strict_training_rows), 4
        )
        if strict_training_rows
        else 0,
        "strict_split_counts": {
            split: len(rows) for split, rows in strict_split_rows.items()
        },
        "everyday_split_counts": {
            split: len(rows) for split, rows in everyday_split_rows.items()
        },
        "instruction_rows": len(instruction_rows),
        "everyday_instruction_rows": len(everyday_instruction_rows),
        "instruction_split_counts": {
            split: len(rows) for split, rows in instruction_split_rows.items()
        },
        "everyday_instruction_split_counts": {
            split: len(rows) for split, rows in everyday_instruction_split_rows.items()
        },
        "bucket_files": {
            name: str((buckets_dir / f"{name}.jsonl").relative_to(input_path.parent))
            for name in bucket_rows
        },
        "strict_exclusion_counts": dict(strict_exclusion_counts.most_common()),
        "everyday_exclusion_counts": dict(everyday_exclusion_counts.most_common()),
        "samples": {name: rows[:3] for name, rows in bucket_rows.items() if rows},
    }
    write_json(domain_report_path, domain_summary)


if __name__ == "__main__":
    main()
