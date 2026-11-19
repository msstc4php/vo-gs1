# Architecture

deptrac forbids every framework namespace (Doctrine, Symfony, Illuminate, yii, CodeIgniter, Cycle, Spiral), as in the
core.

| Area | Purpose |
|---|---|
| `Exception\` | One family per area, all extending vo-base `InvalidValueObjectException`: identification key, element string, Digital Link, EPC URI, UDI, marking code. Ambiguity uses vo-base `AmbiguousValueException` |
| `Internal\Gs1CheckDigit` | GS1 standard check digit (weights 3/1 from the right); `compute(body)`, `isValid(key)` |
| `Internal\GmnCheckCharacters` | MOD 1021,32 pair over CSET 82, primes from the right; body at most 23 characters |
| `Internal\Mod43` | HIBC LIC check character (Table B1 of ANSI/HIBC 2.6); may return a space |
| `Internal\AiTable` | **Generated** (`tools/generate-ai-table.php`): `spec(ai)`, `matchPrefix(data)`, `ais()`, `RELEASE`, `COMMIT` |
| `Internal\AiSpec`, `AiComponent`, `AiCharset` | Hand-written readonly shapes the table returns |
| `ElementString\ElementString` | String VO, canonical = bracketed form (AI order as given); no parsed state, accessors re-parse `$value`; `unmetRequirements()` |
| `ElementString\UnmetRequirement` | Readonly: `ai`, `alternatives` (groups of AIs/patterns), `describe()` |
| `ElementString\ElementStringBuilder` | Immutable `with(ai, value)` → `build()`; renders the bracketed form and parses it |
| `Internal\ElementStringSyntax` | Notations (bracketed / GS / `^` / symbology id), per-AI data check (components, charset, linters), repeats, `ex` (refused), `req` (reported via `unmetRequirements()`), size caps |
| `Internal\AiLinters` | GS1 linters: `IMPLEMENTED`, `NOT_ENFORCED` (always pass), `passes(name, data)` throws `LogicException` on an unknown name; `isCset64()` |
| `Internal\Gs1Dates` | GenSpecs 7.12 century window, `DD = 00`, `toDate()` at 00:00 UTC |
| `Internal\AiElement` | One AI + unescaped data |
| `DigitalLink\DigitalLink` | String VO, value = URI as given; `equals()` overridden (data identity, see known-issues); `toElementString()`, `primaryKey()`, `toCanonical()`, `url()`, `fromElementString($es, $domain)` |
| `Internal\DigitalLinkSyntax` | Parse (engine's backward scan for the primary key, `dlpkey` ordered subsets, query rules), `render()`, `arrange()` (ES → path/query), `keyFor()` (AI → key class) |
| `Internal\DigitalLinkParts` | Readonly: lower-case scheme/authority, stem path, path elements, attributes, raw extensions, fragment |
| `Epc\EpcScheme`, `Epc\EpcTagEncoding` | Backed enums of the 20 pure identity schemes and 22 tag encodings; `hasElementString()`, `filterBits()`, `isPartitioned()` |
| `Epc\EpcPureIdentityUri`, `Epc\EpcTagUri` | String VOs, value = URI with upper-case escape hex; tag parse re-validates the embedded pure URI |
| `Internal\EpcSyntax` | Per-scheme grammar (one regex each), CP 6..12, ES mapping both ways, tag parse, control fields, field limits per encoding |

Helpers throw `\LogicException` (message without input) on a precondition violation: the value objects check the
format first.
