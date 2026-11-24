# Domain model

Every class is a string value object (`AbstractStringValueObject`); the canonical string is the stored value, so
bridges need no codec. Rules come from published sources only (quotes in `docs/sources/GS1.md`); where no source
gives a rule the class checks the format (README, "Format only").

| Family | Canonical value | Invariants |
|---|---|---|
| Keys (`Gtin`, `Gln`, `Sscc`, `Gsrn`, `Gsin`, `Grai`, `Gdti`, `Gcn`) | digits as given | GS1 check digit; `Gtin` keeps the form given, `equals()` compares the 14-digit form |
| Keys without check digit (`Giai`, `Ginc`, `Cpid`) | as given | length, character set, first 4 digits company prefix |
| `Gmn` | as given | two MOD 1021,32 check characters |
| `ElementString` | bracketed form, `(` in data as `\(`, AI order as given | AI table; `ex` pairs refused; `req` pairs reported by `unmetRequirements()`, `iban`/`mediatype` linters by `hasValidLinterData()`; clock-free date validity |
| `DigitalLink` | the URI as given; `equals()` compares parsed identity | uncompressed; path key plus ordered qualifiers; data attributes in query |
| `EpcPureIdentityUri`, `EpcTagUri` | URI with upper-case escape hex | TDS grammar, company prefix 6..12 digits, field limits |
| `Gs1Udi` | bracketed element string | has AI (01) |
| `HibcUdi` | primary + secondary concatenated | Mod 43 check character; link character |
| `IsbtUdi` | as given: compound message with its header, or the human-readable concatenation; `equals()` compares the structures | data-structure counts and order |
| `BasicUdiDi\*` | as given | the issuing entity's check characters; an ambiguous value throws `AmbiguousValueException` |
| `Ndc`, `Ndc12` | hyphenated | configuration; bare digits are ambiguous for `Ndc`; `candidatesFromBillingFormat()` lists the 5-4-2 readings |
| `RU\MarkingCode` | pack as is, or GS1 barcode data with GS | one of five structures; lost GS refused where detectable |
| `RU\MarkingIdentifier` | 25 pack characters (`]d1` + 25 when they also read as `01…21…`) or bracketed `(01)…(21)…` | КИ without the crypto tail |
| `EU\FmdPackCode`, `Ppn` | GS1 barcode data / IFA message with trailer; PPN | IFA mod 97, PZN check |
| `EU\TobaccoUnitIdentifier(WithoutTimeStamp)` | as given | length, alphanumeric, UTC time stamp (full form only) |

Two canonical styles coexist by ruling: `MarkingIdentifier` is bracketed (a raw 25-character КИ is ambiguous), while
`MarkingCode` and `FmdPackCode` keep the raw form with GS/RS/EOT.

Decoders that never refuse: `Gs1Prefix` (GenSpecs classification only; no Member Organisation names, see
known-issues) and `ElementString\AidcMediaType` (Table 3-12, `01`-`10`; `ElementString::aidcMediaType()`, null-safe:
enum `tryFrom(null)` is a TypeError under strict types).
