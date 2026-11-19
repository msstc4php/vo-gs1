# Integrations

- `msstc4php/vo-base ^1.2`: `InvalidValueObjectException`, `AmbiguousValueException`, `Contract\Maskable`,
  `AbstractStringValueObject`. Locally through the `../vo-base` path repository as `dev-main as 1.2.0`; CI needs the
  `v1.2.0` tag.
- `vo-gs1-doctrine`: one ORM and one ODM type per value object, `gs1_<snake_name>`.
- GS1 Barcode Syntax Dictionary: vendored unmodified at `resources/gs1-syntax-dictionary.txt` (release 2026-01-27,
  commit `8ad003f`), Apache-2.0 licence next to it, attribution in `NOTICE`. The generator refuses another release
  until `AiTableGenerator::RELEASE`/`COMMIT` are updated, and throws on any flag, attribute or component syntax it does
  not know.
- vo-base `I18n\CountryCode` (`fromNumeric`, `tryFrom` alpha-2) and `I18n\CurrencyCode::fromNumeric` back the
  `iso3166*`/`iso4217` linters; the ISO 3166 lists match the GS1 linter tables exactly (249 codes, checked 2026-11-19).
- GS1 Barcode Syntax Engine (<https://github.com/gs1/gs1-syntax-engine>, commit `58c1dc8`): source of the `\(`
  escape, the `^` notation, the splitting rules and the repeated-AI rule. Not a dependency, quoted in GS1.md only.
