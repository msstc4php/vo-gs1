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

## Identification keys

- `Cpid` (AI 8010) is CSET 39 (Table 7-21, dictionary `Y..30`), not CSET 82; `Internal\Gs1Charsets` holds both sets.
- `Grai::assetType()` / `Gdti::documentType()` return the key without serial (14 / 13 digits), i.e. the AI value minus
  its serial; the company prefix is never split off.
- `Gs1Prefix` has no Member Organisation names: the allocation list is not in the General Specifications.
- Company prefixes are 4 to 12 digits (GenSpecs 1.2.3.3; 3 for the GS1-8 Prefix of a GTIN-8); `Giai`, `Ginc`, `Cpid`,
  `Gmn` start with 4 digits (dictionary `gcppos1`).
- `Gtin::gs1Prefix()` decodes the 14-digit form, so equal GTINs agree: six leading zeros mean a GTIN-8 (GS1-8 table).
- No key strips spaces or hyphens.
- GenSpecs text has no example for GDTI, GCN, GSIN, GSRN, GINC, CPID; their tests use computed check digits (see
  GS1.md).

## Element strings

- Parse-time date validity is clock-free (owner ruling): `yymmd0`/`yymmdd` check MM/DD with
  `YY % 4 === 0` as leap (so `000229` always parses), unlike `lint_yymmd0.c`, which uses `CURRENT_YEAR`. The century
  is applied only in `Gs1Dates::toDate()`; the accessors throw `UnresolvableDateException` (final, extends
  `\DomainException`, not the invalid-value family; ruling S4) when the chosen century lacks the day (2100-02-29).
- `ElementStringBuilder::with()` refuses a backslash at once: `X\` followed by the next `(` would otherwise be read as
  an escaped `(` and merge two AIs into one valid serial.
- Lost GS separators are only detectable when the swallowed data breaks the AI's length or charset: `10ABC21XYZ`
  (separator lost) is a valid batch `ABC21XYZ`. That is the GS1 engine's behaviour too; never split at max length.
- `req` is **not** refused (owner ruling): GS1 evaluates pairings over all carriers of an item, so
  `(10)ABC` parses and `unmetRequirements()` reports it. `ex` stays refused.
- Association checks run over distinct AIs and look patterns up by expansion (`35nn` → 100 keys), so repeats cannot
  make them quadratic; input is capped at 14178 characters before parsing and the canonical bracketed form at 14178
  after (package bound, ruling CR-017; the former 7089 single-QR-Code data cap refused multi-carrier data and is gone).
- `ElementString` caches its parsed elements in a private nullable property set by `parse()` (S-6; PHPStan refuses a
  readonly property assigned outside the constructor, and the parent's constructor is final). Values are still
  parsed against the installed table when constructed. BC rule (1.0, G1): a dictionary release ships in a minor and
  must not refuse a value an earlier 1.x table accepted; when GS1 removes or tightens an AI, the generator/table must
  keep the old spec as legacy (not implemented yet: no such release so far; design it with the first one). Other composite VOs (DigitalLink, HibcUdi, MarkingCode, …) still re-parse per accessor.
- `iso4217`: vo-base `CurrencyCode` lacks the ISO non-currency codes 955-965, 994, 999 that GS1's linter accepts; they
  are listed in `AiLinters::ISO_4217_NON_CURRENCY`. vo-base also accepts withdrawn codes the linter refuses (lenient).
- GS1 Syntax Engine clone used for quotes: `main` at `58c1dc8` (its newest tags are 2023); cite the commit.
- Infection: ~57 escaped mutants in the new code are equivalent (redundant guards in `Gs1Dates::isDate`, length
  arithmetic in error messages); Covered Code MSI 95%.

## Digital Link and EPC

- URI Syntax 1.7.0 has no compression; RE2 also matches plain-number segments, so "is compressed" is only the message
  for a no-key path whose last segment has a letter, `_` or `-` (or starts `eh`/`ex`).
- The parser follows GS1's engine `dl.c` where the standard is silent: backward scan for the last primary-key pair,
  unknown AI in the scan stops it (`/01/X/foo/bar` has no key), `/414/…/01/…` makes `/414/…` the stem, a query AI that
  would fit the path is refused.
- Rulings, where they depart from the engine: a repeated query attribute is valid, last value
  wins (section 3; engine: `DUPLICATE_AI`); a query AI repeating a path AI stays refused; convenience alphas in the
  path are accepted via the engine's `alpha_ai_map` (engine default: off); short GTINs are padded for (01), (02), (03),
  not for (8006)/(8026) (GTIN + piece/total); a bare numeric query key (`?17`) is refused.
  `+` is a space only in the query (`/10/A+B` is `A+B`).
- AiTable `spec('10')`-style lookups are fine, but `array_keys()` of an AI-keyed array turns `'10'` into int `10`;
  `array_intersect()` compares string forms, so `arrange()` still works.
- `equals()` of Digital Link compares scheme and host lower-cased (userinfo case-sensitive, RFC 3986; an authority
  with two `@` is refused), decoded path data (`/gtin/` = `/01/`), effective attributes sorted by AI, fragment (`#` vs
  none differ). Userinfo, stem path, extension parameters and fragment compare after RFC 3986 6.2.2 escape
  normalisation (`%2f` = `%2F`, `%41` = `A`); port as written, extension order significant (CR-015).
- DL AI lookup decodes escaped unreserved characters first (`?%31%37=` is AI 17, `/%30%31/` is `/01/`); a broken `%`
  is refused. A query AI that fits the path's qualifier sequence moves into the path (ruling CR-014, no SHALL in DL
  1.7.0 against it); the engine's `AI_SHOULD_BE_IN_PATH_INFO` refusal is not followed.
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
  "has no filter value"; otherwise leading zero / above the field width (a filter of more than two digits is refused
  before the int cast). `usdod-96` widths (filter 4, serial 36 bits) come from the US DoD guide v15, which TDS defers to.
- EPC BIC owner code is `[A-Z]{3}`: TDS 6.3.19's grammar excludes I/O but its prose and real owner codes (OOLU) do not.
  CAGE/DoDAAC keeps the grammar's I/O exclusion (no contradicting prose).
- EPC parse validates GS1 schemes by building the element string, so GenSpecs limits (serial ≤ 20, piece ≤ total,
  CSET 82) apply to EPC URIs too.
- `EpcTagUri::filter()`/`partition()` are `int<0, 63>|null` / `int<0, 6>|null` through no-op `max/min` clamps
  (ruling N2); their five mutants (bounds ±1, `$filter[0]`) are equivalent: the values are already in range.
- Infection: ~11 escaped mutants in Digital Link/EPC code are equivalent (regex capture positions of unused components,
  `(int)` of the whole filter match, sort keys of control fields, stem-path slice offsets used only in identity).

## UDI, Basic UDI-DI, NDC

- vo-base `AmbiguousValueException` is **final**: no subclass can carry the NDC configurations. The NDC throws it with
  candidates `[Ndc::class]` and a fixed type string (no configurations, no digit count: they reveal the input length,
  ruling S3/G2); `Ndc::candidates()` is the documented recovery path, then `Ndc::fromDigits()`. The exception is not an `InvalidUdiException` (both are
  `InvalidValueObjectException`).
- `Ndc::toBillingFormat()` is `?string` (not `string`): HIPAA's 5-4-2 has no conversion for 6-digit labeler
  codes (FDA: "outside the scope of FDA authority"), so it returns null for 6-3-2 / 6-4-1.
- HIBC: the check character can be a space or `/`, so always strip the last character before splitting on `/`. With
  supplemental data a separate secondary symbol's link character is accepted before the first `/` or second to last
  (owner ruling); both valid → refused as ambiguous. Two-digit years = 20YY, MMYY = last day of month: package
  decisions, not HIBCC's.
- HIBC `isSecondary()`: a digit or `$` after `+` (Table 2 Note 1); so a primary-looking LIC starting with a digit is
  reported as "secondary data only".
- HIBC Basic UDI-DI check = `GmnCheckCharacters` over the whole string **including `++`** (HIBCC Table 1 = CSET 82,
  Table 2 = CSET 32); max 23 body characters fits the helper exactly.
- ISBT 128: data structures are split at every `=`/`&` (ST-017 chapter 5); non-UDI structures are opaque, so one
  containing `=` would be split and miscount `aa`. DIN flags are accepted per charset (reading software ignores them),
  though 3.1 says medical devices set `00`. Dates: year = 2000 + `cyy` (from the example "019032 refers to 1 Feb 2019").
- ICCBBA MOD 37-2 checksum is two **digits** 00-36 although 3.1's table types `bb` as alphanumeric.
- `PP1234500000007005` is a valid ICCBBA and IFA Basic UDI-DI at once (test fixture for the ambiguity path).
- Infection: 21 escaped mutants in UDI code are equivalent — `substr` length overshoots in HIBC date offsets,
  no-op `max/min` clamps that give PHPStan `int<0, 9>` / `int<0, 99999>`, unreachable `LogicException` guards,
  `ppic` in the required list (always first), `(int)` before string arithmetic, `calendar(…, 2)` before "last day of".
- Rulings: ISBT messages no longer echo `aa`/`bbb` (input); `UdiFactory` strips `]xx` and routes `+`/`=`/`&` on the
  rest (GS1 gets the original); legacy HIBC quantity `$$8`/`$$9` accepted (Appendix H), layout quoted from ANSI/HIBC
  2.3-2009 via an Internet Archive copy (quantity-only `+$$848GQ` has no date flag); `/S` beside a serial secondary
  accepted (only a heading, no prohibition), exposed as `supplementalSerial()`; HIBC cap 113 (serial form with `$$+9`
  quantity + all supplementals); `Gs1Udi::gtin()` guard is a `LogicException`.
- HIBC `/` is a Mod 43 check character: with primary check `/` the separate secondary's link `/` is the first `/` and
  the supplemental `/` follows it (`LOT//S123`). `/14D` and `/Q` beside secondary date/quantity are both exposed
  (`supplementalExpiryDate()`, `supplementalQuantity()`), like `/S`.

## Marking codes

- No operator page prints a real code; tests build codes from the published format lines (GTIN `04600439931256`).
- Rulings: intact-GS codes outside the published structures are `GenericGs1` (AI table only; needs
  (01), (21) first and (92) or (93)). Published lengths serve the pack and lost-GS detection only.
- Lost GS: `ElementStringSyntax` never splits, so (21) swallows the rest up to the next GS. `RuMarkingSyntax::
  PUBLISHED_NEXT` refuses a group of an **unpublished** length that holds, at a published length, the AI a published
  structure puts next (e.g. tobacco group `21` + 7 + `8005…` GS `93…` → a 17-character serial runs into 8005 at 7).
  Ruling: groups of a published length are never checked (20-char random serials contain `93` etc. a few
  percent of the time). Remaining cost: a would-be `GenericGs1` code with such a sequence at such an offset is
  refused. Identifiers apply the same rule, so canonical round trips stay consistent.
- Opposite residual (ruling: no new heuristic): a `GenericGs1` code with an unpublished serial
  length whose GS after (21) was lost can swallow the next AIs into the serial and be accepted as a different code.
  Undetectable without the separators; a "(92) without (91)" refusal was rejected (would refuse valid generic codes).
- `MarkingIdentifier::from()` refuses 25 pack-shaped characters that also read as a valid `01…21…` + 7 with
  `AmbiguousValueException` (ruling B2, 1.0); `fromTobaccoConsumerPack()` (or `]d1` input) reads the pack and stores
  `]d1` + 25, exactly as `MarkingCode` does for 29. `RuMarkingSyntax::packIdentifierCanonical()` decides the marker
  for both, so `MarkingCode::identifier()` never throws. `__unserialize()` maps a 0.x bare-25 payload of such a pack
  to the `]d1` form (G15), and so do the vo-gs1-doctrine `RuMarkingIdentifierType`s on load; `from()` does not (that
  is the point of the ambiguity). Its check-digit failure message
  points to `fromDocumentForm()`. Infection: `gs1Identifier(…, false)` → `true` in `packIdentifierCanonical()` is
  equivalent (25 characters leave a 7-character serial, a published length, so the lost-GS check returns early).
- 29 GS-free characters `01` + GTIN + `21` + 5 + `93` + 4 are pack-shaped: ambiguous if the first 14 digits are also a
  valid GTIN, else refused as lost GS. GTIN `04600439931256` happens to give a valid `01046004399312`; tests use
  `04606203086627` for the non-ambiguous case.
- `MarkingIdentifier::from()` takes only canonical forms (25 pack chars, bracketed); `fromDocumentForm()` handles
  `01…21…` and the ambiguity. `MarkingCode::identifier()` therefore never throws.
- Decree No. 224: current edition (29.12.2025, in force 01.03.2026) read on the КонтурЭкстерн mirror;
  pp. 43-45 unchanged. The CSET 82 pack charset rests on a comment answer on the operator's water page.
- EU tobacco: Art 21(4) allows the time stamp outside the carrier → `TobaccoUnitIdentifierWithoutTimeStamp`; never
  strip trailing digits by guess. `withTimeStamp()` refuses years outside 2000-2099.
- Strings of 255 (`1`×255) or 512 `1`s parse as valid GS1 element strings (repeated AI (11)); length-bound tests
  assert the later message, not a parse failure.
- IFA expiry `DD = 00` → last day of the month (package decision, IFA only says "month and year"); century 2000-2099
  per IFA. Tobacco UI century 2000: package decision.
- `FmdPackCode::MAX_LENGTH` (512) bounds the canonical form, not the input (ruling B3): an IFA message without
  trailer gains RS EOT, so 511 input characters gave a 513-character value that its own `from()` refused. Input is
  only capped at the element-string bound (14178) before parsing. Bridge column: 512.
- `FmdPackCode::pzn()` reads NTIN-DE `04150` + PZN; productCode equality between the formats = `Ppn::toNtin()`
  equals the GTIN (the types differ, so "same product code" is that comparison plus `pzn()`).
- EUR-Lex stayed blank on a retry; publication.pravo.gov.ru and legalacts.ru close the socket.
- Infection: escaped mutants in the marking code are equivalent or unreachable (negative string offset × 0 in the PZN
  loop, `(int)` casts, `substr` length overshoot in the IFA day, unreachable `LogicException`s, the unused
  `identifier` field of a parsed identifier).
- RU lost-GS check (ruling CR-007): only the last group of unbracketed input, and only when the rest after a
  published length parses as the expected next AI with valid data (`startsWithValidAi()`); bracketed input skips it.
  `MarkingIdentifier` (01)(21)(8005) takes any serial length (CR-008).
- RU pack that also reads as `01`+GTIN+`21`+5+`93`+4: `MarkingCode::fromTobaccoConsumerPack()` or `]d1` input picks
  the pack; canonical value is then `]d1` + 29 characters (persisted that way), every other pack stays 29 characters.
