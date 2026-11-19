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
