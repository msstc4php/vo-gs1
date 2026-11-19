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

## Element strings

`MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString` is a GS1 element string, checked against the AI table: data
format and character set, the GS1 linters (check digits, dates, country and currency codes, …) and invalid AI pairs
(`ex`). Mandatory AI pairs (`req`) are reported, not refused (see below). An AI may repeat only with the same data.
The data may be at most 7089 characters, the capacity of the largest GS1 QR Code.

```php
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;

$scanned = ElementString::from("]d201095060001343521721122510ABC\x1D21XYZ");
(string) $scanned;           // (01)09506000134352(17)211225(10)ABC(21)XYZ
$scanned->gtin();            // Gtin 09506000134352
$scanned->expiryDate();      // 2021-12-25 00:00:00 UTC
$scanned->toBarcodeData();   // "01095060001343521721122510ABC\x1D21XYZ"

$built = (new ElementStringBuilder())->with('01', '09506000134352')->with('21', 'A(B')->build();
(string) $built;             // (01)09506000134352(21)A\(B
```

- **Input:** bracketed, with a literal `(` in data written `\(`; or unbracketed with FNC1 as GS (U+001D) or `^`,
  optionally after a symbology identifier `]C1`, `]e0`, `]d2`, `]Q3`, `]J1` or one leading FNC1.
- **Canonical form:** the bracketed form, `(` in data escaped as `\(`. Notations of the same data are equal.
- **AI order is kept as given.** The same AIs in another order give another canonical string, so they are not
  `equals()`. Normalise the order yourself if you compare element strings from different sources.
- **Accessors:** `get($ai)`, `has($ai)`, `ais()` (each AI once, in order), `unmetRequirements()`, `gtin()` (AI 01),
  `sscc()` (AI 00), `batch()` (10), `serial()` (21), `expiryDate()` (17), `productionDate()` (11), `toBarcodeData()`
  (GS after every AI not of predefined length, except the last).
- **Stored values and dictionary updates:** the accessors re-read the stored canonical value against the AI table of
  the installed version. A table update ships as a minor release; if it removes an AI or tightens its format, a value
  stored under the older table can throw `InvalidElementStringException` from an accessor (and from `from()`).
- **Dates:** the century follows GS1's sliding window around the UTC year of `$today` (an argument, default now, so a
  stored value keeps its meaning when you pass the day it was read); `DD = 00` is the last day of the month; the result
  is 00:00:00 UTC. An impossible month or day is refused where the AI's linter says so (`yymmd0`, `yymmdd`); that
  check never depends on the clock (29 February passes for every `YY` divisible by four, `00` included).
- **Lost separators:** unbracketed data is never split at a guessed position. An AI that is not of predefined length
  runs to the next GS, so a string whose separators were lost is refused when the swallowed data no longer fits.
- **Mandatory pairs** are not refused: GS1 evaluates them "over the combined data received from all GS1 carriers
  marking a physical item", so `(10)ABC` alone (its GTIN in another barcode) is a valid element string.
  `unmetRequirements()` lists each `req` attribute the string does not meet (`UnmetRequirement`: `ai`, `alternatives`,
  `describe()` → `(01) or (02) or …`); check it on the combined data of an item.
- **Format only** (the AI is checked for length and character set, its linter is not run): `mediatype`, `packagetype`
  (code lists that grow), `iban` (per-country structure: later wave), `couponcode`, `couponposoffer` (coupons are out
  of scope). The registry part of `gcppos1`/`gcppos2` (is this a real GS1 Company Prefix) is never checked. See
  [`docs/sources/GS1.md`](docs/sources/GS1.md), "Linters".

## GS1 Digital Link

`MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink` is an uncompressed GS1 Digital Link URI (URI Syntax 1.7.0) on any
http or https domain, built on vo-base `Network\Url`.

```php
use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;

$link = DigitalLink::from('https://example.com/01/09520123456788/10/ABC1?17=201225&linkType=gs1:pip');
(string) $link->toElementString();   // (01)09520123456788(10)ABC1(17)201225
$link->primaryKey();                  // Gtin 09520123456788
(string) $link->toCanonical();        // https://id.gs1.org/01/09520123456788/10/ABC1?17=201225

DigitalLink::fromElementString(ElementString::from('(01)09520123456788(17)201225(10)ABC1'));
// https://id.gs1.org/01/09520123456788/10/ABC1?17=201225
```

- **Path:** the primary key is the last `/AI/value` pair whose AI is a primary key; anything before it is the
  domain's own path. Key qualifiers must follow the dictionary's order (`/01/…/22/…/10/…/21/…`), a trailing slash is
  tolerated. Values are percent-decoded; in the query `+` is a space.
- **Query:** all-numeric keys are AIs and must be GS1 Digital Link data attributes. Refused: an AI that fits the
  path, an all-numeric key that is no AI, an AI without `=value`, an AI that is already in the path. A repeated data
  attribute is allowed and its last value counts, as section 3 says. `&` and `;` separate parameters. Every other
  parameter (`linkType`, `context`, your own) is kept as given, in order, and does not enter the element string.
- **Value and equality:** the value is the URI exactly as given; the standard defines no normal form short of the
  canonical URI, which drops the domain and the extra parameters. `equals()` treats as one link: scheme and host in
  any case (userinfo case-sensitive, RFC 3986), the same stem path, the same path data (a zero-suppressed GTIN equals
  its 14-digit form, `%41` equals `A`, `/gtin/` equals `/01/`), the same effective data attributes in any order, the
  same non-GS1 parameters, raw and in order (`x=%41` is not `x=A`), and the same fragment. `toCanonical()` gives
  section 4.12's canonical URI: `https://id.gs1.org`, 14-digit GTIN, attributes in lexical order of the AI, nothing
  else.
- **`toElementString()`** lists the path AIs, then the query AIs in query order (a repeated attribute at its last
  place). `primaryKey()` returns the key class of the path AI (an ITIP, AI 8006, gives its GTIN).
- **`fromElementString($elementString, $domain = 'https://id.gs1.org')`** needs a primary key AI: the first one is
  the path key, with the longest qualifier sequence the data carries (on a tie, one whose leftover AIs can all be data
  attributes); every other AI must be a data attribute and goes to the query in the order given. Every element string
  of a link this class accepts converts back. The domain may carry a path stem but no query or fragment.
- **Refused:** compressed links (`/ARHKVAdpQgTSoTF0`, `/eh…`, `/ex…`): URI Syntax 1.7.0 no longer defines
  compression, the legacy algorithm is "not approved for use in open supply chain standards" and its successor is a
  separate standard.
- **Accepted for old links** (section 4.1: existing infrastructure "SHOULD continue to support legacy expressions"):
  a GTIN-8, -12 or -13 without its leading zeros in AI (01), (02) or (03), and the deprecated convenience alphas of the
  path (`/gtin/…/lot/…/ser/…`), read as their AIs. The value stays as given; `toCanonical()` writes the AIs.
- At most 14178 characters (twice the element string data cap).

## EPC URIs

`MSSTC4PHP\ValueObject\Gs1\Epc\` covers the URI forms of the EPC Tag Data Standard 2.3. Binary and hex tag encodings
and the EPC Raw and Class URIs are out of scope.

```php
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcPureIdentityUri;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcScheme;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagEncoding;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagUri;

$epc = EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.32a%2Fb');
$epc->scheme();                       // EpcScheme::Sgtin
(string) $epc->toElementString();     // (01)09521141123454(21)32a/b
EpcPureIdentityUri::fromElementString(ElementString::from('(01)09521141123454(21)32a/b'), EpcScheme::Sgtin, 7);

$tag = EpcTagUri::from('urn:epc:tag:sgtin-96:3.9521141.012345.4711');
$tag->filter();                       // 3
$tag->partition();                    // 5
(string) $tag->toPureIdentityUri();   // urn:epc:id:sgtin:9521141.012345.4711
EpcTagUri::fromPureIdentityUri($epc, EpcTagEncoding::Sgtin198, 3);
```

- **`EpcPureIdentityUri`:** `urn:epc:id:<scheme>:…` for all twenty schemes (`EpcScheme`: sgtin, sscc, sgln, grai,
  giai, gsrn, gsrnp, gdti, cpi, sgcn, ginc, gsin, itip, upui, pgln, gid, usdod, adi, bic, imovn), each checked against
  its TDS grammar. In the schemes with a GS1 key the company prefix component has 6 to 12 digits (TDS 7.1: a 4- or
  5-digit prefix is written as 6 digits) and the data must give a valid element string (`toElementString()`; null for
  gid, usdod, adi, bic, imovn). `fromElementString()` needs the company prefix length, a registry fact: 6 for a 4- or
  5-digit prefix, 8 for a GTIN-8, 12 for an individually assigned key; AIs the scheme does not use are ignored.
- **Value and equality:** the URI with the hex digits of percent escapes in upper case, the one normalisation TDS
  section 6.1 names; `A` and `%41` stay different EPCs.
- **`EpcTagUri`:** `urn:epc:tag:[att=xNN][umi=B][xpc=xNNNN]:<encoding>:<filter>.…` for the 22 encodings of
  `EpcTagEncoding` (sgtin-96, sgtin-198, sscc-96, …, gid-96, usdod-96, adi-var; the TDS 2.0 `+`/`++` encodings define
  no tag URI). Control fields are optional and in that order. The filter is any value of its field, reserved or not:
  0-7, 0-63 for adi-var, none for gid-96. The company prefix length gives the partition (`partition()`, 0-6). Each
  encoding's field limits apply (for example `sgtin-96`: a serial without leading zeros below 2^38; `giai-96`: digits
  and bits by partition).
- **Format only:** the BIC check digit (ISO 6346) and the IMO number check digit are not in TDS; the usdod-96 filter
  is defined by the US DoD guide (any number up to 18 digits); TDS 7.3.3/7.3.4 (no SGTIN from RCN-8 or company
  internal numbering) is not enforced.

## Medical devices: UDI, Basic UDI-DI, NDC

`MSSTC4PHP\ValueObject\Gs1\Udi\` holds one class per issuing agency, all implementing `Udi` (`deviceIdentifier()`,
`issuingAgency(): IssuingAgency`), the EU Basic UDI-DI of each issuing entity, and the US National Drug Code.
Exceptions are `InvalidUdiException`; ambiguity is vo-base `AmbiguousValueException`.

```php
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\BasicUdiDiFactory;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use MSSTC4PHP\ValueObject\Gs1\Udi\UdiFactory;

UdiFactory::fromString('(01)09506000134352(17)201225(10)ABC123');   // Gs1Udi
UdiFactory::fromString('+A123BJC5D6E71/$$52001510X3C');              // HibcUdi: lot 10X3, expiry 2020-01-15
UdiFactory::fromString('=+04000=/A9997XYZ100T0479=A99991712345600=,000012=>019031');   // IsbtUdi
HibcUdi::fromSymbols('+A123BJC5D6E71G', '+$$52001510X3GD');          // the same as the concatenated form

BasicUdiDiFactory::fromString('++A999MODELIDENTIFIER11S8');           // HibccBasicUdiDi

Ndc::from('10010-001-01')->toBillingFormat();                        // "10010000101"
Ndc::from('10010-001-01')->toNdc12();                                // Ndc12 010010-0001-01
```

- **`UdiFactory::fromString()`** picks the agency by the first character: `+` HIBC, `=` ISBT 128, `(`, `]`, a digit,
  GS or `^` GS1. The sets do not overlap; anything else is refused, never guessed. The agency class checks the rest.
- **`Gs1Udi`:** an `ElementString` (every notation it accepts) that must carry AI (01). `deviceIdentifier()` is the
  14-digit GTIN; `gtin()`, `lot()` (10), `serial()` (21), `expiryDate($today)` (17), `productionDate($today)` (11),
  `elementString()`.
- **`HibcUdi`** (ANSI/HIBC 2.6): the primary data alone (`+A123BJC5D6E71G`) or primary and secondary data
  concatenated in one symbol, with supplemental data (`/S`, `/16D`, `/14D`, `/Q`); the Mod 43 check character is
  verified. A secondary symbol alone is not a UDI: pass both symbols to `fromSymbols()`, which checks the link
  character and stores the concatenated form, so both labels of an item give equal values. `deviceIdentifier()` is the
  primary data structure with its check character; `labelerIdentificationCode()`, `productNumber()`,
  `unitOfMeasure()`, `lot()`, `serial()` (secondary data, else `S`), `expiryDate()` (secondary data, else `14D`),
  `expiryDateFormat()` (`HibcDateFormat`), `manufactureDate()` (`16D`), `quantity()` (`Q`). Every format of Appendix F
  is read, including the legacy `$`, `$+` and five-digit Julian forms. **Not in the standard, chosen here:** two-digit
  years are 20YY (no clock dependence), an MMYY expiry is the last day of the month, dates are UTC with the hour where
  the format has one. The check character may be a space or `/`; the value keeps it.
- **`IsbtUdi`** (ICCBBA ST-017): the Compound Message `=+aabbb…` or the human-readable concatenation of the data
  structures; the value is the latter (`toCompoundMessage()` rebuilds the former with an unspecified sequence). The
  UDI starts with the Processor Product Identification Code and carries the Donation Identification Number and the
  Product Divisions; expiration date, production date and MPHO lot number are optional; each at most once. The count
  `aa` is checked, and the order for the specified sequences 034-037. Non-UDI data structures after the production
  identifiers are kept unparsed. `deviceIdentifier()` (16 characters), `facilityIdentificationNumber()`,
  `facilityProductCode()`, `productDescriptionCode()`, `donationIdentificationNumber()` (13 characters, flags
  ignored), `serial()`, `expiryDate()`, `productionDate()` (year = 2000 + `cyy`, as ST-017's example), `lot()`.
- **Basic UDI-DI** (`Udi\BasicUdiDi\`), every class with its entity's check characters: `Gs1BasicUdiDi` (the GMN,
  MOD 1021,32; `gmn()`), `HibccBasicUdiDi` (`++` + LIC + model identifier, MOD 1021,32), `IccbbaBasicUdiDi` (UDI-DI +
  ISO 7064 MOD 37-2), `IfaBasicUdiDi` (`PP` + manufacturer code + device group code, modulo 97).
  `BasicUdiDiFactory::fromString()` tries all four: no match throws `InvalidUdiException`, several (for example
  `PP1234500000007005`, valid for ICCBBA and IFA) throw `AmbiguousValueException` listing the classes.
- **`Ndc`** (21 CFR 207.33(b)): the hyphenated form in any of the five configurations of `NdcConfiguration` (4-4-2,
  5-3-2, 5-4-1, 6-3-2, 6-4-1). Bare digits are ambiguous ("there is no reliable way to know where the hyphens were",
  FDA): `Ndc::from('9999912345')` throws `AmbiguousValueException` whose `candidates()` is `[Ndc::class]` (the
  configurations are not classes); recover them with `Ndc::candidates('9999912345')` (4-4-2, 5-3-2, 5-4-1) and build
  the right one with `Ndc::fromDigits($digits, $configuration)` once you know it. `labelerCode()`, `productCode()`,
  `packageCode()`, `configuration()`, `toBillingFormat()` (HIPAA 11-digit 5-4-2, unhyphenated; null for a 6-digit
  labeler code, for which HIPAA defines no conversion), `toNdc12()`.
- **`Ndc12`:** the 6-4-2 format of the FDA final rule of 2026-03-05 (effective 2033-03-07), hyphenated or 12 bare
  digits (one configuration), stored hyphenated.
- **Format only:** ISBT 128 specified compound sequences other than 034-037 (their order is in ICCBBA's RT017, not
  public); non-UDI ISBT 128 data structures; the alternatively formatted NDC of 207.33(b)(4) is not accepted (no
  published format).
- Size limits before parsing: HIBC 106 characters, ISBT 128 2048 characters and 99 data structures, NDC 13.

## GS1 Application Identifier table

The AI table (data formats, FNC1 rule, linters, mandatory and invalid AI pairs) is generated from the
[GS1 Barcode Syntax Dictionary](https://github.com/gs1/gs1-syntax-dictionary), release 2026-01-27, which is vendored
unmodified under `resources/` (Apache License 2.0, see [`NOTICE`](NOTICE)). A new dictionary release is picked up
with `php tools/generate-ai-table.php` and ships as a minor release. Regenerating needs a development checkout with
the dev dependencies installed: the generator lives in `tests/Support/` and the script in `tools/`, both
export-ignored (`.gitattributes`), so the Composer package ships neither.

## License

MIT, see [`LICENSE`](LICENSE). The GS1 Barcode Syntax Dictionary is Apache-2.0, see [`NOTICE`](NOTICE).
