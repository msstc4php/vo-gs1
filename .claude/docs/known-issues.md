# Known issues

- PHPStan generalises a constant array above 256 elements and ignores `@var` on it: a tuple table of the ~540 AIs
  lost `positive-int` and failed level max. The generated table is therefore a `match` of `new AiSpec(...)` per
  dictionary line (ranges as multi-condition arms), so every literal is type-checked.
- PHP turns numeric-string array keys without a leading zero into integers (`'10'` → `10`); the table avoids array
  keys for AIs (match on strings, `AIS` is a list).
- The dictionary's `main` branch carries unreleased changes ("Release: UNSET") and a newer `req` notation
  (`req=A+C,B+C` instead of `req=A,B req=C`); the generator reads both, the table follows tagged releases only.
- GenSpecs 26.0 does not prescribe the 14-digit database form of a GTIN ("may be stored with or without leading
  zeroes"); the plan's fallback applies (keep the given form, compare the 14-digit form).
- EUR-Lex answered 202 with an empty body (bot challenge) on 2026-11-19 UTC; EU texts were read on legislation.gov.uk.

## Identification keys (Task 4)

- `Cpid` (AI 8010) is CSET 39 (Table 7-21, dictionary `Y..30`), not CSET 82; `Internal\Gs1Charsets` holds both sets.
- `Grai::assetType()` / `Gdti::documentType()` return the key without serial (14 / 13 digits), i.e. the AI value minus its serial; the company prefix is never split off.
- `Gs1Prefix` has no Member Organisation names: the allocation list is not in the General Specifications.
- Company prefixes are 4 to 12 digits (GenSpecs 1.2.3.3; 3 for the GS1-8 Prefix of a GTIN-8); `Giai`, `Ginc`, `Cpid`, `Gmn` start with 4 digits (dictionary `gcppos1`).
- `Gtin::gs1Prefix()` decodes the 14-digit form, so equal GTINs agree: six leading zeros mean a GTIN-8 (GS1-8 table).
- No key strips spaces or hyphens.
- GenSpecs text has no example for GDTI, GCN, GSIN, GSRN, GINC, CPID; their tests use computed check digits (see GS1.md).

## Element strings (Task 5)

- Parse-time date validity is clock-free (controller ruling, fix round 1): `yymmd0`/`yymmdd` check MM/DD with
  `YY % 4 === 0` as leap (so `000229` always parses), unlike `lint_yymmd0.c`, which uses `CURRENT_YEAR`. The century
  is applied only in `Gs1Dates::toDate()`; the accessors throw when the chosen century lacks the day (2100-02-29).
- `ElementStringBuilder::with()` refuses a backslash at once: `X\` followed by the next `(` would otherwise be read as
  an escaped `(` and merge two AIs into one valid serial.
- Lost GS separators are only detectable when the swallowed data breaks the AI's length or charset: `10ABC21XYZ`
  (separator lost) is a valid batch `ABC21XYZ`. That is the GS1 engine's behaviour too; never split at max length.
- `req` is **not** refused (controller ruling, fix round 1): GS1 evaluates pairings over all carriers of an item, so
  `(10)ABC` parses and `unmetRequirements()` reports it. `ex` stays refused.
- Association checks run over distinct AIs and look patterns up by expansion (`35nn` → 100 keys), so repeats cannot
  make them quadratic; input is capped at 14178 characters before parsing and data at 7089 (GS1 QR Code capacity).
- `ElementString` keeps no parsed state: the parent's final constructor allows no readonly initialisation, so accessors
  re-parse the canonical value (bounded by the size cap). Consequence: a value stored under an older dictionary can
  throw `InvalidElementStringException` from accessors after a dictionary release (a minor release) removes or
  tightens one of its AIs. Stated in the README.
- `iso4217`: vo-base `CurrencyCode` lacks the ISO non-currency codes 955-965, 994, 999 that GS1's linter accepts; they
  are listed in `AiLinters::ISO_4217_NON_CURRENCY`. vo-base also accepts withdrawn codes the linter refuses (lenient).
- GS1 Syntax Engine clone used for quotes: `main` at `58c1dc8` (its newest tags are 2023); cite the commit.
- Infection: ~57 escaped mutants in the new code are equivalent (redundant guards in `Gs1Dates::isDate`, length
  arithmetic in error messages); Covered Code MSI 95%.
