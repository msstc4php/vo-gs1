# msstc4php/vo-gs1

Value objects for GS1 identification keys (GTIN, GLN, SSCC and the others), GS1 element strings, GS1 Digital Link
and EPC URIs, medical-device UDI (GS1, HIBCC, ICCBBA) and the US NDC, and Russian and EU marking codes. Built on
[`msstc4php/vo-base`](https://github.com/msstc4php/vo-base); PHP >= 8.4.

Work in progress: the value objects arrive with the 0.1.0 release. Every check digit and structural rule cites its
official source; the notes, with quotes, are in [`docs/sources/GS1.md`](docs/sources/GS1.md).

## Install

Not on Packagist yet; register the GitHub repositories:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-base" },
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-gs1" }
    ],
    "require": {
        "msstc4php/vo-gs1": "^0.1"
    }
}
```

Runtime requirement: `msstc4php/vo-base ^1.2`. No framework.

## GS1 Application Identifier table

The AI table (data formats, FNC1 rule, linters, mandatory and invalid AI pairs) is generated from the
[GS1 Barcode Syntax Dictionary](https://github.com/gs1/gs1-syntax-dictionary), release 2026-01-27, which is vendored
unmodified under `resources/` (Apache License 2.0, see [`NOTICE`](NOTICE)). A new dictionary release is picked up
with `php tools/generate-ai-table.php` and ships as a minor release.

## License

MIT, see [`LICENSE`](LICENSE). The GS1 Barcode Syntax Dictionary is Apache-2.0, see [`NOTICE`](NOTICE).
