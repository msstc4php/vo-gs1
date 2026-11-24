# Changelog

## [1.1.0] - 2026-11-24

### Added

- `UdiFactory::tryFromString()` and `BasicUdiDiFactory::tryFromString()`: null where `fromString()` throws a value
  refusal (vo-base `InvalidValueObjectException`, including `AmbiguousValueException`).

## [1.0.0] - 2026-11-23

First stable release: from here on a breaking change needs 2.0.

**Backward compatibility (full text in the README, "Backward compatibility"):** 1.x covers public classes, methods,
parameter names and return types; the canonical (stored, `__toString()`, JSON, `__serialize()`) form of every value
object; enum backed values and case names; the public `MAX_LENGTH` bounds and the bridge's type names and column
lengths; exception classes and their parents. A minor never refuses a value an earlier 1.x release accepted (a check
that gets a source later is reported by `hasValid…()`, refusing waits for 2.0); a GS1 AI table edition keeps an AI
removed or tightened upstream accepted as legacy; a minor may accept more only within `MAX_LENGTH`; factory candidate
sets are frozen per major; enum cases may be added (keep a default arm); `IdentificationKey`, `Udi` and `BasicUdiDi`
are implemented by this package only and may gain methods in minors. Not covered: exception message wording,
`Internal\` and `@internal`.

### Added

- `MarkingIdentifier::fromTobaccoConsumerPack()`.
- `Udi::lot()`, `serial()`, `expiryDate()`, `productionDate()` (every UDI class already had them).
- `Exception\UnresolvableDateException` (final, extends `\DomainException`).
- A public `MAX_LENGTH` on every value object: the longest stored value, frozen for 1.x.
- `ElementString::AI_TABLE_RELEASE` (the installed GS1 Barcode Syntax Dictionary release) and
  `DigitalLink::CANONICAL_DOMAIN`.
- README sections "Backward compatibility" and the frozen stored forms of the Russian code and identifier.

### Changed

- `IdentificationKey` extends `StringConvertible` and `ValueObject`: `DigitalLink::primaryKey()` can be cast, compared
  and JSON-encoded.
- `MarkingIdentifier::from()` throws `AmbiguousValueException` for 25 pack characters that also read as `01` + GTIN +
  `21` + 7, instead of reading them as the pack; such a pack is stored as `]d1` + 25 characters (as `MarkingCode`
  does), also by `MarkingCode::identifier()`.
- `FmdPackCode::MAX_LENGTH` is 514 (was 512) and bounds the stored form: an IFA message of up to 512 characters
  given without its trailer, which 0.x accepted, is stored with RS EOT appended and now reads back (0.x refused its
  own 513/514-character values). Input is capped at 14178 characters before parsing.
- `IsbtUdi::MAX_LENGTH` is 3123 (was 3116): 3116 characters of data structures plus the `=+aabbb` header, so the
  output of `toCompoundMessage()` always reads back (0.x refused it above 3109 characters of data structures).
- `IsbtUdi` keeps the compound message header (`=+aabbb`) in its value, so a specified sequence round-trips;
  `equals()` compares the data structures, so both forms of one UDI stay equal.
- The date accessors of `ElementString`, `Gs1Udi` and `FmdPackCode` throw `UnresolvableDateException` (was
  `InvalidElementStringException`) when the chosen century lacks the day.
- `ElementString::get()`/`has()` throw `\InvalidArgumentException` for an argument that is no AI of the table (was
  null/false).
- `Gtin::asGtin13()` is null for a GTIN-8.
- Exception messages carry no part of the input: no length, count, segment lengths, AI number, data identifier, data
  structure, date format or EPC encoding name read from it, and no bound or check name of the AI, data identifier,
  data structure or EPC encoding the input selected (they name the kind of rule broken).
- The six exception families are `final`; `UdiFactory` and `BasicUdiDiFactory` have a private constructor;
  `UnmetRequirement`'s constructor is `@internal`.
- Requires `msstc4php/vo-base ^1.4`.

### Upgrading from 0.x

- `HibcUdi::manufactureDate()` is now `productionDate()`.
- `BasicUdiDi::issuingEntity()` is now `issuingAgency()` (on `BasicUdiDi` and the four Basic UDI-DI classes).
- Catch `UnresolvableDateException` instead of `InvalidElementStringException` around `expiryDate()`/`productionDate()`.
- Code that called `ElementString::get()`/`has()` with something that is no AI (to probe) must check first.
- Subclasses of the exception families are no longer possible; catch the family instead.
- Implementations of `IdentificationKey`, `Udi` or `BasicUdiDi` outside this package are outside the BC promise; the
  interfaces gained parents and methods.
- Stored `MarkingIdentifier` values: a 0.x value that is 25 pack characters which also read as `01…21…` + 7 no longer
  loads through `from()` (it throws `AmbiguousValueException`). Serialized PHP objects and vo-gs1-doctrine 1.0
  columns still load it as the pack (to the `]d1` form); other stores read such values with
  `MarkingIdentifier::fromTobaccoConsumerPack()` or prepend `]d1` to them.
- `MarkingIdentifier::tryFrom()` returns null and `isValid()` false for such a 25-character value (they follow
  `from()`); filter with `fromTobaccoConsumerPack()` where the value is known to be a pack.
- The string and JSON of such a pack identifier, from `fromTobaccoConsumerPack()` and from `MarkingCode::identifier()`,
  now start with `]d1` (28 characters). Use `toDocumentForm()` for the operator's documents and for comparisons with
  25-character strings.
- Stored `FmdPackCode` values of 513 or 514 characters (an IFA message of 511 or 512 characters given without its
  trailer) failed to read back in 0.x; they now load. The bridge column stays `VARCHAR(514)`.
- `new UdiFactory()` and `new BasicUdiDiFactory()` fail: call their static methods.
- `IsbtUdi::equals()` no longer implies string equality: the compound message and the human-readable form of one UDI
  are equal but have different strings. Deduplicate (`array_unique`, unique keys) by `equals()`, not by `(string)`.
- Stored `IsbtUdi` values (the human-readable form) read back unchanged; new compound-message input is now stored
  with its header.
- Tests that pin exception messages: the messages changed (they are not covered by the BC promise).

## [0.1.1] - 2026-11-23

### Changed

- Requires `msstc4php/vo-base ^1.3` (was `^1.2`). Under 1.2 a forged serialized string restored a value object
  without validating it, and the state derived from it (the parsed elements of `ElementString`, for instance) as
  stored. vo-base 1.3 serializes the value alone and runs `parse()` again on `unserialize()`; payloads written under
  1.2 still load. No change in this package's code.

## [0.1.0] - 2026-11-20

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
