# Changelog

## [Unreleased]

First release content (0.1.0).

### Added

- GS1 identification keys `Key\Gtin` (keeps the form given, equality on the 14-digit form, `GtinFormat`), `Gln`,
  `Sscc`, `Grai`, `Giai`, `Gsrn`, `Gdti`, `Gcn`, `Ginc`, `Gsin`, `Cpid`, `Gmn`, with `fromParts()`; `Gs1Prefix`
  decodes the GS1 Prefix and never refuses.
- `ElementString\ElementString`: GS1 element strings in the bracketed, unbracketed (GS or `^`) and scanner notations,
  checked against the AI table (data formats, linters, invalid pairs, repeats, GS1 date century window); mandatory AI
  pairs are reported by `unmetRequirements()` (`UnmetRequirement`), not refused. Immutable `ElementStringBuilder`.
- The AI table, generated from the GS1 Barcode Syntax Dictionary release 2026-01-27 (vendored, Apache-2.0, `NOTICE`).
- `DigitalLink\DigitalLink`: uncompressed GS1 Digital Link URIs (URI Syntax 1.7.0) on any domain, both ways with
  element strings, `primaryKey()`, `toCanonical()`; non-GS1 query parameters are kept; compressed links are refused.
- `Epc\EpcPureIdentityUri` (20 schemes of EPC Tag Data Standard 2.3, `EpcScheme`) and `Epc\EpcTagUri` (22 encodings,
  `EpcTagEncoding`, with filter, partition and field limits); element string both ways.
- Medical devices (`Udi\`): `Gs1Udi`, `HibcUdi` (ANSI/HIBC 2.6, `fromSymbols()`), `IsbtUdi` (ICCBBA ST-017),
  `UdiFactory`, `IssuingAgency`, `HibcDateFormat`; Basic UDI-DI per issuing entity (`Gs1BasicUdiDi`,
  `HibccBasicUdiDi`, `IccbbaBasicUdiDi`, `IfaBasicUdiDi`, `BasicUdiDiFactory`); US NDC `Ndc` (five 21 CFR 207.33
  configurations, `candidates()`, `fromDigits()`, `toBillingFormat()`, `toNdc12()`), `NdcConfiguration`, `Ndc12`.
- Russian marking (`Marking\RU\`): `MarkingCode` (tobacco consumer and group packs, standard and shortened
  structures, `GenericGs1`; codes with lost GS separators are refused), `MarkingIdentifier` (КИ,
  `fromDocumentForm()`), `MarkingCodeStructure`.
- EU marking (`Marking\EU\`): `FmdPackCode` (GS1 and IFA format 06), `Ppn`, `TobaccoUnitIdentifier` and
  `TobaccoUnitIdentifierWithoutTimeStamp`.
- Exception families `InvalidIdentificationKeyException`, `InvalidElementStringException`,
  `InvalidDigitalLinkException`, `InvalidEpcUriException`, `InvalidUdiException`, `InvalidMarkingCodeException`.
- Source notes with quotes in `docs/sources/GS1.md`; README with class table, "format only" and "not covered" lists.

### Fixed before the first release (final review)

- `Gtin::gs1Prefix()` reads a GTIN-14 over a GTIN-8 with any indicator digit as the GTIN-8; `Gs1Prefix::fromDigits()`
  decodes a zero run shorter than seven digits as `Unknown` (or `Unused` when every completion is unused).
- EPC: the BIC owner code takes every capital letter (`OOLU`, `ONEU`; TDS 6.3.19 prose over its grammar line);
  `usdod-96` filter is 0-15 and its serial below 2^36 (US DoD RFID guide v15); the CAGE/DoDAAC grammar is quoted.
- HIBC: a `/` primary check character links a secondary symbol that carries supplemental data; a `$`/`$+` lot or
  serial may start with `.` or `-`; `supplementalExpiryDate()` and `supplementalQuantity()` expose `/14D` and `/Q`
  beside a secondary date or quantity.
- Digital Link: escaped unreserved characters in AI path segments and query keys are decoded before the AI lookup;
  a `%` without two hex digits and an authority with two `@` are refused; a key qualifier given in the query is read
  into the path; `equals()` normalises percent-escapes; `fromElementString()` names an AI it cannot carry.
- Element strings: the 7089-character single-QR-Code cap is gone; the one bound is 14178 characters for the input and
  for the canonical bracketed form (a package limit, not a GS1 rule).
- ISBT 128: input bound 3116 characters (largest Data Matrix, GenSpecs 5.6.2) instead of an unsourced 2048.
- Russian marking: `MarkingCode::fromTobaccoConsumerPack()` (and `]d1` input) reads a pack that also reads as a GS1
  code with lost separators, stored as `]d1` + pack; the lost-GS check covers only the last group of unbracketed data
  and needs a valid next AI; `MarkingIdentifier` (01)(21)(8005) takes any serial length.
- `FmdPackCode` reads an IFA message after a scanner's `]d1`/`]d2`.
- NDC messages cite 21 CFR 207.33(b)(1)(i) and (ii); stricter PHPStan types on key accessors.
