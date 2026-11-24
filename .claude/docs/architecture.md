# Architecture

deptrac forbids every framework namespace (Doctrine, Symfony, Illuminate, yii, CodeIgniter, Cycle, Spiral), as in the
core.

| Area | Purpose |
|---|---|
| `Key\IdentificationKey`, `Udi\Udi`, `Udi\BasicUdiDi\BasicUdiDi` | Package-only interfaces (docblock: "Implemented by this package only; methods may be added in minor releases"); all extend `StringConvertible` + `ValueObject` |
| `Exception\` | One family per area, all extending vo-base `InvalidValueObjectException`: identification key, element string, Digital Link, EPC URI, UDI, marking code. Ambiguity uses vo-base `AmbiguousValueException` |
| `Internal\Gs1CheckDigit` | GS1 standard check digit (weights 3/1 from the right); `compute(body)`, `isValid(key)` |
| `Internal\GmnCheckCharacters` | MOD 1021,32 pair over CSET 82, primes from the right; body at most 23 characters |
| `Internal\Mod43` | HIBC LIC check character (Table B1 of ANSI/HIBC 2.6); may return a space |
| `Internal\AiTable` | **Generated** (`tools/generate-ai-table.php`): `spec(ai)`, `matchPrefix(data)`, `ais()`, `RELEASE`, `COMMIT` |
| `Internal\AiSpec`, `AiComponent`, `AiCharset` | Hand-written readonly shapes the table returns |
| `ElementString\ElementString` | String VO, canonical = bracketed form (AI order as given); parsed elements cached in a private property; `unmetRequirements()` |
| `ElementString\UnmetRequirement` | Readonly: `ai`, `alternatives` (groups of AIs/patterns), `describe()`; constructor `@internal` |
| `ElementString\ElementStringBuilder` | Immutable `with(ai, value)` → `build()`; renders the bracketed form and parses it |
| `Internal\ElementStringSyntax` | Notations (bracketed / GS / `^` / symbology id), per-AI data check (components, charset, linters), repeats, `ex` (refused), `req` (reported via `unmetRequirements()`), size caps |
| `Internal\AiLinters` | GS1 linters: `IMPLEMENTED`, `NOT_ENFORCED` (always pass), `passes(name, data)` throws `LogicException` on an unknown name; `isCset64()` |
| `Internal\Gs1Dates` | GenSpecs 7.12 century window, `DD = 00`, `toDate()` at 00:00 UTC |
| `Internal\AiElement` | One AI + unescaped data |
| `DigitalLink\DigitalLink` | String VO, value = URI as given; `equals()` overridden (data identity, see known-issues); `toElementString()`, `primaryKey()`, `toCanonical()`, `url()`, `fromElementString($es, $domain)` |
| `Internal\DigitalLinkSyntax` | Parse (engine's backward scan for the primary key, `dlpkey` ordered subsets, query rules), `render()`, `arrange()` (ES → path/query), `keyFor()` (AI → key class) |
| `Internal\DigitalLinkParts` | Readonly: lower-case scheme and host (userinfo as given), stem path, path elements, attributes, raw extensions, fragment |
| `Epc\EpcScheme`, `Epc\EpcTagEncoding` | Backed enums of the 20 pure identity schemes and 22 tag encodings; `hasElementString()`, `filterBits()`, `isPartitioned()` |
| `Epc\EpcPureIdentityUri`, `Epc\EpcTagUri` | String VOs, value = URI with upper-case escape hex; tag parse re-validates the embedded pure URI |
| `Internal\EpcSyntax` | Per-scheme grammar (one regex each), CP 6..12, ES mapping both ways, tag parse, control fields, field limits per encoding |
| `Udi\Udi`, `Udi\IssuingAgency` | UDI contract (`deviceIdentifier()`, `issuingAgency()`, `lot()`, `serial()`, `expiryDate()`, `productionDate()`); enum GS1, HIBCC, ICCBBA, IFA (IFA only for Basic UDI-DI) |
| `Udi\Gs1Udi` | String VO over `ElementString`, must have AI (01); value = bracketed form |
| `Udi\HibcUdi`, `Internal\HibcSyntax`, `Internal\HibcData` | HIBC LIC primary / concatenated (+ supplemental `/S`, `/16D`, `/14D`, `/Q`); value = concatenated form; `fromSymbols()` joins two symbols via the link character; accessors re-parse |
| `Udi\IsbtUdi`, `Internal\IsbtSyntax`, `Internal\IsbtData` | ISBT 128 (ST-017): compound `=+aabbb` or HRI in, value = as given (header kept, ruling S8); `equals()` on the structures; `toCompoundMessage()` |
| `Udi\UdiFactory` | Strips a leading `]xx`, then routes on the next character: `+` HIBC, `=`/`&` ISBT (both built from the data without the identifier), `(` / digit / GS / `^` or data behind an identifier GS1 (given whole); else `InvalidUdiException` |
| `Udi\BasicUdiDi\*` | `BasicUdiDi` interface (`issuingAgency()`), one class per entity with its check characters; `BasicUdiDiFactory` tries all, 0 → invalid, >1 → `AmbiguousValueException`; `UdiFactory`/`BasicUdiDiFactory::tryFromString()` map any `InvalidValueObjectException` (ambiguity included) to null |
| `Udi\Ndc`, `Udi\NdcConfiguration`, `Udi\Ndc12` | NDC, 10 digits in five configurations (4-4-2, 5-3-2, 5-4-1, 6-3-2, 6-4-1), hyphens required; bare digits → `AmbiguousValueException([Ndc::class])` + `Ndc::candidates()`; `Ndc12` 6-4-2 |
| `Internal\Iso7064Mod37`, `Internal\IfaMod97`, `Internal\UdiDates` | ICCBBA MOD 37-2 (Horner), IFA mod 97 over ASCII, UTC calendar/ordinal dates (null when the day does not exist) |
| `Marking\RU\MarkingCode`, `MarkingIdentifier`, `MarkingCodeStructure` | String VOs over `Internal\RuMarkingSyntax` (+ readonly `RuMarkingParts`); code canonical = pack as is / GS1 barcode data with GS; identifier canonical = 25 pack chars / bracketed `(01)…(21)…[(8005)…]` |
| `Internal\RuMarkingSyntax` | Pack-like (29, 14 digits, no GS/`^`; prefix `]d1`/`]d2`/GS stripped) incl. the 5-char-serial lost-GS ambiguity; GS1: lost-GS rule (`PUBLISHED_NEXT`) first, then published structures by the AI after (21) (91/93/8005), else `GenericGs1`; `identifier()` canonical-only, `documentForm()` with ambiguity |
| `Marking\EU\FmdPackCode`, `Internal\IfaPackData` | GS1 (01/21/10/17 required, canonical barcode data) or IFA `[)>` RS `06` GS … RS EOT (9N/S/1T/D once each, 8P repeatable, other DIs kept) |
| `Marking\EU\Ppn`, `Marking\EU\TobaccoUnitIdentifier`, `TobaccoUnitIdentifierWithoutTimeStamp` | IFA mod 97 + PZN mod 11 (PRA 11), `toNtin()` = `04150` + PZN; tobacco UI ≤ 50 alnum, `YYMMDDhh` UTC; carrier content without time stamp (Art 21(4)) 3-42 alnum |

Helpers throw `\LogicException` (message without input) on a precondition violation: the value objects check the
format first.
