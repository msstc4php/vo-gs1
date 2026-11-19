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

## Digital Link and EPC (Task 6)

- URI Syntax 1.7.0 has no compression; RE2 also matches plain-number segments, so "is compressed" is only the message
  for a no-key path whose last segment has a letter, `_` or `-` (or starts `eh`/`ex`).
- The parser follows GS1's engine `dl.c` where the standard is silent: backward scan for the last primary-key pair,
  unknown AI in the scan stops it (`/01/X/foo/bar` has no key), `/414/…/01/…` makes `/414/…` the stem, a query AI that would fit the path is refused.
- Controller rulings (fix round 1), where they depart from the engine: a repeated query attribute is valid, last value
  wins (section 3; engine: `DUPLICATE_AI`); a query AI repeating a path AI stays refused; convenience alphas in the
  path are accepted via the engine's `alpha_ai_map` (engine default: off); short GTINs are padded for (01), (02), (03),
  not for (8006)/(8026) (GTIN + piece/total); a bare numeric query key (`?17`) is refused.
  `+` is a space only in the query (`/10/A+B` is `A+B`).
- AiTable `spec('10')`-style lookups are fine, but `array_keys()` of an AI-keyed array turns `'10'` into int `10`;
  `array_intersect()` compares string forms, so `arrange()` still works.
- `equals()` of Digital Link compares scheme and host lower-cased (userinfo case-sensitive, RFC 3986; the host is
  what follows the last `@` of the authority), stem path as given, decoded path data (`/gtin/` = `/01/`), effective
  attributes sorted by AI, fragment (`#` vs none differ). Extension parameters are compared raw (`?x=%41` ≠ `?x=A`):
  a deliberate "kept as given" choice, their meaning is not GS1's.
- `arrange()` (element string → link) ranks qualifier sequences by length, then by whether every leftover AI is a
  data attribute: `(01)(10)(235)` gives `/01/…/235/…?10=…`, so every link the class accepts converts back.
- TDS 12.4 grammar omits `GSRNPTagURIBody` (only `GSRNPEncName`); `gsrnp-96` is accepted per Table 12-2.
- TDS 7.1 ("CompanyPrefix … SHALL include the 4- or 5-digit GCP and the following 2 or 1 digits") makes 6..12 digits
  the pure-URI rule, so the tag partition check never sees another length; `partition()` = 12 − L.
- CPI-96 reference: within 3+P digits the value is always below 2^(11..31) bits, so only the digit limit is checked;
  GIAI-96 needs both (2^42 < 10^13).
- SGLN extension `0` means "no extension": `(254)0` → EPC `…0` → element string without (254) (asymmetric, by TDS).
- AI (8011) `nozeroprefix` accepts the single digit `0` (GenSpecs 26.0, 3.9.11), unlike `lint_nozeroprefix.c`, whose
  test vector refuses `"0"`; so `urn:epc:id:cpi:….0` and a cpi-96 tag with serial 0 are valid.
- `EpcTagUri` filter messages: when the body without a filter is already a whole EPC of the scheme, the message is
  "has no filter value"; otherwise leading zero / more than 18 digits / above the field width.
- EPC parse validates GS1 schemes by building the element string, so GenSpecs limits (serial ≤ 20, piece ≤ total,
  CSET 82) apply to EPC URIs too.
- Infection: ~11 escaped mutants in Task 6 code are equivalent (regex capture positions of unused components,
  `(int)` of the whole filter match, sort keys of control fields, stem-path slice offsets used only in identity).
