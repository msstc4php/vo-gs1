# Changelog

## [Unreleased]

### Added

- Package scaffold, CI and tool configuration from `vo-documents`.
- Exception families `InvalidIdentificationKeyException`, `InvalidElementStringException`,
  `InvalidDigitalLinkException`, `InvalidEpcUriException`, `InvalidUdiException`, `InvalidMarkingCodeException`.
- Internal check-digit helpers (GS1 standard check digit, GMN check character pair, HIBC Mod 43) and the GS1
  Application Identifier table generated from the GS1 Barcode Syntax Dictionary release 2026-01-27.
- Source notes `docs/sources/GS1.md`.
- GS1 identification keys `Gtin`, `Gln`, `Sscc`, `Grai`, `Giai`, `Gsrn`, `Gdti`, `Gcn`, `Ginc`, `Gsin`, `Cpid`, `Gmn`,
  and `Gs1Prefix`, `GtinFormat`, `IdentificationKey`.
- `ElementString\ElementString` (GS1 element strings: bracketed, unbracketed and scanner notations, linters, `req`/`ex`
  association rules, GS1 date century window) and the immutable `ElementString\ElementStringBuilder`; unmet
  mandatory AI pairs are reported by `unmetRequirements()` (`ElementString\UnmetRequirement`), not refused.
- `DigitalLink\DigitalLink`: uncompressed GS1 Digital Link URIs (URI Syntax 1.7.0) on any domain, both ways with
  element strings, `primaryKey()`, `toCanonical()`; non-GS1 query parameters are kept; compressed links are refused.
- `Epc\EpcPureIdentityUri` (all twenty schemes of EPC Tag Data Standard 2.3, `EpcScheme`, element string both ways),
  `Epc\EpcTagUri` (tag URIs of the 22 encodings of `EpcTagEncoding`, with filter, partition and field limits).
- Medical devices (`Udi\`): `Gs1Udi`, `HibcUdi` (ANSI/HIBC 2.6 primary, secondary and supplemental data, Mod 43,
  `fromSymbols()`), `IsbtUdi` (ICCBBA ST-017 compound messages), `UdiFactory`, `IssuingAgency`, `HibcDateFormat`;
  Basic UDI-DI per issuing entity (`Gs1BasicUdiDi`, `HibccBasicUdiDi`, `IccbbaBasicUdiDi`, `IfaBasicUdiDi`,
  `BasicUdiDiFactory`); US NDC `Ndc` (all five 21 CFR 207.33 configurations, `candidates()`, `fromDigits()`,
  `toBillingFormat()`, `toNdc12()`), `NdcConfiguration` and `Ndc12` (6-4-2, final rule of 2026-03-05).
- Marking codes: `Marking\RU\MarkingCode` (every structure the Russian decrees and «Честный знак» publish:
  tobacco consumer pack, tobacco group pack, standard, shortened; `structure()`, `identifier()`; codes with lost GS
  separators are refused), `Marking\RU\MarkingIdentifier` (КИ, `toDocumentForm()`), `Marking\RU\MarkingCodeStructure`;
  `Marking\EU\FmdPackCode` (Delegated Regulation (EU) 2016/161 unique identifier, GS1 and IFA format 06),
  `Marking\EU\Ppn` (IFA check digits, PZN, `toNtin()`), `Marking\EU\TobaccoUnitIdentifier` (Implementing Regulation
  (EU) 2018/574, UTC time stamp).
