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
