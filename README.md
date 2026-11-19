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

## Identification keys

All in `MSSTC4PHP\ValueObject\Gs1\Key\`, string value objects. The check digit (or the GMN check characters) is verified;
`fromParts($companyPrefix, $reference)` builds a key and computes it. The GS1 Company Prefix is never split off (its
length is a registry fact); `gs1Prefix()` reads the GS1 Prefix range instead.

| Class | AI | Notes |
|---|---|---|
| `Gtin` | 01 | 8, 12, 13 or 14 digits; keeps the form given, `equals()` compares the 14-digit form; `format()`, `toGtin14()`, `asGtin13/12/8()` |
| `Gln` | 410-417 | 13 digits |
| `Sscc` | 00 | 18 digits; `extensionDigit()` |
| `Grai` | 8003 | `0` + 13 digits + optional serial; `assetType()`, `serial()` |
| `Giai` | 8004 | company prefix + asset reference, no check digit |
| `Gsrn` | 8017, 8018 | 18 digits |
| `Gdti` | 253 | 13 digits + optional serial; `documentType()`, `serial()` |
| `Gcn` | 255 | 13 digits + optional numeric serial; `serial()` |
| `Ginc` | 401 | company prefix + consignment reference, no check digit |
| `Gsin` | 402 | 17 digits |
| `Cpid` | 8010 | company prefix + part reference in CSET 39 |
| `Gmn` | 8013 | company prefix + model reference + two check characters (the GS1 Basic UDI-DI) |

`Gs1Prefix` decodes restricted circulation (`02`, `04`, `20`-`29`), coupons, ISBN, ISSN, demonstration, refund
receipts and the unused ranges from the General Specifications tables; every other assigned prefix is
`MemberOrganisation` (the allocation per Member Organisation is not in the specifications), and a well-formed
unassigned one is `Unknown`, never refused.

## GS1 Application Identifier table

The AI table (data formats, FNC1 rule, linters, mandatory and invalid AI pairs) is generated from the
[GS1 Barcode Syntax Dictionary](https://github.com/gs1/gs1-syntax-dictionary), release 2026-01-27, which is vendored
unmodified under `resources/` (Apache License 2.0, see [`NOTICE`](NOTICE)). A new dictionary release is picked up
with `php tools/generate-ai-table.php` and ships as a minor release. Regenerating needs a development checkout with
the dev dependencies installed: the generator lives in `tests/Support/` and the script in `tools/`, both
export-ignored (`.gitattributes`), so the Composer package ships neither.

## License

MIT, see [`LICENSE`](LICENSE). The GS1 Barcode Syntax Dictionary is Apache-2.0, see [`NOTICE`](NOTICE).
