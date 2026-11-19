# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

`msstc4php/vo-gs1`: value objects for GS1 identification keys, element strings, GS1 Digital Link and EPC URIs,
medical-device UDI and NDC, and Russian and EU marking codes. PHP >= 8.4. Namespace `MSSTC4PHP\ValueObject\Gs1\`.
Framework-agnostic; Doctrine storage is the separate `vo-gs1-doctrine` bridge. Requirements and the plan live in
`../vo-base/docs/` (brainstorm `2026-11-19-identifiers-and-codes-requirements.md`, plan
`2026-11-19-001-feat-gs1-wave-plan.md`); source notes in `docs/sources/`.

## Common commands

Local install against the sibling `../vo-base`: `COMPOSER=composer-local.json composer update
--ignore-platform-req=php`. In CI the install is `composer-ci.json`.

`composer-local.json` is gitignored. Create it from `composer-ci.json`: copy the file and replace the VCS repository
with `{"type": "path", "url": "../vo-base", "options": {"symlink": true}}`, and require `msstc4php/vo-base` as
`dev-main as 1.2.0`.

- `COMPOSER=composer-local.json make check`, `make test`, `make infection`, `make fix`.
- `php tools/generate-ai-table.php` regenerates `src/Internal/AiTable.php` from `resources/gs1-syntax-dictionary.txt`.

`composer-ci.lock` (written by `make install-ci`) is not ignored, because `.gitignore` is a byte-identical template;
delete it after a CI-profile install and never commit it.

## Standard

Tool configuration is byte-identical to the `php84` templates of `msstc4symfony/bundle-standard`; CI's `templates` job
enforces it. Do not edit `.php-cs-fixer.dist.php`, `rector.php`, `phpstan*.neon`, `phpunit.xml.dist`,
`infection.json5`, `Makefile` or `.gitignore` here. The hex layout of that standard does not apply.

Exception messages never contain the input or any part of it; never call `InvalidValueObjectException::excerpt()`.
