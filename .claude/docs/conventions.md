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
- Tests that run code of another `src/` class need `#[UsesClass]` for it: `beStrictAboutCoverageMetadata` only bites
  with coverage on, i.e. in Infection, not in plain `make test`.
