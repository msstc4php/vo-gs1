# Conventions

- Exception messages name the type and the length or reason, never the input; no `excerpt()`.
- Tool configuration is byte-identical to the bundle-standard `php84` templates.
- php-cs-fixer rewrites `@link` to `@see`: cite sources with `@see <url> <document, section>`.
- Tests: `tests/Unit` (what Infection runs); generator code lives in `tests/Support` (autoload-dev), the `tools/` script
  only calls it, as in vo-base's ISO enum generator.
- Generated `AiTable.php` must already be cs-fixer and Rector clean: after changing the generator template, run
  `php tools/generate-ai-table.php && make fix` and check `AiTableUpToDateTest` still passes (the fixer must not touch
  the file). `spec()` carries `@infection-ignore-all`: its data is guarded byte for byte by that test, while mutants of
  a match arm would escape it (Infection mutates a copy, the test reads the file on disk).
- Check-digit tests use the source's own examples (GenSpecs Table 7-9 and keys quoted in it, Figure 7-15 for the GMN,
  HIBCC Appendix B `+A123BJC5D6E71` → `G`) plus the GS1 linter test vectors.
- Element-string messages: `Element string AI (NN) …` — the AI number of a known AI is fine; an unknown AI is never
  echoed ("contains an unknown AI"), nor is any data.
- A new linter in a dictionary release fails `AiLintersTest` until it is implemented in `AiLinters` or added to
  `NOT_ENFORCED` **and** to the linter table in `docs/sources/GS1.md` (rows `| \`name\` | enforced |` /
  `| \`name\` | not enforced | reason |`; the test matches that shape).
- Digital Link / EPC messages: `GS1 Digital Link …` / `EPC …`; a wrapped element string failure appends the
  (input-free) element string message and keeps it as `previous`.
- Value objects whose identity is not the string (Digital Link) override `equals()` with a serialized "identity" of
  the parsed parts; keep the stored value as given when the standard defines no normal form.
- Tests that run code of another `src/` class need `#[UsesClass]` for it: `beStrictAboutCoverageMetadata` only bites
  with coverage on, i.e. in Infection, not in plain `make test`.
- UDI messages: `HIBC UDI …`, `ISBT 128 UDI …`, `GS1 UDI …`, `NDC …`, `<Entity> Basic UDI-DI …`; wrapped element
  string / GMN failures append the inner message and keep it as `previous`.
- Typed parts without `@var`: accessors re-run the class's literal regex (`preg_match(self::PATTERN, $this->value)`),
  so PHPStan infers `numeric-string` / `non-empty-string` groups; the unreachable "lost its parts" `LogicException`
  is the accepted cost.
- Marking messages: `Russian marking code …`, `Russian marking identifier …`, `FMD pack code (GS1|IFA) …`, `PPN …`,
  `Tobacco unit level UI …`; AI numbers and DI names are fine, data never. All throw `InvalidMarkingCodeException`.
- Marking canonical forms carry control characters (GS; RS/EOT for the IFA format): tests assert round trips and the
  GS count, and the Doctrine bridge must store them byte for byte.
