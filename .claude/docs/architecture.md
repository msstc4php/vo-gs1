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

Helpers throw `\LogicException` (message without input) on a precondition violation: the value objects check the
format first.
