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
