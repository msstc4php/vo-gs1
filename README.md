# msstc4php/vo-gs1

Value objects for GS1 identification keys (GTIN, GLN, SSCC and the others), GS1 element strings, GS1 Digital Link
and EPC URIs, medical-device UDI (GS1, HIBCC, ICCBBA) and the US NDC, and Russian and EU marking codes. Built on
[`msstc4php/vo-base`](https://github.com/msstc4php/vo-base); PHP >= 8.4.

Every check digit and structural rule cites its official source; the notes, with quotes, are in
[`docs/sources/GS1.md`](docs/sources/GS1.md). What a class cannot check from a published source is checked for
format only and listed under "Format only" below.

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

Runtime requirement: `msstc4php/vo-base ^1.3`. No framework.
vo-base 1.3 serializes a value object as its value alone and validates it again on `unserialize()`; the state
derived from it (the element string's parsed elements, for instance) is rebuilt, never restored.

## Usage

```php
use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use MSSTC4PHP\ValueObject\Gs1\Udi\UdiFactory;

$gtin = Gtin::from('4006381333931');             // an EAN-13 is a GTIN-13
$gtin->format()->name;                           // Gtin13
$gtin->equals(Gtin::from('04006381333931'));     // true: equality compares the 14-digit form

$scan = ElementString::from("]d201095060001343521721122510ABC\x1D21XYZ");   // scanner output
(string) $scan;                                  // (01)09506000134352(17)211225(10)ABC(21)XYZ

DigitalLink::from('https://example.com/01/09520123456788/10/ABC1?17=201225')->toCanonical();
// https://id.gs1.org/01/09520123456788/10/ABC1?17=201225

UdiFactory::fromString('(01)09506000134352(17)201225(10)ABC123');   // Gs1Udi
Ndc::from('10010-001-01')->toBillingFormat();    // "10010000101"
```

The sections below give each family in full, with more examples.

## Classes

All value objects are string value objects (`AbstractStringValueObject`): `(string) $vo` and `jsonSerialize()` give the
canonical value, `equals()` compares it (Digital Link overrides this, see its section). A failure is an exception of the
area's family in `MSSTC4PHP\ValueObject\Gs1\Exception\`, all extending vo-base `InvalidValueObjectException`; a value
that fits several classes throws vo-base `AmbiguousValueException`. Exception messages never contain the input.

| Class | Checks | Source |
|---|---|---|
| `Key\Gtin`, `Gln`, `Sscc`, `Gsrn`, `Gsin`, `Grai`, `Gdti`, `Gcn` | length, digits, GS1 check digit | GS1 General Specifications 26.0 |
| `Key\Giai`, `Ginc`, `Cpid` | length and character set (CSET 82; CSET 39 for `Cpid`), company-prefix position | General Specifications, dictionary `gcppos1` |
| `Key\Gmn` | character set, the two check characters (MOD 1021,32) | General Specifications 26.0 |
| `Key\Gs1Prefix`, `GtinFormat` | decoding of the GS1 Prefix and of the GTIN format; never refuses | General Specifications 26.0 (GS1 Prefix and GTIN tables) |
| `ElementString\ElementString` | AI table: data format, character set, linters, invalid pairs (`ex`), repeats, dates; reports `req` | GS1 Barcode Syntax Dictionary, General Specifications 26.0 |
| `ElementString\ElementStringBuilder`, `UnmetRequirement` | immutable builder; one unmet mandatory pair | same |
| `DigitalLink\DigitalLink` | uncompressed URI: path key and qualifier order, query attributes | GS1 Digital Link URI Syntax 1.7.0 |
| `Epc\EpcPureIdentityUri`, `EpcTagUri` (+ `EpcScheme`, `EpcTagEncoding`) | TDS grammar of 20 schemes and 22 tag encodings, field limits, partition | EPC Tag Data Standard 2.3 |
| `Udi\Gs1Udi` | an element string with AI (01) | General Specifications 26.0 (AI 01 as the device identifier) |
| `Udi\HibcUdi` | LIC primary, secondary and supplemental data, Mod 43 | ANSI/HIBC 2.6 |
| `Udi\IsbtUdi` | data structures, counts, sequence 034-037, dates | ICCBBA ST-017 |
| `Udi\UdiFactory`, `IssuingAgency` | picks the agency by the leading characters | the three classes above |
| `Udi\BasicUdiDi\Gs1BasicUdiDi`, `HibccBasicUdiDi`, `IccbbaBasicUdiDi`, `IfaBasicUdiDi`, `BasicUdiDiFactory` | structure and check characters of each issuing entity | GS1, HIBCC, ICCBBA and IFA documents (GS1.md) |
| `Udi\Ndc`, `NdcConfiguration` | hyphenated 10-digit forms (five configurations) | 21 CFR 207.33 |
| `Udi\Ndc12` | the 6-4-2 format | FDA final rule of 2026-03-05 (91 FR, doc 2026-04368) |
| `Marking\RU\MarkingCode`, `MarkingIdentifier`, `MarkingCodeStructure` | the published code structures, lost GS detection, AI table | Government decrees per product group, operator's methodology pages |
| `Marking\EU\FmdPackCode` | GS1 form or IFA format 06 of the unique identifier | Delegated Regulation (EU) 2016/161, IFA |
| `Marking\EU\Ppn` | PRA Code, product number, IFA modulo 97, PZN check | IFA PPN-Code Specification 3.06 |
| `Marking\EU\TobaccoUnitIdentifier`, `TobaccoUnitIdentifierWithoutTimeStamp` | length, alphanumeric, UTC time stamp | Implementing Regulation (EU) 2018/574 |

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
Input and the canonical bracketed form may be at most 14178 characters each: a package bound against oversized input
and for storage (twice the 7089-character capacity of the largest GS1 QR Code, so data of several carriers fits), not
a GS1 rule.

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
- **Format only:** see [Format only](#format-only) (`mediatype`, `packagetype`, `iban`, coupon AIs, the registry half of
  `gcppos1`/`gcppos2`); linter details in [`docs/sources/GS1.md`](docs/sources/GS1.md), "Linters".

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
- **Query:** all-numeric keys are AIs and must be GS1 Digital Link data attributes, except a key qualifier that fits
  the path (even (21), which is no data attribute), which is read into the path. Refused: an all-numeric key that is
  no AI, any other AI that is no data attribute, an AI without `=value`, an AI that is already in the path. A repeated data
  attribute is allowed and its last value counts, as section 3 says. `&` and `;` separate parameters. Every other
  parameter (`linkType`, `context`, your own) is kept as given, in order, and does not enter the element string.
- **Value and equality:** the value is the URI exactly as given; the standard defines no normal form short of the
  canonical URI, which drops the domain and the extra parameters. `equals()` treats as one link: scheme and host in
  any case (userinfo case-sensitive, RFC 3986), the same stem path, the same path data (a zero-suppressed GTIN equals
  its 14-digit form, `%41` equals `A`, `/gtin/` equals `/01/`), the same effective data attributes in any order, the
  same non-GS1 parameters in order and the same fragment, escapes normalised (`%2f` is `%2F`, `%41` is `A`). `toCanonical()` gives
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
- At most 14178 characters, the element string bound: a package limit against oversized input, not a GS1 rule.
- Percent-escapes: a `%` must be followed by two hex digits; escaped unreserved characters in AI path segments and
  query keys are decoded before the AI lookup (`/%30%31/…` is `/01/…`, `?%31%37=…` is AI (17)); the authority may
  hold one `@`. A key qualifier given in the query (`?10=ABC` beside a GTIN path) is read as that qualifier: the
  standard has no SHALL against it.
- `equals()`: escapes compare after RFC 3986 6.2.2 normalisation; the port is compared as written and non-GS1
  parameters keep their order.

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
- **Format only:** see [Format only](#format-only) (BIC and IMO check digits, TDS 7.3.3/7.3.4).

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

- **`UdiFactory::fromString()`** sets aside a leading symbology identifier (`]` + two characters), then picks the
  agency by the next character: `+` HIBC, `=` or `&` ISBT 128 (both built from the data without the identifier);
  data behind an identifier, or starting with `(`, a digit, GS or `^`, is GS1 (passed whole, `ElementString` reads the
  identifier). Anything else is refused, never guessed. The agency class checks the rest.
- **`Gs1Udi`:** an `ElementString` (every notation it accepts) that must carry AI (01). `deviceIdentifier()` is the
  14-digit GTIN; `gtin()`, `lot()` (10), `serial()` (21), `expiryDate($today)` (17), `productionDate($today)` (11),
  `elementString()`.
- **`HibcUdi`** (ANSI/HIBC 2.6): the primary data alone (`+A123BJC5D6E71G`) or primary and secondary data
  concatenated in one symbol, with supplemental data (`/S`, `/16D`, `/14D`, `/Q`); the Mod 43 check character is
  verified. A secondary symbol alone is not a UDI: pass both symbols to `fromSymbols()`, which checks the link
  character and stores the concatenated form, so both labels of an item give equal values. `deviceIdentifier()` is the
  primary data structure with its check character; `labelerIdentificationCode()`, `productNumber()`,
  `unitOfMeasure()`, `lot()`, `serial()` (secondary data, else `S`), `supplementalSerial()` (`S`), `expiryDate()`
  (secondary data, else `14D`), `supplementalExpiryDate()` (`14D`), `expiryDateFormat()` (`HibcDateFormat`),
  `manufactureDate()` (`16D`), `quantity()` (secondary data, else `Q`), `supplementalQuantity()` (`Q`). Every format
  of Appendix F
  is read, including the legacy `$`, `$+` and five-digit Julian forms, and the pre-2.6 quantity forms `$$8`/`$$9`
  (Appendix H: "Existing labels are still valid but should not be used for Unique Device Identification"; layout
  and examples from ANSI/HIBC 2.3-2009, quantity in `quantity()`). `/S` is accepted next to a serial secondary; HIBC does not
  say which serial wins, so `serial()` returns the secondary data's (else `/S`) and `supplementalSerial()` the `/S`
  one. In a separate secondary symbol with supplemental data the link character may stand
  after the lot/serial or before the check character (a `/` link character is followed directly by the supplemental
  `/`); two valid readings are refused as ambiguous. A lot or serial may start with `.` or `-` (section 3.0). **Not in the standard, chosen here:** two-digit
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
- **Format only:** see [Format only](#format-only) (ISBT 128 sequences and non-UDI structures, the alternatively formatted NDC).
- Size limits before parsing: HIBC 113 characters, ISBT 128 3116 characters (the largest Data Matrix,
  GenSpecs 5.6.2) and 99 data structures, NDC 13.

## Marking codes: Russia («Честный знак») and the EU

`MSSTC4PHP\ValueObject\Gs1\Marking\RU\` and `Marking\EU\`; every failure is `InvalidMarkingCodeException`, ambiguity is
vo-base `AmbiguousValueException`. No crypto tail is verified (that needs the operator's keys); its format is.

```php
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\FmdPackCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;

$code = MarkingCode::from("0104600439931256215aB3x_\x1D93Ab1+");   // dairy, shortened structure
$code->structure();                    // MarkingCodeStructure::Shortened
$code->identifier()->toDocumentForm(); // "0104600439931256215aB3x_" (КИ)

FmdPackCode::from("[)>\x1E06\x1D9N111234567842\x1D1T1A234B5\x1DD151231\x1DS1234567890123456\x1E\x04")
    ->productCode()->toNtin();         // Gtin 04150123456782, the product code of the GS1 form
TobaccoUnitIdentifier::from('AB1x9ZqkP3m719052014')->manufacturedAt();   // 2019-05-20 14:00 UTC
```

- **`RU\MarkingCode`** accepts every structure the decrees and the operator publish; `structure()` names it
  (`MarkingCodeStructure`), the product group is not decoded (one structure serves many groups):

  | Structure | Groups | Source |
  |---|---|---|
  | `TobaccoConsumerPack` | 14-digit GTIN + 7 serial + 4 encoded price + 4 check code, no AIs | Decree No. 224, p. 43 |
  | `TobaccoGroupPack` | (01), (21) of 7, (8005) of 6, (93) of 4, then any further groups | No. 224, pp. 44-45 |
  | `Standard` | (01), (21) of 6, 13 or 20, (91) of 4, (92) of 44 or 88; optional (3103) | per group, see GS1.md |
  | `Shortened` | (01), (21) of 6, 7, 13 or 20, (93) of 4; optional (3103) or (335y) | per group, see GS1.md |
  | `GenericGs1` | (01), (21), then (92) or (93) somewhere, intact GS separators; AI-table checks only | none: lengths or groups no page publishes |

  Accessors: `gtin()`, `serial()`, `verificationKey()` (91), `verificationCode()` (92, 93 or the pack's 4th group),
  `identifier()` (never throws), `elementString()` (null for the pack). `verificationCode()` is never null. Input:
  the pack code (optionally after `]d1`, `]d2` or a leading GS) or a GS1 code in any `ElementString` notation.
  **Lost GS separators** (a code copied from a web form): data is never split at a guessed position. The last group
  of unbracketed data, when of an unpublished length, is refused if the rest after one of its published lengths
  parses as the AI a published structure puts next, with valid data (`21` + 6 characters + `93` + 4, `91` + 4 +
  `92…`, …); a code left without (92)/(93) is refused too. Brackets and GS separators prove their boundaries, so
  data before them is never read as a lost separator. 29 GS-free characters that read both as a pack and as such a
  code throw `AmbiguousValueException`; `MarkingCode::fromTobaccoConsumerPack()` (or `]d1` input, which no GS1 code
  carries) reads the pack, and its value keeps the `]d1` prefix so it reads back unambiguously; `]d2` input of such
  characters is GS1 data and is refused as a lost separator. **Residual:** a
  `GenericGs1` code (unpublished serial length) whose GS after (21) was lost can have its next AIs swallowed into the
  serial and is then accepted as a different code; without the separators this cannot be detected. The operator
  publishes no recovery rule. Canonical form: the pack as is (`]d1` + pack in the ambiguous case), a GS1 code as
  barcode data with its GS separators,
  AIs in the order given (store the GS characters too).
- **`RU\MarkingIdentifier`** (КИ, the code without the crypto tail): (01) + (21); with (8005) for the tobacco group pack
  (Decree No. 224, p. 44: "первые 3 группы"; any serial length, as `GenericGs1` codes have); the first 25 characters of the pack. `from()` takes the
  canonical forms only: bracketed `(01)…(21)…` or the 25 pack characters (any 25-character string is read as a
  pack КИ by `from()`). `fromDocumentForm()` reads the operator's
  `01…21…` (which `toDocumentForm()` gives); 25 characters that read both as a pack КИ and as `01…21…` + 7 throw
  `AmbiguousValueException` there.
- **Russian product groups covered** (structure read on the operator's pages, decree cited there): tobacco and
  nicotine products, light industry, footwear, tyres, perfume, cameras, dairy, packaged water, beer, non-alcoholic beer, non-alcoholic
  beverages, BAD, antiseptics, medical devices, medicines (MDLP), veterinary medicines, canned food, sweets, grocery,
  meat, caviar, pet food, vegetable oils, sports nutrition, construction materials, household goods, cosmetics and
  household chemicals, toys, bicycles, technical rehabilitation equipment, radio-electronics, electronic nicotine
  delivery systems, motor oils, cables, optical fibre, polymer pipes.
- **Not covered:** see [Not covered](#not-covered); such codes parse as `GenericGs1` when their separators are intact.
- **`EU\FmdPackCode`** (Delegated Regulation (EU) 2016/161): a GS1 element string with (01), (21), (10), (17), or the
  IFA format `[)>` RS `06` GS `9N…` GS `S…` GS `1T…` GS `D…` RS EOT (ISO/IEC 15434 format 06, ASC data identifiers;
  the trailer is optional on input, so is a scanner's `]d1`/`]d2` before it; 8P may repeat, other DIs are kept). `productCode(): Gtin|Ppn`, `serial()`,
  `batch()`, `expiry()`, `expiryDate($today)` (IFA: 2000-2099; `DD = 00` the last day of the month), `pzn()` (from a
  PRA-11 PPN or an NTIN-DE `04150…`), `isIfaFormat()`, `elementString()`. Canonical form: the GS1 barcode data or the
  IFA message with its trailer (control characters included).
- **`EU\Ppn`**: PRA Code + product number + two check digits (IFA modulo 97); for PRA Code 11 the PZN check digit
  too. `fromParts()`, `praCode()`, `productNumber()`, `pzn()`, `toNtin()`.
- **`EU\TobaccoUnitIdentifier`** (Implementing Regulation (EU) 2018/574, Article 8): the full UI, 11 to 50
  alphanumeric characters ending with the time stamp `YYMMDDhh` in UTC; `issuerPart()`, `timeStamp()`,
  `manufacturedAt()`, `withoutTimeStamp()`. Format only otherwise: the ID issuer's own structure (possibly encrypted)
  is not published. Century 2000: a package decision.
- **`EU\TobaccoUnitIdentifierWithoutTimeStamp`**: what a data carrier holds when the time stamp is printed beside it
  (Article 21(4)), 3 to 42 alphanumeric characters, no time-stamp accessors. Use it for scanned content unless you
  know the carrier includes the time stamp: trailing digits are never guessed to be one.
  `withTimeStamp($manufacturedAt)` builds the full UI (UTC hour).
- Size limits before parsing: Russian codes and identifiers 255 characters, FMD pack codes 512.

## Format only

One list for the whole package: where no published source gives a rule, the class checks the format and no more.

| What | Why |
|---|---|
| `mediatype`, `packagetype` | code lists that grow; GS1 itself checks the format |
| `iban` | the per-country structure belongs to a later wave |
| `couponcode`, `couponposoffer` | coupons are out of scope |
| Registry half of `gcppos1`/`gcppos2` | whether a prefix is a real GS1 Company Prefix is a registry fact; decoding never refuses |
| BIC check digit (ISO 6346) and IMO number check digit in EPC schemes `bic`, `imovn` | not in the EPC TDS; ISO 6346 and IMO checks arrive in wave 3 (`vo-identifiers`) |
| TDS 7.3.3/7.3.4 (no SGTIN from RCN-8 or internal numbering) | not enforced |
| ISBT 128 compound sequences other than 034-037; non-UDI ISBT 128 data structures | the order is in ICCBBA's RT017, not public |
| Alternatively formatted NDC (21 CFR 207.33(b)(4)) | no published format; not accepted |
| Russian crypto tail (AI 91/92/93 contents) | verifying it needs the operator's keys; only its format is checked |
| `GenericGs1` marking codes | AI table only: no page publishes their lengths |
| EU `TobaccoUnitIdentifier` beyond length, character set and time stamp | the rest is each ID issuer's own, possibly encrypted |
| Digital Link extension parameters | kept as given; their meaning is not GS1's |

## Not covered

- Russian product groups whose structure page was not found (auto parts, pharmaceutical raw materials, frozen and
  semi-finished food, heating appliances, titanium products, fertilisers, pyrotechnics, fire safety equipment, printed
  matter, wheelchairs). Their codes still parse as `GenericGs1` or a published structure when the separators are intact.
- Fur coats (an RFID code, not a Data Matrix code) and alcohol (EGAIS excise stamps, another system).
- Compressed GS1 Digital Link: URI Syntax 1.7.0 dropped it, so such links are refused.
- Binary and hex EPC tag encodings, EPC Raw and Class URIs.
- Verification of cryptographic signatures and tails (Russian marking, FMD).
- GS1 Member Organisation names for a GS1 Prefix: the allocation list is not in the General Specifications.
- Coupons beyond the format check.

## With the bridges

Every class is a string value object, so `ValueObjectMapper` (vo-base) and the framework bridges map it with no codec:
the stored value is the canonical string. Marking and FMD codes carry control characters (GS, RS, EOT) that must be
stored as they are; an element string is stored in its bracketed form, which has none. For Doctrine use
[`msstc4php/vo-gs1-doctrine`](https://github.com/msstc4php/vo-gs1-doctrine) (31 ORM and ODM types, `gs1_<snake_name>`).

## GS1 Application Identifier table

The AI table (data formats, FNC1 rule, linters, mandatory and invalid AI pairs) is generated from the
[GS1 Barcode Syntax Dictionary](https://github.com/gs1/gs1-syntax-dictionary), release 2026-01-27, which is vendored
unmodified under `resources/` (Apache License 2.0, see [`NOTICE`](NOTICE)). Update policy:

- The dictionary release is pinned (recorded in the header of `src/Internal/AiTable.php`); the generator refuses any
  other release until its constants are updated.
- A new release is picked up with `php tools/generate-ai-table.php` and ships as a minor release.
- Stored values are parsed against the installed table (on loading, and again by the accessors of the composite
  codes), so a value stored under an older release can be refused after an update (see Element strings).
- Regenerating needs a development checkout with the dev dependencies installed: the generator lives in `tests/Support/` and the script in `tools/`, both
  export-ignored (`.gitattributes`), so the Composer package ships neither.

## License

MIT, see [`LICENSE`](LICENSE). The GS1 Barcode Syntax Dictionary is Apache-2.0, see [`NOTICE`](NOTICE).
