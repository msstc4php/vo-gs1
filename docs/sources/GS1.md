# Sources: GS1, medical-device UDI, NDC and marking codes

Research of 2026-11-19 (UTC). Every check digit and structural rule in this package comes from a source below; the
quotes are a sentence or a table row, the documents themselves stay at their links. Downloaded PDFs were read locally
and are not part of the repository. Later tasks extend the sections they implement (marking structures: Task 8).

## Summary

| Source | Edition / date | Fetched | Status |
|---|---|---|---|
| [GS1 General Specifications](https://ref.gs1.org/standards/genspecs/) | Release 26.0, Ratified, Jan 2026 | 2026-11-19 | read (PDF, 579 pages) |
| [GS1 Barcode Syntax Dictionary](https://github.com/gs1/gs1-syntax-dictionary) | release `2026-01-27`, commit `8ad003f83a4ba6c2179ce15d578fae27d6580909` | 2026-11-19 | vendored, Apache-2.0 |
| [GS1 Barcode Syntax Resource](https://ref.gs1.org/tools/gs1-barcode-syntax-resource/) | web page | 2026-11-19 | read |
| [GS1 Barcode Syntax Engine](https://github.com/gs1/gs1-syntax-engine) | `main`, commit `58c1dc85d9ff4eb9368293bcf5264a9fe70bfc72` (2026-10-08) | 2026-11-19 | read (C sources and headers), Apache-2.0 |
| [GS1 Digital Link Standard: URI Syntax](https://ref.gs1.org/standards/digital-link/uri-syntax/) | Release 1.7.0, Ratified Aug 2026 | 2026-11-19 | read (HTML) |
| [EPC Tag Data Standard](https://ref.gs1.org/standards/tds/) | Release 2.3, Ratified, Oct 2025 | 2026-11-19 | downloaded (PDF); read in Task 6 |
| [ANSI/HIBC 2.6 Supplier Labeling Standard](https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf) | ANSI/HIBC 2.6-2016 (R2026) | 2026-11-19 | read (PDF) |
| [HIBCC Mod 43 Check Character](https://www.hibcc.org/wp-content/uploads/Mod-43-Check-Character.pdf) | Appendix B of ANSI/HIBC 2.5-2015 | 2026-11-19 | read; same text as 2.6 Appendix B |
| [HIBC Basic UDI-DI](https://www.hibcc.org/wp-content/uploads/HIBCC-Basic-UDI-DI.pdf) | undated | 2026-11-19 | read (PDF) |
| [ICCBBA ST-017, Coding and Labeling of Medical Devices Containing MPHO](https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf) | v1.0.1, September 2024 | 2026-11-19 | read (PDF): ISBT 128 is reachable |
| [21 CFR 207.33 (eCFR, current)](https://www.ecfr.gov/current/title-21/chapter-I/subchapter-C/part-207/subpart-B/section-207.33) | current text | 2026-11-19 | read |
| [FDA final rule, 91 FR 10749, doc 2026-04368](https://www.federalregister.gov/documents/2026/03/05/2026-04368/revising-the-national-drug-code-format-and-drug-label-barcode-requirements) | published 2026-03-05, effective 2033-03-07 | 2026-11-19 | read (full text) |
| Delegated Regulation (EU) 2016/161 | OJ L 32, 9.2.2016 | 2026-11-19 | EUR-Lex unreachable (see below); text read on legislation.gov.uk |
| [IFA PPN-Code Specification for Retail Packaging](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA_Spec_PPN_Code_Handelspackung_EN.pdf) | Version 3.06, 6 November 2023 | 2026-11-19 | read (PDF) |
| [IFA Check Digit Calculations of PZN, PPN and Basic UDI-DI](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Check_Digit_Calculations_PZN_PPN_UDI_EN.pdf) | undated | 2026-11-19 | read (PDF) |
| Implementing Regulation (EU) 2018/574 | OJ L 96, 16.4.2018 | 2026-11-19 | EUR-Lex unreachable; text read on legislation.gov.uk |
| Russian Government decrees per product group | see section 9 | 2026-11-19 | located on publication.pravo.gov.ru |
| [Operator knowledge base (ЦРПТ, «Честный знак»)](https://markirovka.ru/knowledge/tovarnye-gruppy/) | pages dated 2026 | 2026-11-19 | read (sample pages) |

## 1. GS1 General Specifications, Release 26.0

`https://ref.gs1.org/standards/genspecs/` serves the PDF itself (12 175 796 bytes); its cover reads "Release 26.0,
Ratified, Jan 26".

### Check digit (section 7.9.1, Table 7-8) — `Internal\Gs1CheckDigit`

> This algorithm is identical for all fixed length numeric GS1 data structures (including GDTI, GLN, GRAI, etc.) that
> require a check digit.

Table 7-8: positions multiplied by `x3 x1 x3 …` from the position before the check digit leftwards; "Subtract sum from
nearest equal or higher multiple of ten = check digit". Table 7-9 example: `37610425002123456` → sum 101 → check digit
`9`. Section 7.2.7: "the check digit will not normally be verified by the barcode reader and SHALL be verified
separately".

### GTIN formats and the 14-digit form (sections 1.3.1, 2.1.1.10)

> A GTIN may be an eight, twelve, thirteen or fourteen-digit string.

> When any of these GTINs is encoded in a data carrier that must encode a fixed-length data string of 14-digits, the
> GTINs less than 14-digits in length must be prefixed by leading zeroes that simply act as filler digits.

> The presence or lack of these leading zeroes does not change the GTIN concerned.

**Database form: Release 26.0 does not prescribe the 14-digit form.** Section 2.1.1.10 says instead:

> Note: GTINs may be stored with or without leading zeroes in the same database field, depending on the requirements
> of the particular application.

and section 1.3.1 adds "there SHALL be consistency within the database field about whether these leading zeroes are
present or not", and that the leading zeroes of a GTIN-12 "are meaningful since they are part of the U.P.C. Company
Prefix … Therefore these zeroes must be preserved when storing the GTIN-12". Per the plan's fallback, `Gtin` (Task 4)
keeps the form given and compares the 14-digit form for equality. `Gtin` does not strip spaces or hyphens, like
every other key. The GS1 Digital Link URI Syntax (section 4) does require 14 digits in a URI.

### GS1 Prefix list (section 1.2.3.1, Table 1-4; GS1-8: Table 1-5) — `Key\Gs1Prefix`

Rows of Table 1-4 ("Synopsis of GS1 Prefix ranges") that `Gs1Prefix::fromDigits()` branches on, verbatim:

| GS1 Prefix range | Significance |
|---|---|
| 0000000 | Used to issue Restricted Circulation Numbers within a company |
| 0000001 – 0000099 | Unused to avoid collision with GTIN-8 |
| 02 | Used to issue Restricted Circulation Numbers within a geographic region (MO defined) |
| 04 | Used to issue Restricted Circulation Numbers within a company (GS1 user company defined) |
| 20 – 29 | Used to issue Restricted Circulation Numbers within a geographic region (MO defined) |
| 951 | Previously used (2004-2012) to issue General Manager Numbers for the EPC General Identifier (GID) scheme … GS1 discontinued issuance of new General Manager Numbers in June 2023. |
| 952 | Used for demonstrations and examples of the GS1 system |
| 977 | Allocated to ISSN International Centre for serial publications |
| 978 – 979 | Allocated to International ISBN Agency for books, a portion of 979 sub-allocated to International ISMN Agency for music |
| 980 | Used to issue GS1 identification of refund receipts (MO defined) |
| 981 – 983 | Used to issue GS1 coupon identification for common currency areas (MO defined) |
| 984 – 989 | Reserved for future use |
| 99 | Used to issue GS1 coupon identification (MO defined) |

Every other row ("Used to issue GS1 Company Prefixes", with or without "from which U.P.C. Company Prefixes can be
derived") is decoded as `MemberOrganisation`; `951` and `984 – 989` as `Unknown`. Table 1-5 ("Synopsis of GS1-8
Prefixes", section 1.2.3.2, "a unique string of two or more digits"), `Gs1Prefix::fromGtin8Digits()`:

| GS1-8 Prefixes | Significance |
|---|---|
| 000 – 099 | Used to issue Restricted Circulation Numbers within a company (company defined) |
| 200 – 299 | Used to issue Restricted Circulation Numbers within a company (company defined) |
| 952 | Used for demonstrations and examples of the GS1 system |
| 977 – 999 | Reserved for future use |

(`100 – 199`, `300 – 951` and `953 – 976` "Used to issue GTIN-8s" decode as `MemberOrganisation`; `977 – 999` as
`Unknown`.) The allocation of a prefix to a given Member Organisation is not in the General Specifications; it is
GS1's published prefix list (<https://www.gs1.org/standards/id-keys/company-prefix>), not fetched, so `Gs1Prefix` does
not name Member Organisations. `Gtin::gs1Prefix()` decodes the 14-digit form, so equal GTINs give one result: a form
that starts with six zeros is read as a GTIN-8 (the dictionary's `gcppos1` note normalises a GS1-8 Prefix "by
prefixing `00000`" to the 13-digit space), any other by dropping the indicator digit.

> Note: Once a GS1 Prefix is issued, no other GS1 Prefix that begins with the same digits SHALL be issued

### GS1 Company Prefix length (section 1.2.3.3) — `Internal\KeyRules`

> A GS1 Company Prefix is a unique string of four to twelve digits used to issue GS1 identification keys. The first
> digits are a valid GS1 Prefix and the length of the GS1 Company Prefix SHALL be at least one digit longer than the
> length of the GS1 Prefix.

So `fromParts()` takes a company prefix of 4 to 12 digits, also for GTIN-12 and GTIN-13. For a GTIN-8 it takes the
GS1-8 Prefix, which Table 1-5 (section 1.2.3.2) defines as "a unique string of two or more digits": 2 to 12 digits. For the
keys that begin with the company prefix and have no check digit (`Giai`, `Ginc`, `Cpid`, `Gmn`; dictionary linter
`gcppos1`) the dictionary's reference implementation (`src/lint_gcppos1.c`, tag `2026-01-27`, Apache-2.0) says:

> `#define GCP_MIN_LENGTH 4  ///< Currently the shortest GS1 Company Prefix is four digits.`

> Any character within the minimum-length GCP prefix that is outside the range '0' to '9' is illegal.

and "The `gcppos1` linter checks whether an input starts with a GS1 Company Prefix", so those keys must start with four
digits. The `zero` linter of GRAI (`N1,zero N13,csum`) "ensures that the given data has a zero value".

### Character sets (section 7.11) — `Internal\Gs1Charsets`

Table 7-20 "GS1 AI encodable character set 82" lists `! " % & ' ( ) * + , - . / 0-9 : ; < = > ? A-Z _ a-z`; Table 7-21
"GS1 AI encodable character set 39" lists `# - / 0-9 A-Z` (Number Sign, Hyphen/Minus, Solidus, the ten digits, the
capital letters). Section 7.11: Table 7-20 is for "all characters allowed for use in GS1 Application Identifier (AI)
element strings with the exception of the Component and Parts Identifier"; Table 7-21 lists "all the characters
allowed for use in the GS1 Application Identifier for Component and Parts Identifier" (`Cpid`, AI 8010; the
dictionary types it `Y..30`).

### Examples used by the key tests (GS1 General Specifications 26.0)

The text of the document carries these keys, which the tests use: GRAI plain syntax `9524141234564789` (GS1 Company
Prefix 9524141, asset type 23456, check digit 4, serial 789; element string `(8003)09524141234564789`), SSCC
`095212340005678903` (section on Digital Link URIs), GLN `(414)9521234500018`, GIAI `09521234500XY09`
(`...?8004=09521234500XY09`), GMN `1987654Ad4X4bL5ttr2310c2K` (Figure 7-15) and the SSCC of Table 7-9. **Gap:** the
document's text (pdftotext) has no example value for GDTI, GCN, GSIN, GSRN, GINC or CPID (the coupon examples of
2.6.2.1 are images); their tests use company prefix 0614141 with check digits computed by `Gs1CheckDigit`.

### Dates and the century window (sections 3.4.2 and 7.12)

AI (11) and the other `YYMMDD` dates (3.4.2):

> When it is not necessary to specify the day (the day field is filled with two zeroes), the resultant data string
> SHALL be interpreted as the last day of the noted month including any adjustment for leap years (e.g., “130200” is
> “2013 February 28”, “160200” is “2016 February 29”, etc.).

Since 1 January 2025 regulated healthcare products "SHALL NOT" use `00` (note under 3.4.2); old values stay readable.
Section 7.12, Figure 7-17 "Determination of century" (a flow chart; its boxes read): "Subtract current year from N1 N2"
→ "Result is 51 to 99" → "Year N1 N2 is previous century"; otherwise "Result is -50 to -99" → "Year N1 N2 is next
century"; otherwise "Year N1 N2 is current century". Note: "The element string can only specify a date in the range
from 49 years in the past to 50 years in the future of the current year".

### FNC1 and predefined lengths (sections 7.8.3, 7.8.4)

> These element strings are shown in the predefined table in Table 7-6. All other element strings, even if defined as
> fixed length in section 3, are not of predefined length and are formally variable length fields which require a
> separator character if followed by another element string.

> The value of the decoded separator character transmitted in the decoded data string is always control character
> <GS> (ASCII value 29 (decimal), 1D (hexadecimal)).

The dictionary's `*` flag carries Table 7-6 (see section 2).

### GMN check character pair (section 7.9.5, Tables 7-18, 7-19, Figure 7-15) — `Internal\GmnCheckCharacters`

> GS1's check character algorithm uses MOD 1021,32 to calculate the check character pair for use in alphanumeric data
> structures

> Beginning with the right most non-check character (Xj) and progressing left to first character (N1) the prime
> weight increases 2, 3, 5, 7, 11, 13, to Wn

Table 7-18 assigns each CSET 82 character its position (`!` = 0 … `z` = 81); Table 7-19 is CSET 32
`23456789ABCDEFGHJKLMNPQRSTUVWXYZ` (0 … 31); "C1 = INT (Ck / 32)", "C2 = Ck MOD 32". Figure 7-15: GMN
`1987654Ad4X4bL5ttr2310c2K`, sum 24521, MOD 1021 = 17, check characters `2` `K`. Section 3.9.13: the GMN is AI (8013),
`X..25`; the Basic UDI-DI of GS1 is the GMN ("Basic UDI-DI (GMN) is used for medical device registration", section
2.6.13).

## 2. GS1 Barcode Syntax Dictionary — `Internal\AiTable`

Repository <https://github.com/gs1/gs1-syntax-dictionary>, tag `2026-01-27` (the latest release on 2026-11-19), commit
`8ad003f83a4ba6c2179ce15d578fae27d6580909`. `main` (`c63d8a1`, 2026-08-29) carries unreleased changes ("Release:
UNSET", e.g. AI (03) relationships per WR 25-306, `req=A,B req=C` rewritten as `req=A+C,B+C`); the table follows
releases only. The generator reads both `req` notations.

Licence: the repository's `LICENSE` is the Apache License 2.0, and the file header says:

> Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
> the License.

Copyright lines: "Copyright (c) 2020-2021 BWIPP project", "Copyright (c) 2020-2021 Zint Project", "Copyright (c)
2021-2025 GS1 AISBL". Redistribution is allowed with the licence and attribution: the file is vendored unmodified as
`resources/gs1-syntax-dictionary.txt`, the licence as `resources/gs1-syntax-dictionary.LICENSE`, and `NOTICE` names
both and the derived `src/Internal/AiTable.php`. The upstream repository has no NOTICE file.

The GS1 Barcode Syntax Resource page names it as GS1's own: "A simple text file that lists all currently assigned GS1
Application Identifiers (AI) and the components required to form a valid GS1 data syntax." The header defines the
syntax the generator implements:

> "*" - Pre-defined length AI not requiring FNC1 separator

> "req=01+21,02,35nn" - Mandatory association with one of the listed AI or pattern groups

> "ex=8111,394n" - Invalid to pair with any of the listed AIs or patterns

> Note: An AI is not exclusive of itself even if it matches a pattern

The GS1 linters (`src/lint_*.c` at the same tag, Apache-2.0) are the reference for the named checks; their test
vectors are used as test data (`lint_csumalpha.c`: `1987654Ad4X4bL5ttr2310c2K`, `12345678901234567890123NT`, …).

### Element strings (Task 5) — `ElementString\ElementString`, `Internal\ElementStringSyntax`

Bracketed notation is the human readable interpretation, section 1.4.2:

> surrounded by parentheses e.g., (01)09521234500001(10)ABC123(11)250504. Parentheses are not a part of the GS1
> Application Identifier and are only used to aid readability.

and section 4.14: "parentheses SHALL surround AIs in HRI". A `(` inside data is escaped as the GS1 Barcode Syntax
Engine does (<https://github.com/gs1/gs1-syntax-engine>, `src/c-lib/gs1encoders.h`, `gs1_encoder_setAIdataStr()`,
commit `58c1dc85d9ff4eb9368293bcf5264a9fe70bfc72`, Apache-2.0):

> Any "(" characters in AI element values must be escaped as "\(" to avoid conflating them with the start of the
> next AI.

Unbracketed data: FNC1 is transmitted as GS (section 7.8.4, quoted above); `^` is the engine's notation
(`gs1_encoder_setDataStr()`):

> A "^" character at the start of the input indicates that the data is in GS1 Application Identifier syntax. In this
> case, all subsequent instances of the "^" character represent the FNC1 non-data characters that are used to
> separate fields that are not specified as being pre-defined length from subsequent fields.

Symbology identifiers, section 5.1.3, Table 5-2 ("If used, the symbology identifier is transmitted as a prefix to the
data message"): `]C1` GS1-128, `]e0` GS1 DataBar, `]d2` GS1 DataMatrix, `]Q3` GS1 QR Code, `]J1` GS1 DotCode, each
"Standard AI element strings". `]d1` and `]Q1` carry a GS1 Digital Link URI and are refused here (Task 6).

Splitting unbracketed data follows the engine's `gs1_processAIdata()` (`src/c-lib/ai.c`): an AI of predefined length
(dictionary flag `*`) takes its fixed length, any other AI runs to the next FNC1 or the end ("r points to the next
FNC1 or end of string"), and data still left after an FNC1-terminated AI is refused (`AI_DATA_IS_TOO_LONG`). So data
whose separators were lost is never cut at a guessed length. The engine also skips a separator after a
predefined-length AI ("Skip FNC1, even at end of fixed-length AIs"), which is accepted here too.

Repeated AIs, `validateAIrepeats()` in `ai.c`:

> AI validation routine that ensure that any repeated AIs in the data have the same value. (Repeated AIs may occur
> when the AI data from reads of multiple symbol carriers on the same label is concatenated.)

Association rules: `req` and `ex` as the dictionary header states them (section 2), matched as the engine's
`existsInAIdata()` does (an AI pattern of the same length, `n` any digit, never the AI itself). The header adds:

> Note: Invalid and mandatory AI pairings should be evaluated over the combined data received from all GS1 carriers
> marking a physical item.

An invalid pair (`ex`) is invalid in any part of that data, so it is refused. A mandatory partner (`req`) may sit in
another carrier of the item, so a missing one is **not** refused: `ElementString::unmetRequirements()` reports it (the
engine likewise lets `gs1_encoder_vREQUISITE_AIS` be disabled, while `vMUTEX_AIS` is locked on). **Not enforced:** the engine's `validateDigSigRequiresSerialisedKey()` (AI 8030 with
a serial on 253/255/8003) is an engine rule, not a dictionary attribute.

CSET 64 (type `Z`, AI 8030), Table 7-22, Note 1: "the upper-case letters A-Z, lower-case letters a-z, digits 0-9,
hyphen (-), low line/underscore/underline (_) and the equals character (=) as a special pad character". Enforced: those
characters, `=` only as up to two trailing pad characters (the reference `lint_cset64.c` also refuses padding unless
the length is a multiple of 3; that check is not applied).

Dates: `DD = 00` and the century window are section 3.4.2 and 7.12 above. The reference `lint_yymmd0.c` picks the
century from the clock before checking the day ("Convert YY to a year using a horizon based on CURRENT_YEAR"); here the
`yymmd0` and `yymmdd` linters check month and day only, with 29 February valid for every `YY` divisible by four (`00`
included), so whether a value parses never depends on the day it is parsed. The century is applied only by
`expiryDate()` / `productionDate()`, relative to their `$today` argument; a 29 February that the chosen century lacks
(`00` read as 2100) throws there.

Size bound, section 5.7.4.1 (GS1 QR Code square format):

> The largest symbol (177 X 177 modules, Error Correction Level = L) can encode up to 7089 digits or 4296
> alphanumeric characters technically, however permissible data is specified by the application standards.

That is the largest capacity the General Specifications state for a GS1 data carrier (GS1 DataMatrix: "Numeric data:
3116 digits", section 5.6.2; GS1-128: 48), so the unbracketed data (GS separators included) is limited to 7089
characters. Input is refused before parsing above twice that (14178): no notation more than doubles the data.

#### Linters

Every linter the dictionary names on an AI component. "Enforced" ones follow the reference implementation
(`src/lint_<name>.c` of the dictionary repository at tag `2026-01-27`, its `@brief` quoted); "not enforced" ones always
pass and the AI is checked for format only. `AiLintersTest` fails when the dictionary names a linter missing here.

| Linter | Status | Source quote or reason |
|---|---|---|
| `csum` | enforced | GenSpecs 7.9.1 check digit (`Internal\Gs1CheckDigit`) |
| `csumalpha` | enforced | GenSpecs 7.9.5 check character pair; `lint_csumalpha.c` accepts `22` as the pair of an empty body |
| `gcppos1` | enforced | "checks whether an input starts with a GS1 Company Prefix": the four leading digits (`GCP_MIN_LENGTH`); the registry lookup (`GS1_LINTER_CUSTOM_GCP_LOOKUP`) is registry data and not used |
| `gcppos2` | enforced | as `gcppos1` "from its second character position" |
| `yymmd0` | enforced | "ensures that the data represents a meaningful date, in YYMMDD format, additionally permitting YYMM00 format indicating an unspecified day" |
| `yymmdd` | enforced | "ensures that the data represents a meaningful date, in YYMMDD format" (day `00` refused) |
| `yyyymmdd` | enforced | "ensures that the data represents a meaningful date, in YYYYMMDD format" |
| `hh` | enforced | "ensures that the given data is a meaningful hour number in a day" (00-23) |
| `mi` | enforced | "ensures that the given data is a meaningful minute number in an hour" (00-59) |
| `ss` | enforced | "ensures that the given data is a meaningful second number in a minute" (00-59) |
| `hhmi` | enforced | "ensures that the given data is meaningful time as hours and minutes, in HHMI format" |
| `iso3166` | enforced | ISO 3166-1 "num-3" codes; vo-base `CountryCode::fromNumeric` (same 249 codes as the linter's table) |
| `iso3166999` | enforced | "an ISO 3166 "num-3" country code, or the value "999"" |
| `iso3166alpha2` | enforced | ISO 3166-1 "alpha-2" codes; vo-base `CountryCode` (same 249 codes) |
| `iso4217` | enforced | ISO 4217 three-digit codes: vo-base `CurrencyCode::fromNumeric`, plus the linter's codes vo-base does not carry (955-965, 994, 999: funds, metals, testing, no currency); withdrawn codes vo-base knows are accepted, though the linter's table has only current ones |
| `iso5218` | enforced | ""0" (not known), "1" (male), "2" (female) or "9" (not applicable)" |
| `zero` | enforced | "ensures that the given data has a zero value" |
| `nonzero` | enforced | "ensures that the given data has a non-zero value" |
| `nozeroprefix` | enforced | "ensures that the given data does not start with a zero" |
| `yesno` | enforced | "either "0" (no) or "1" (yes)" |
| `winding` | enforced | ""0" (face out), "1" (face in) or "9" (undefined)" |
| `pieceoftotal` | enforced | "a concatenation of non-zero piece number follow by an equal-width, non-zero total number of pieces" (piece not above total) |
| `hasnondigit` | enforced | "ensures that the given data contains a non-digit character" |
| `posinseqslash` | enforced | "a meaningful position in a sequence in the format "`<pos>/<end>`"" (no leading zeros, position not above end) |
| `latitude` | enforced | "the latitude component of a WGS84 coordinate expressed as a 10-digit number" (at most 1800000000) |
| `longitude` | enforced | "the longitude component of a WGS84 coordinate expressed as a 10-digit number" (at most 3600000000) |
| `hyphen` | enforced | "ensures that the given data contains only hyphens" |
| `importeridx` | enforced | "a valid Importer Index … used in the EU 2018/574 system": one of `-`, `0-9`, `A-Z`, `_`, `a-z` |
| `pcenc` | enforced | "ensures that the data has correct percent-encoding" (RFC 3986 section 2.1) |
| `mediatype` | not enforced | AIDC media type code list (ICCBBA and GS1 assignments, many ranges "Reserved for future assignment"): a newer list would make a refused value valid |
| `packagetype` | not enforced | UN/ECE Recommendation 21 codes "extended with additional GS1 code values", a list GS1 maintains: a newer list would make a refused value valid |
| `iban` | not enforced | the per-country IBAN structure belongs to the finance wave (plan: wave 2) |
| `couponcode` | not enforced | the North American coupon code structure (AI 8110) is out of scope of this wave |
| `couponposoffer` | not enforced | the positive offer file coupon structure (AI 8112) is out of scope of this wave |

## 3. GS1 Digital Link URI Syntax, Release 1.7.0 (Task 6)

Compression is **not** defined in the URI Syntax any more. Section 1 points to a separate standard:

> This document [ DL-CompressionEPC ] explains how GS1 identifiers in EPC binary syntax can be expressed as a special
> compressed form of a GS1 Digital Link URI … This specification supersedes the compression/decompression algorithm
> defined in the legacy GS1 Digital Link Standard [ DL-Legacy ].

Section 6.1.2:

> Compressed GS1 Digital Link URIs that use the legacy specification are not expected to be used in any future
> Application Standards and are not approved for use in open supply chain standards.

So compressed links fall outside the URI Syntax edition; Task 6 refuses them and the README says why. Section 4.1:

> As of version 1.4.0 , the GS1 Digital Link standard: URI syntax now expects the GTIN value to be expressed using 14
> digits

with "only existing infrastructure … SHOULD continue to support legacy expressions" (shorter GTINs, convenience
alphas, removed in 1.3.0).

## 4. EPC Tag Data Standard (Task 6)

`https://ref.gs1.org/standards/tds/` serves "EPC Tag Data Standard TDS … Release 2.3, Ratified, Oct 2025" (its log:
"Introduction of twelve new ‘++’ EPC schemes, SGTIN++ and DSGTIN++ …"). Read in Task 6.

## 5. HIBCC (Task 7) — `Internal\Mod43`

ANSI/HIBC 2.6-2016, reaffirmed 2026 (cover: "ANSI/HIBC 2.6 – 2016 (R2026)"), Appendix B.2.0:

> The Check Character is the Modulo 43 sum of all the character values in a given message and is printed as the last
> character in a given message, preceding the Stop Character.

> Sum of values: 41+10+1+2+3+11+19+12+5+13+6+14+7+1 = 145

> Divide 145 by 43. The quotient is 3 with a remainder of 16. The Check Character is the character corresponding to
> the value of the remainder (see table below), which in this example is 16, or “G”.

Table B1: `0`-`9` = 0-9, `A`-`Z` = 10-35, `-` = 36, `.` = 37, Sp = 38, `$` = 39, `/` = 40, `+` = 41, `%` = 42. B.2.1:
the check character may be a space. The stand-alone "Mod 43 Check Character" PDF on hibcc.org is the same appendix in
its 2.5-2015 edition.

HIBC Basic UDI-DI (undated PDF on hibcc.org): structure `++` + LIC (4, first character alphabetical) + Global Model
Identifier (1-17) + check characters (2), example `++A999MODELIDENTIFIER11S8`.

> The HIBCC keying check character scheme utilizes Modulo 1021 arithmetic to calculate the check value and then two
> characters from a 32-character set to represent the check value.

The weights are the primes from 2 on the character next to the pair, leftwards — the GS1 MOD 1021,32 scheme; its
example values (`1` = 14) match GS1 Table 7-18. Task 7 verifies the example with `GmnCheckCharacters`.

## 6. ICCBBA (ISBT 128) (Task 7)

Reachable. ST-017 v1.0.1 (September 2024), section 2.1: the UDI-DI is the Processor Product Identification Code,
"Structure: =/nnnnnppppppqqqqq" (`nnnnn` alphanumeric `{A-N, P-Z, 0-9}`, `pppppp` and `qqqqq` `{A-Z, 0-9}`); example
UDI `=+06000=/A9999XYZ100T0476=,000025=A99971712345600=>019032=}017032&,1000000000000XYZ123`. Appendix 1 (Basic UDI-DI):
"The Basic UDI-DI format is identical to the UDI-DI format with the exception that it carries an additional two digits
that form a mod 37-2 checksum", structure `nnnnnppppppqqqqqbb`. ST-011 (medical devices using ISBT 128, v1.9.0, April
2024) was not fetched: its links point to a portal outside the allowed hosts; Task 7 uses ST-017 or retries ST-011.

## 7. FDA NDC (Task 7)

21 CFR 207.33(b), current eCFR text (with the cross-reference "Link to an amendment published at 91 FR 10770, Mar. 5,
2026"):

> (1) Except as described in paragraph (b)(4) of this section, the NDC must consist of 10 or 11 digits, divided into
> three segments

> (ii) If a labeler code is 4 digits in length, it may be combined only with a product code consisting of 4 digits and
> a package code consisting of 2 digits for a total NDC length of 10 digits (4-4-2).

and (b)(2)(i): a 5- or 6-digit labeler code with 4-1 or 3-2 (5-4-1, 6-4-1, 5-3-2, 6-3-2). The 11-digit 6-x-x forms are
in the current rule; the final rule discusses what would happen "If, in the absence of this final rule, FDA were to
begin issuing 6-digit labeler codes". Task 7 decides whether `Ndc` accepts them.

Final rule "Revising the National Drug Code Format and Drug Label Barcode Requirements", 91 FR 10749, published
2026-03-05, effective 2033-03-07 (Federal Register API: `effective_on` 2033-03-07). Amended 207.33(b)(1):

> the NDC must consist of 12 digits, divided into three segments as follows: (i) The first segment of the NDC is the
> labeler code and consists of 6 digits.

with a 4-digit product code and 2-digit package code. Table 1 of the rule shows the HIPAA 11-digit conversion
`10010-001-01` → `10010-0001-01` → `10010000101`.

## 8. EU: falsified medicines and tobacco (Task 8)

EUR-Lex (`eur-lex.europa.eu`, HTML and PDF, and the `data.europa.eu/eli` endpoints) answered HTTP 202 with an empty
body (a bot challenge) on 2026-11-19 (UTC). The texts were read on legislation.gov.uk, "Original (As adopted by EU)":
<https://www.legislation.gov.uk/eur/2016/161/article/4/adopted>, `/eur/2016/161/article/5/adopted`,
<https://www.legislation.gov.uk/eur/2018/574/article/8/adopted>. Task 8 retries EUR-Lex for the `@see` links.

Delegated Regulation (EU) 2016/161, Article 4(b): the unique identifier consists of the product code, "a numeric or
alphanumeric sequence of maximum 20 characters, generated by a deterministic or a non-deterministic randomisation
algorithm (‘serial number’)", a national reimbursement number if required, the batch number and the expiry date.
Article 5(5): the product code "shall be less than 50 characters and be globally unique".

IFA PPN-Code Specification for Retail Packaging, Version 3.06 (6 November 2023): the IFA format of the FMD pack code.
IFA check digits (Technical Information "Check Digit Calculations of PZN, PPN and Basic UDI-DI"):

> The check digit of the PPN and the Basic UDI-DI is calculated based on mod 97.

> The weighting of the digits starts on the left with two and increases by one for each following digit.

Example: PRA Code `11` + PZN `03752864`, sum 3409, `3409 / 97 = 35 remainder 14`, "this is the complete PPN:
110375286414". PZN: mod 11, weights 1 to 7, example `27580899`.

Implementing Regulation (EU) 2018/574, Article 8(1): the unit level UI is "as short a sequence of alphanumeric
characters as possible, not exceeding 50 characters", starting with the ID issuer identification code and ending with
"the time stamp in the form of a numeric sequence of eight characters, in the format YYMMDDhh, indicating the date and
time of manufacture". Article 8(4): "Where ID issuers use encryption or compression for the generation of unit level
UIs, they shall inform the competent authorities" — the inner structure is each ID issuer's own.

## 9. Russian marking (Task 8)

Government decrees found on the official portal publication.pravo.gov.ru (search by title on 2026-11-19):

| Product group | Decree | Portal number |
|---|---|---|
| Tobacco | Постановление Правительства РФ от 28.02.2019 № 224 «Об утверждении Правил маркировки табачной продукции средствами идентификации…» | 0001201903060010 |
| Footwear | Постановление Правительства РФ от 05.07.2019 № 860 «Об утверждении Правил маркировки обувных товаров средствами идентификации…» | 0001201907080024 |
| Dairy | Постановление Правительства РФ от 15.12.2020 № 2099 «Об утверждении Правил маркировки молочной продукции средствами идентификации…» | 0001202012180005 |
| Veterinary medicines | Постановление Правительства РФ от 27.05.2024 № 675 | 0001202405310045 |

Amendments seen: tobacco № 64 of 28.01.2021, dairy № 1733 of 30.09.2022 and № 1208 of 25.07.2023, footwear № 676 of
14.05.2020 and № 1459 of 01.09.2021. The remaining groups (medicines, water, beer, tyres, apparel, …) are Task 8.

The operator's knowledge base on markirovka.ru ("Официальный сайт сообщества маркировки «Честный знак»") publishes
"Состав кода маркировки" per group. Examples read:

- Polymer pipes (2026-08-06), citing paragraph 43 of the group's methodological recommendations: the long structure
  "Состоит из 76 символов. Включает в себя 4 группы данных, идентифицирующихся атрибутами (идентификаторами
  применения - AI)" — (01) 14 digits, (21) 6 characters, (91) 4 characters, (92) 44 characters, each variable group
  ending with "символом-разделителем FNC1 (ASCII 29)"; a short structure with (93) exists.
- Tobacco, МОТП/АТП (2026-02-10): the identification code (КИ) used in documents "должен состоять из 25 символов.
  Состав: 01 … + GTIN … + 21 … + серийный номер (7 символов)".
- Dairy (2026-02-06): "КИ должен состоять из 24 символов", serial of 6 characters.

## Unreachable or deferred

| Source | Date (UTC) | What was tried | Effect |
|---|---|---|---|
| EUR-Lex (2016/161, 2018/574) | 2026-11-19 | HTML, PDF and ELI URLs: HTTP 202, empty body | text read on legislation.gov.uk; retry in Task 8 |
| ICCBBA ST-011 v1.9.0 | 2026-11-19 | the registry page linking it is on a host outside the sandbox allowlist | ST-017 covers the UDI-DI structure; Task 7 |
| GS1 prefix allocation list (MO per prefix) | 2026-11-19 | not fetched | `Gs1Prefix` decodes the General Specifications tables only; Member Organisations are not named |
