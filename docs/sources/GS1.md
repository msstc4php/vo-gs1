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
| [EPC Tag Data Standard](https://ref.gs1.org/standards/tds/) | Release 2.3, Ratified, Oct 2025 | 2026-11-19 | read (PDF, 448 pages) |
| [ANSI/HIBC 2.6 Supplier Labeling Standard](https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf) | ANSI/HIBC 2.6-2016 (R2026) | 2026-11-19 | read (PDF) |
| [HIBCC Mod 43 Check Character](https://www.hibcc.org/wp-content/uploads/Mod-43-Check-Character.pdf) | Appendix B of ANSI/HIBC 2.5-2015 | 2026-11-19 | read; same text as 2.6 Appendix B |
| [HIBC Basic UDI-DI](https://www.hibcc.org/wp-content/uploads/HIBCC-Basic-UDI-DI.pdf) | undated | 2026-11-19 | read (PDF) |
| [ANSI/HIBC 2.3-2009 (Internet Archive copy)](https://web.archive.org/web/20130203212925/http://www.hibcc.org/wp-content/uploads/2012/09/ANSI-HIBC-2.3-SLS-2009.pdf) | 2009, capture 2013-02-03 | 2026-11-19 | read (PDF): legacy quantity layout only |
| [ICCBBA ST-017, Coding and Labeling of Medical Devices Containing MPHO](https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf) | v1.0.1, September 2024 | 2026-11-19 | read (PDF): ISBT 128 is reachable |
| [21 CFR 207.33 (eCFR, current)](https://www.ecfr.gov/current/title-21/chapter-I/subchapter-C/part-207/subpart-B/section-207.33) | current text | 2026-11-19 | read |
| [FDA final rule, 91 FR 10749, doc 2026-04368](https://www.federalregister.gov/documents/2026/03/05/2026-04368/revising-the-national-drug-code-format-and-drug-label-barcode-requirements) | published 2026-03-05, effective 2033-03-07 | 2026-11-19 | read (full text) |
| Delegated Regulation (EU) 2016/161 | OJ L 32, 9.2.2016 | 2026-11-19 | EUR-Lex unreachable (retried in Task 8); text read on legislation.gov.uk |
| [IFA PPN-Code Specification for Retail Packaging](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA_Spec_PPN_Code_Handelspackung_EN.pdf) | Version 3.06, 6 November 2023 | 2026-11-19 | read (PDF, 44 pages; section 8) |
| [IFA Coding System for medical devices (UDI specification)](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Spec_UDI_Code_EN.pdf) | Version 2.5, 1 April 2026 | 2026-11-19 | read (PDF), section 3.5 |
| [IFA Check Digit Calculations of PZN, PPN and Basic UDI-DI](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Check_Digit_Calculations_PZN_PPN_UDI_EN.pdf) | undated | 2026-11-19 | read (PDF) |
| Implementing Regulation (EU) 2018/574 | OJ L 96, 16.4.2018 | 2026-11-19 | EUR-Lex unreachable (retried in Task 8); Articles 2, 3, 8, 21 read on legislation.gov.uk |
| Russian Government decrees per product group | see section 9 | 2026-11-19 | No. 224 read on a mirror; the others cited through the operator's pages |
| [Operator knowledge base (ЦРПТ, «Честный знак»)](https://markirovka.ru/knowledge/tovarnye-gruppy/) | pages dated 2024-2026 | 2026-11-19 | read: «Состав кода маркировки» of 36 groups (section 9) |

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
| `nozeroprefix` | enforced | "ensures that the given data does not start with a zero", except the single digit `0`: GenSpecs 26.0 section 3.9.11 (AI 8011, its only user) "The C/P serial number SHALL NOT begin with a "0" digit, unless the entire serial number consists of the single digit "0"." GenSpecs is normative; `lint_nozeroprefix.c` refuses `0` (its test vector `UNIT_TEST_FAIL(…, "0", …)`) |
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

## 3. GS1 Digital Link URI Syntax, Release 1.7.0 (Task 6) — `DigitalLink\DigitalLink`

Read from the HTML edition of Release 1.7.0, Ratified Aug 2026. The parser follows the GS1 Barcode Syntax Engine
(`src/c-lib/dl.c`, `gs1_parseDLuri()`, commit `58c1dc8`), GS1's reference implementation, where the standard leaves
the procedure open.

**Compression.** It is **not** defined in the URI Syntax any more. Section 1 points to a separate standard:

> This document [ DL-CompressionEPC ] explains how GS1 identifiers in EPC binary syntax can be expressed as a special
> compressed form of a GS1 Digital Link URI … This specification supersedes the compression/decompression algorithm
> defined in the legacy GS1 Digital Link Standard [ DL-Legacy ].

Section 6.1.2:

> Compressed GS1 Digital Link URIs that use the legacy specification are not expected to be used in any future
> Application Standards and are not approved for use in open supply chain standards.

So compressed links fall outside the edition and are refused. RE2 and RE3 of section 6.1.2 (`/[0-9A-Za-z_-]{10,}$`,
`/(eh[0-9a-f]*|ex[a-zA-Z0-9_-]*)$`) only choose the message ("is compressed") when no primary key is found; an
all-digit last segment does not count, because the standard warns that RE2 also matches non-compressed URLs.

**Legacy forms.** Section 4.1:

> As of version 1.4.0 , the GS1 Digital Link standard: URI syntax now expects the GTIN value to be expressed using 14
> digits

> For reasons of backwards compatibility, only existing infrastructure for GS1 Digital Link SHOULD continue to support
> legacy expressions of GS1 Digital Link URIs. Legacy expressions of GS1 Digital Link URIs include GTIN values with
> fewer than 14 digits and the deprecated convenience alphas.

Both legacy expressions are accepted (controller ruling: still in circulation), the value stays as given:

- A zero-suppressed GTIN (8, 12 or 13 digits) is padded to 14 digits in the element string for the AIs whose value is
  a GTIN: (01) GTIN, (02) CONTENT, (03) MTO GTIN (`N14,csum,gcppos2`, titled GTIN in the dictionary). The engine's
  `permitZeroSuppressedGTINinDLuris` pads (01) only. Not padded: (8006) ITIP and (8026) ITIP CONTENT, whose value is
  a GTIN followed by piece and total (`itip-value = 14DIGIT 2DIGIT 2DIGIT`), not a "GTIN value" in the sense of 4.1.
- Convenience alphas in the path map to their AIs by the engine's `alpha_ai_map` (`src/c-lib/dl.c`, commit `58c1dc8`):
  `cpid` 8010, `cpsn` 8011, `cpv` 22, `gcn` 255, `gdti` 253, `giai` 8004, `ginc` 401, `gln` 414, `glnx` 254, `gmn`
  8013, `grai` 8003, `gsin` 402, `gsrn` 8018, `gsrnp` 8017, `gtin` 01, `itip` 8006, `lot` 10, `party` 417, `refno`
  8020, `ser` 21, `srin` 8019, `sscc` 00. As in the engine, the query takes numeric AI keys only (an alpha key there
  is an extension parameter). Section 4.1: "Convenience alphas were marked as deprecated in version 1.2 of the
  standard and have been removed completely as of version 1.3.0." 

**Path.** Section 4.3 lists the primary keys, 4.4 the key qualifiers, 4.9 their order:

> The square bracket notation indicates that the enclosed key qualifier component may be omitted but the sequence in
> which they appear is important and must be preserved.

> `gtin-path = gtin-comp [cpv-comp] [lot-comp] [ser-comp]`

The keys and qualifier sequences come from the dictionary's `dlpkey` attribute (`dlpkey=22,10,21|235` for AI 01), so
they follow the AI table release. Any ordered subset of one sequence is accepted, as the engine's
`gs1_populateDLkeyQualifiers()` builds it. Section 4.11:

> Note that zero or more path segments are permitted to appear after the hostname or domain name and before the start
> of the gs1uriPattern defined in this specification.

The primary key is the last `/AI/value` pair of the path whose AI is a primary key, scanning back from the end over
known AIs (engine). A trailing slash is tolerated (section 4.12):

> a resolver should tolerate https://example.com/8003/0{grai}/ even though that final / character is not allowed by the
> formal ABNF.

**Percent-encoding.** Section 4.2: "The following characters must be represented using percent-encoding (see section
2.1 of RFC 3986 …) when used as literal characters within URIs" (`#`, `/`, `%`, `&`, `+`, `,`, `!`, `(`, `)`, `*`,
`'`, `:`, `;`, `<`, `=`, `>`, `?`). Values are percent-decoded; in the query `+` is a space (engine
`URIunescape()`). Writing encodes every character but the RFC 3986 unreserved ones (engine `URIescape()`).

**Query.** Section 4.10:

> Data attributes and their values SHALL be expressed via the URI query string as key=value pairs.

Only AIs with the dictionary flag `?` ("AI that is permitted as a GS1 Digital Link data attribute") are accepted
there. An attribute that would form a valid path with the qualifiers present is refused ("AI_SHOULD_BE_IN_PATH_INFO"
in the engine). Section 4.10.1:

> Any key=value pairs used for extension data SHALL NOT use all-numeric keys to avoid conflict with existing and
> future keys used for GS1 Application Identifiers

so an all-numeric key that is no AI is refused, with or without `=`; every other parameter is an extension and is
kept as given (compared raw: `x=%41` and `x=A` differ). The delimiters are `queryStringDelim = "&" / ";"` (section
4.11). Section 3:

> It should be noted that no key=value pair should be repeated with the same primary identification key in the URI
> query string. If a primary identification key is repeated, the last defined value for that primary identification
> key takes precedence over any previously defined value.

So a repeated data attribute is valid and its last value counts (controller ruling; the engine refuses it as
`DUPLICATE_AI`). A query AI that repeats a path AI is refused: the path and the query would give one AI two values.

**Canonical URI.** Section 4.12: "the scheme SHALL be HTTPS; the domain name SHALL be id.gs1.org; … GTIN-8, GTIN-12
and GTIN-13 SHALL remain padded to 14 digits; the URI query string (if present) SHALL NOT contain any other key=value
pairs except for keys that are GS1 Application Identifiers; key=value pairs, if present, should be sorted in lexical,
not numeric, order of the key" and "A canonical GS1 Digital Link URI SHALL NOT include a trailing slash."
`toCanonical()` builds it. The standard defines no normal form for other URIs; section 5.7 calls URIs whose attributes
differ only in order "equivalent", which is what `equals()` compares (scheme and host case-insensitive, RFC 3986
section 6.2.2.1; userinfo, path and non-GS1 parameters as given).

**Examples.** Section 5 (5.1-5.11) gives the test values, URI and element string both ways. Section 5.5 lists the
element string as `(01)…(17)180426(10)ABC1(21)12345`; `toElementString()` keeps path then query order, so it gives
`(01)…(10)ABC1(21)12345(17)180426` (the same data; element string equality is order-sensitive).

## 4. EPC Tag Data Standard, Release 2.3 (Task 6) — `Epc\`

`https://ref.gs1.org/standards/tds/` serves "EPC Tag Data Standard TDS … Release 2.3, Ratified, Oct 2025" (its log:
"Introduction of twelve new ‘++’ EPC schemes, SGTIN++ and DSGTIN++ …"). Read from the PDF (448 pages).

**Schemes.** Section 6.3, `EPC-URI = SGTIN-URI / SSCC-URI / SGLN-URI / GRAI-URI / GIAI-URI / GSRN-URI / GDTI-URI /
CPI-URI / SGCN-URI / GINC-URI / GSIN-URI / ITIP-URI / UPUI-URI / PGLN-URI / GID-URI / DOD-URI / ADI-URI / BIC-URI /
IMOVN-URI`, plus `gsrnp` (Figure 6-1 and 6.3.7): `Epc\EpcScheme`, twenty cases. "Note that no new Pure Identity EPC
URI formats are defined for the new EPC schemes and binary encodings introduced in TDS 2.0." The EPC Class URI
(`urn:epc:class:lgtin:`, 6.4) is not a pure identity URI and is not covered.

**Grammar.** Section 5:

> GS3A3Char = Digit / UpperAlpha / LowerAlpha / OtherChar / Escape

> CPRefChar = Digit / UpperAlpha / "-" / "%2F" / "%23"

with `OtherChar = "!" / "'" / "(" / ")" / "*" / "+" / "," / "-" / "." / ":" / ";" / "=" / "_"` and
`Escape = "%" HexChar HexChar`. Every scheme's rule is taken from its subsection of 6.3, with the digit totals stated
there, for example 6.3.1: "The number of characters in the two PaddedNumericComponent fields must total 13" (SSCC,
GSRN, GSRNP 17; SGLN, GRAI, GDTI, SGCN, PGLN 12; GSIN 16; ITIP 13 plus two 2-digit fields). `%s"urn:epc:id:"` is
case-sensitive.

**Company prefix.** Section 7.1:

> The GS1 Company Prefix (GCP) is a 4- to 12-digit number … In the EPC URI, the GCP is encoded in the CompanyPrefix
> component, which SHALL include the 4- or 5-digit GCP and the following 2 or 1 digits of the GS1 key, as though it
> were a 6-digit GCP.

So the CompanyPrefix component of the schemes with a GS1 key has 6 to 12 digits. GTIN-8 (7.3.2): "the GS1 Company
Prefix portion of the EPC is eight digits and shall be 00000N1N2N3". Not enforced: 7.3.3/7.3.4 forbid SGTINs from
RCN-8 and company internal numbering (prefixes `04`, `0001`-`0007`); telling those apart needs the GS1 prefix ranges
applied to the EPC form and is left out.

**Element strings.** Section 7 (7.3-7.17) gives each correspondence and its example, used as test values: SGTIN
`(01)`+`(21)`, SSCC `(00)`, SGLN `(414)` with `(254)` unless the extension is `0` ("If the entire GLN Extension is just
a single zero digit, it indicates that the SGLN stands for a GLN, without an extension"), GRAI `(8003)` with the extra
`0`, GIAI `(8004)`, GSRN `(8018)`, GSRNP `(8017)`, GDTI `(253)`, CPI `(8010)`+`(8011)`, SGCN `(255)`, GINC `(401)`, GSIN
`(402)`, ITIP `(8006)`+`(21)`, UPUI `(01)`+`(235)`, PGLN `(417)`. The check digit is computed (7.3 step 3). Percent
escapes map to characters by Table A-1, whose "URI Form" escapes `"`, `%`, `&`, `/`, `<`, `>`, `?`. A URI whose data
gives no valid element string (a serial of 21 characters, a piece above its total) is refused.

**Equality.** Section 6.1:

> the question may be answered simply by comparing the full EPC URI strings as specified in [RFC3986], Section 6.2. In
> most cases, the "simple string comparison" method suffices, though if a URI contains percent-encoding triplets the
> hexadecimal digits may require case normalisation as described in [RFC3986], Section 6.2.2.1.

The canonical value upper-cases the hex digits of escapes and changes nothing else.

**Tag URI.** Section 12.4:

> TagURI = %s"urn:epc:tag:" TagURIControlBody
> TagURIControlBody = 0*1( ControlField+ ":" ) TagURIBody

with the encoding names `sgtin-96`, `sgtin-198`, `sscc-96`, `sgln-96`, `sgln-195`, `grai-96`, `grai-170`, `giai-96`,
`giai-202`, `gsrn-96`, `gsrnp-96`, `gdti-96`, `gdti-113`, `gdti-174`, `cpi-96`, `cpi-var`, `sgcn-96`, `itip-110`,
`itip-212`, `gid-96`, `usdod-96`, `adi-var` (`Epc\EpcTagEncoding`). The grammar defines `GSRNPEncName` but omits a
`GSRNPTagURIBody`; Table 12-2 and the GSRNP-96 coding table (14.6.7) define the tag form, so it is accepted like
GSRN-96. Control fields: `ControlName = %s"att" / %s"umi" / %s"xpc"`, `ControlValue = BinaryControlValue /
HexControlValue`, and section 12.2.2: "any control information fields that appear in a URI must occur in alphabetical
order".

**Filter.** Section 12.2.1: "the filter value is indicated as an additional field following the scheme name and
preceding the remainder of the EPC"; 10.1: "Implementations of the encoding and decoding rules specified herein SHALL
accept any value of the filter values, whether reserved or not." So any value that fits the field is accepted: 3 bits
(0-7) in every coding table, 6 bits (0-63) for ADI-var (Table 10-11); GID-96 has none ("The GID EPC scheme does not
provide for the use of filter values", 10.12). USDOD-96: "Filter values for US DoD EPC Tags are as specified in
[USDOD]" — format only (a number without leading zeros, at most 18 digits so it fits an int).

**Partition tables** (14.6, "SGTIN Partition Table" and the tables of the other schemes): partition values 0-6 map to
GS1 Company Prefix lengths 12-6, for example

| Partition Value (P) | GS1 Company Prefix Digits (L) | Indicator/Pad Digit and Item Reference Digits |
|---|---|---|
| 0 | 12 | 1 |
| 6 | 6 | 7 |

A prefix length outside 6-12 has no partition and is refused (it is refused in the pure identity URI as well, by 7.1).
`partition()` is `12 - L`.

**Field limits** (Table 12-2 "EPC Binary Coding Schemes and their limitations" and the coding tables):

| Encoding | Rule as quoted |
|---|---|
| `sgtin-96`, `grai-96`, `itip-110` | "Numeric-only, no leading zeros, decimal value must be less than 2^38" |
| `sgln-96`, `gdti-96` | "Numeric-only, no leading zeros, decimal value must be less than 2^41" |
| `gdti-113` | "up to 17 decimal digits, with or without leading zeros" |
| `giai-96` | "GIAI-96 Partition Table": P 0-6 gives Max Digits (K) 13-19 and Bits (N) 42, 45, 48, 52, 55, 58, 62 |
| `cpi-96` | "CPI-96 Partition Table": Maximum Digits 3-9 for P 0-6; serial "less than 2^31"; within those digits the reference is always below its 11-31 bits |
| `gid-96` | GID-96 coding table: 28, 24 and 36 bits for manager number, object class and serial |
| `adi-var` | "The number of characters in the Part Number segment must be greater than or equal to zero and less than or equal to 32"; serial "greater than or equal to one and less than or equal to 30" |

The long encodings (`sgtin-198`, `sgln-195`, `grai-170`, `giai-202`, `gdti-174`, `cpi-var`, `itip-212`) allow
"All values permitted by GS1 General Specifications", which the pure identity URI already checks through its element
string. Binary and hex tag encodings and the EPC Raw URI are out of scope.

**Examples.** Section 6.3 and Annex E: every scheme and every encoding has a test value from them where TDS gives one
(`gdti-113` has none; its test reuses the GDTI example of 7.10).

## 5. HIBCC (Task 7) — `Internal\Mod43`, `Udi\HibcUdi`, `Udi\BasicUdiDi\HibccBasicUdiDi`

ANSI/HIBC 2.6-2016, reaffirmed 2026 (cover: "ANSI/HIBC 2.6 – 2016 (R2026)"), Appendix B.2.0:

> The Check Character is the Modulo 43 sum of all the character values in a given message and is printed as the last
> character in a given message, preceding the Stop Character.

> Sum of values: 41+10+1+2+3+11+19+12+5+13+6+14+7+1 = 145

> Divide 145 by 43. The quotient is 3 with a remainder of 16. The Check Character is the character corresponding to
> the value of the remainder (see table below), which in this example is 16, or “G”.

Table B1: `0`-`9` = 0-9, `A`-`Z` = 10-35, `-` = 36, `.` = 37, Sp = 38, `$` = 39, `/` = 40, `+` = 41, `%` = 42. B.2.1:
the check character may be a space. The stand-alone "Mod 43 Check Character" PDF on hibcc.org is the same appendix in
its 2.5-2015 edition.

### Primary data structure (section 2.1.1, Table 1) — `HibcUdi`

> The HIBC LIC Primary Data Structure format encodes a “+” data identifier of the HIBC Supplier Data Structure, a
> 4-character Labeler Identification Code (LIC), a 1 to 18 character Product or Catalog Number (PCN), a one-digit Unit
> of Measure Identifier (U/M), and a single Check Character (C).

LIC: "a 4-character alphanumeric identifier, with the first character always being alphabetic". PCN: "The allowed
characters are A through Z and 0 through 9." U/M: "Numeric value only, 0 through 9". Section 2.1 titles the structure
"Primary Data Structure (Device Identifier)": `HibcUdi::deviceIdentifier()` is the primary structure with its check
character. Examples: `+A123BJC5D6E71G` (4.3.1), `+H123ABC01234567890D` (Appendix C).

### Secondary data structure (section 2.2.1, Table 2, Appendices E and F)

Table 2: `+`, the reference identifier R ("1, 2, 3, or 5"), the expiry date field D ("0 or 4-9"), lot or serial B
("0-18", alphanumeric), link character L, check character C.

> Note 1: The HIBC Secondary Data Structure is distinguished from the Primary Data Structure in that the Primary Data
> Structure has an alphabetic character following the HIBC Supplier Labeling Flag Character “+”, while the Secondary
> Data Structure has a numeric character or a “$” following the HIBC Supplier Labeling Flag Character.

Appendix E1.1, the date format flag after `$$` or `$$+`: "0, 1 First digit of month in MMYY (month/year) Date format",
"2 MMDDYY", "3 YYMMDD", "4 YYMMDDHH (year/month/day/hour G.M.T.)", "5 YYJJJ (year/Julian day)", "6 YYJJJHH",
"7 Date Field is null, Lot Field follows". `$` + alphanumeric: lot only ("This format is included for backward
compatibility only"); `$+` + alphanumeric: serial only. A leading digit: the five-digit Julian date, and Note 1 of
Table F1:

> Software that interprets encoded HIBCC secondary data fields should allow lot/batch data following the fixed-length
> numeric Julian date.

("optional variable length (0 to 13)"). Section 3.0: "The use of the special characters “.” and “-“ are permitted in
the Lot and Serial fields". Appendix A: Julian dating is the day of the year, February 29 being 060 in a leap year.
Table F1's example data (lot `3C001`, serial `0001`, expiry 28 September 2005, 22:00) is the test table.

**Legacy quantity (`$$8`, `$$9`).** Table 2, Note 3: "Quantity is no longer included in the Secondary Data
Structure as it had been in previous versions of the standard". Appendix H:

> Among these is the use of quantity in the secondary data structure. […] Existing labels are still valid but should
> not be used for Unique Device Identification (UDI).

So such labels are accepted (never refuse a valid value). The layout is the one of ANSI/HIBC 2.3-2009, read in an
unmodified Internet Archive copy of HIBCC's own PDF:
<https://web.archive.org/web/20130203212925/http://www.hibcc.org/wp-content/uploads/2012/09/ANSI-HIBC-2.3-SLS-2009.pdf>
(capture 2013-02-03 21:29:25 UTC; fetched 2026-11-19 UTC; hibcc.org itself no longer serves 2.3-2.5). Appendix E1.1:

> The digits 8 and 9 specify the Quantity Field format. The first digit following the Quantity Field should be 0
> through 7 to define the Date Field format as defined above.

> 8 Two digit Quantity Field follows / 9 Five digit Quantity Field follows

> If the Secondary Code specifies only the Quantity, both the Date Field and the Lot/Batch/Serial Number Field should
> be null

Examples (primary `+A123BJC5D6E71G`, link `G`): `+$$872404121523G2` ("Quantity is 72 and Date is 12/15/04 23:00
G.M.T."), `+$$9014407A1234G8` ("Quantity is 1440 and Lot # is A1234"), `+$$848GQ` ("Quantity is 48"); Table F1 rows
`+$$824LC`, `+$$900100LC`. E1.1 applies the flag after "$$" or "$$+", so `$$+8`/`$$+9` are read too.

**Package decisions (not in the standard):** a two-digit year is 20YY (no century rule is given; a fixed rule keeps a
stored value's meaning independent of the clock); an MMYY date is the last day of that month (as GS1's `DD = 00`);
dates are UTC, with the hour of the HH formats ("G.M.T."), otherwise 00:00.

### Concatenation (section 2.2.1.1) — `HibcUdi::fromSymbols()`

> When combining the Primary and Secondary Code into a single symbol (known as concatenation), a forward slash (/) is
> used as a delimiter between the primary and secondary data. In addition, the primary data Link Character, the plus
> (+) at the start of the secondary data, and the secondary data Link Character are omitted. Only one Check Character
> at the end of the symbol will be used which will check the entire data string.

Appendix E1.3: "The Link Character for the Secondary Data Structure is the last character from the Primary Data String
in the Primary Symbol (Check Character)." Examples: `+A99912345/$$52001510X33` (2.2.1.1), secondary symbol
`+$$52001510X3GD` of primary `+A123BJC5D6E71G` (4.3.2), concatenated `+A123BJC5D6E71/$$52001510X3C` (4.3.3). In a
separate secondary symbol with supplemental data the standard does not place the link character: Table 2 puts `L`
right after the lot/serial field B, and section 2.3.1 says "the check character will be inserted at the end of the
total string". Both readings are accepted — `L` before the first `/` or `L` second to last; when both give valid data
the symbol is refused as ambiguous rather than guessed.

### Supplemental data (section 2.3)

> The Secondary Supplemental Data field is constructed with a “/” character followed by a Data Identifier (DI),
> followed by data. Multiple Secondary Supplemental data fields are possible.

DIs: `S` serial ("an1 + an18", 2.3.2.1, headed "Serial number when Lot number is used"; the standard has no sentence
forbidding `/S` beside a serial secondary, so it is accepted, and it does not say which serial wins when both are
present: `serial()` returns the secondary's, `supplementalSerial()` the `/S` one), `16D` date of manufacture ("an3+n8 … (YYYYMMDD)"), `14D` expiry
("an3+n8 … (YYYYMMDD)"), `Q` quantity ("an1+n1...n5"); "Quantity must be the last field in supplemental data". Other
DIs are refused (the standard names no others). Examples: `+A99912345/$$52001510X3/16D20111212/S77DEFG457`,
`+A99912345/$10X3/16D20111231/14D202001313`, `+A99912349/$10X3/16D20111231/14D20200131/Q500Z`.

### HIBC Basic UDI-DI — `HibccBasicUdiDi`

HIBC Basic UDI-DI (undated PDF on hibcc.org): structure `++` + LIC (4, "the first character always being
alphabetical") + Global Model Identifier (1-17, "Variable Length Alphanumeric") + check characters (2), example
`++A999MODELIDENTIFIER11S8`.

> The HIBCC keying check character scheme utilizes Modulo 1021 arithmetic to calculate the check value and then two
> characters from a 32-character set to represent the check value.

The weights are the primes from 2 on the character next to the pair, leftwards, over the whole string including `++`
(the worked example ends `+ 8*79 + 8*83`); Table 1 equals GS1 CSET 82 (`+` = 8, `1` = 14) and Table 2 equals CSET 32,
so `GmnCheckCharacters` computes it: `++A999MODELIDENTIFIER11` → `S8` (sum 16089, value 774). The Global Model
Identifier is accepted in both cases: Table 1 assigns values to small letters too.

## 6. ICCBBA (ISBT 128) (Task 7) — `Udi\IsbtUdi`, `Udi\BasicUdiDi\IccbbaBasicUdiDi`

Reachable: [ST-017 v1.0.1](https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf)
(September 2024), re-read on 2026-11-19 (UTC). ST-011 was not needed: ST-017 defines every structure the UDI uses.

| Data structure | ST-017 | Structure | Enforced |
|---|---|---|---|
| Processor Product Identification Code [034] (UDI-DI) | 2.1 | `=/nnnnnppppppqqqqq`: `nnnnn` "{A-N, P-Z, 0-9}", `pppppp` and `qqqqq` "{A-Z, 0-9}" | yes |
| Donation Identification Number [001] | 3.1 | `=αppppyynnnnnnff`: α "{A–N; P–Z; 1–9}", pppp "First two characters alphanumeric {A-N, P-Z, 0-9}; second two characters numeric", yy and nnnnnn numeric, ff "{0–9}, {A-H, J-N, P, R-Y}" | yes |
| Product Divisions [032] (serial) | 3.2 | `=,dddddd`, "{A-Z, 0-9}" | yes |
| Expiration Date [004] | 3.3 | `=>cyyjjj`, numeric | yes, and the day must exist |
| Production Date [008] | 3.4 | `=}cyyjjj`, numeric | yes, and the day must exist |
| MPHO Lot Number [035] | 3.5 | `&,1` + "up to 18 characters" {A–Z; 0–9} | yes |
| Compound Message [023] | 4.1 | `=+aabbb`: "aa shall specify the number of ISBT 128 data structures that follow" | count checked |

Rules quoted from 4.1 and chapter 5:

> To satisfy medical device regulations, the compound message shall always begin with the Processor Product
> Identification Code [Data Structure 034] (the DI). In addition, to meet traceability requirements, the Product
> Divisions [Data Structure 032] and Donation Identification Number [Data Structure 001] shall also be present.

> If non-UDI data structures are included in the compound message, they must appear after the PIs.

> The software should always verify the integrity of the data string, including checking that the correct number of
> data structures appears and, when specified sequence messages are used, that the sequence of data structures is
> correct.

"Elements of the message are divided either by the “=” or the “&” character." Specified sequences 034-037 (Table 3)
are checked; other RT017 numbers are format only (RT017 is published in ICCBBA's password-protected area). Non-UDI
data structures are kept unparsed (printable ASCII, at least three characters). The DIN "flag characters … should be
ignored" (chapter 5) and "shall be set to 00" for medical devices (3.1): reading software ignores them, so any flag of
the structure's character set is accepted and `donationIdentificationNumber()` returns the first 13 characters.

Dates: "These are presented in a YYYJJJ format where the first three characters form a three-digit year … Thus, 019032
refers to 1 Feb 2019": the year is 2000 + `cyy`. Expiration: "the date at the end of which the item expires".

Value: the human-readable concatenation (6.2.2: the compound message identifier and message code are "Characters NOT to
print in human readable UDI text"), so `=+04000…` and its HRI are one value. Examples: chapter 5
`=+06000=/A9999XYZ100T0476=,000025=A99971712345600=>019032=}017032&,1000000000000XYZ123`, 4.3.1
`=+04000=/A9997XYZ100T0479=A99991712345600=,000012=>019031`, 4.3.2 `=+04035=/A9997XYZ100T0479=,000012=A99991712345600=>019031`.

Appendix 1 (Basic UDI-DI): "The Basic UDI-DI format is identical to the UDI-DI format with the exception that it
carries an additional two digits that form a mod 37-2 checksum", structure `nnnnnppppppqqqqqbb`. Checksum steps: the
ISO 7064 value of each of the first sixteen characters ("character F has value 15"), times "the nth power of 2 where n
is the position of the character from the right-hand end", summed, "Find the modulus 37 value", "Subtract … from 38",
"Find the modulus 37 value", "If the value from step six is a single digit add a leading zero". Example
`A9990ABC123T0480` → sum 1211040 → `08`. Table 7 (RT061) is an image; the values used are ISO/IEC 7064's (`0`-`9`,
`A`-`Z` = 10-35), consistent with the example (`A` = 10, `T` = 29). The EU requirement quoted there: "the Basic UDI-DI
code value shall have maximum 25 characters".

## 7. FDA NDC (Task 7) — `Udi\Ndc`, `Udi\Ndc12`

21 CFR 207.33(b), current eCFR text (versioner API, issue date 2026-11-17; with the cross-reference "Link to an
amendment published at 91 FR 10770, Mar. 5, 2026"):

> (1) Except as described in paragraph (b)(4) of this section, the NDC must consist of 10 or 11 digits, divided into
> three segments

> (i) If a labeler code is either 5 or 6 digits in length, it may be combined with: (A) A product code consisting of 4
> digits and a package code consisting of 1 digit for a total NDC length of 10 or 11 digits (5-4-1 or 6-4-1), or (B) A
> product code consisting of 3 digits and a package code consisting of 2 digits for a total NDC length of 10 or 11
> digits (5-3-2 or 6-3-2).

> (ii) If a labeler code is 4 digits in length, it may be combined only with a product code consisting of 4 digits and
> a package code consisting of 2 digits for a total NDC length of 10 digits (4-4-2).

`Ndc` accepts all five configurations. The (b)(4) "alternatively formatted NDC" for certain HCT/Ps has no published
format and is not accepted.

Final rule "Revising the National Drug Code Format and Drug Label Barcode Requirements", 91 FR 10749, published
2026-03-05, effective 2033-03-07 (Federal Register API: `effective_on` 2033-03-07). Amended 207.33(b)(1):

> the NDC must consist of 12 digits, divided into three segments as follows: (i) The first segment of the NDC is the
> labeler code and consists of 6 digits.

with "(ii) … product code and consists of 4 digits" and "(iii) … package code and consists of 2 digits" (`Ndc12`).

Why the hyphens are required (Response 8 and Table 2, "Illustration of Ambiguity With Unhyphenated NDC":
`9999912345` → `9999-9123-45`, `99999-123-45`, `99999-1234-5`):

> if hyphens are removed from an FDA-assigned NDC in an electronic system, there is no reliable way to know where the
> hyphens were in the original NDC.

The 11-digit billing form (`Ndc::toBillingFormat()`) is HHS's HIPAA standard, which this FDA rule describes (the HHS
source itself, 65 FR 50312, was not fetched):

> The HIPAA standard 11-digit NDC format is standardized such that the labeler code is always 5 digits, the product
> code is always 4 digits, and the package code always 2 digits (i.e., 5-4-2 configuration). To convert a 10-digit NDC
> to an 11-digit HIPAA standard NDC, a leading zero is added to the appropriate segment to create the 11-digit
> configuration as defined above.

Table 1: `10010-001-01` → `10010-0001-01` → `10010000101`; for a 6-digit labeler code ("Native 11-digit (6-3-2)")
the rule notes the HIPAA update is "outside the scope of FDA authority", so `toBillingFormat()` returns null for it.

`Ndc::toNdc12()`, Response 14: existing NDCs "would be converted to the uniform, 12-digit format by the addition of
leading zeros to the labeler code, the product code, and/or the package code segments as needed to produce the 6-4-2
format"; footnote 1: "FDA considers such an NDC in its original 10-digit format and in its converted 12-digit format
to be the same NDC with different formats." The quote covers 10-digit NDCs; the 6-digit configurations are padded the
same way.

## IFA Basic UDI-DI (Task 7) — `Udi\BasicUdiDi\IfaBasicUdiDi`, `Internal\IfaMod97`

[IFA Coding System for medical devices](https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Spec_UDI_Code_EN.pdf),
Version 2.5 (1 April 2026), section 3.5 and Figure 9 ("Specifications of the BUDI"): IAC `PP` (2), manufacturer code
(5, data type "A/Num", character set `0–9`, footnote 14: "At present, exclusively numeric manufacturer codes are
assigned"), Device Group Code (1 … 16, "0 – 9; A – Z; “.”"), check digits (2, "Modulo 97"); "String length: 10 ... 25".

> Finally, the two-digit check digit is added, calculated over the complete data field including the "PP" according
> to Modulo 97.

The calculation (Check Digit Calculations of PZN, PPN and Basic UDI-DI, see section 8): ASCII values weighted from 2
on the left, sum modulo 97, two digits. Example: `PP12345ABCD.12345678.90` → sum 16203, remainder 4 → "the complete
Basic UDI-DI: PP12345ABCD.12345678.9004". The manufacturer code is accepted alphanumeric (the data type), so a future
alphanumeric code is not refused.

### Basic UDI-DI per issuing entity — `BasicUdiDiFactory`

| Entity | Class | Check characters |
|---|---|---|
| GS1 | `Gs1BasicUdiDi` (the GMN, section 1) | MOD 1021,32 pair |
| HIBCC | `HibccBasicUdiDi` | MOD 1021,32 pair over `++…` |
| ICCBBA | `IccbbaBasicUdiDi` | ISO 7064 MOD 37-2, two digits |
| IFA | `IfaBasicUdiDi` | modulo 97 over ASCII, two digits |

All four are enforced with their check characters. The factory tries all four; a value valid for several (e.g.
`PP1234500000007005`, both ICCBBA and IFA) throws `AmbiguousValueException`.

## 8. EU: falsified medicines and tobacco (Task 8) — `Marking\EU\`

### EUR-Lex

EUR-Lex answered with an empty body again on 2026-11-19 (UTC), Task 8 retry: `eur-lex.europa.eu/legal-content/EN/TXT/HTML/?uri=CELEX:32018R0574`
and `eur-lex.europa.eu/eli/reg_del/2016/161/oj`. The `@see` links therefore stay on legislation.gov.uk, "Original (As
adopted by EU)": <https://www.legislation.gov.uk/eur/2016/161/article/4/adopted>, `/eur/2016/161/article/5/adopted`,
<https://www.legislation.gov.uk/eur/2018/574/article/8/adopted>, `/article/2/adopted`, `/article/3/adopted`,
`/article/21/adopted`.

### Delegated Regulation (EU) 2016/161 — `FmdPackCode`

Article 4(b): the unique identifier consists of the product code, "a numeric or alphanumeric sequence of maximum 20
characters, generated by a deterministic or a non-deterministic randomisation algorithm (‘serial number’)", a national
reimbursement number if required, the batch number and the expiry date. Article 5(5): the product code "shall be less
than 50 characters and be globally unique". `FmdPackCode` requires the four mandatory elements in either format.

### IFA PPN-Code Specification for Retail Packaging, Version 3.06 (6 November 2023) — `FmdPackCode`, `Ppn`, `Internal\IfaPackData`

Fetched 2026-11-19 (PDF, 44 pages, read locally).

- 4.1.3: "The PPN is listed as a data element with the data identifier “9N” in the ISO/IEC 15418 (ANSI MH10.8.2)
  standard." PRA Code table (Figure 2): `10` GS1 GTIN/NTIN, `11` IFA PZN, `12`-`24` other agencies, `MA` Master
  UDI-DI, `00`-`09` reserved. Reserved codes are not refused (format only).
- 4.2: "11 12345678 42 … the two-digit, calculated check number of the PPN across the entire data field (including
  the “11”)." 4.3: "0 4150 12345678 2 … The “4150” is the prefix assigned for the PZN by GS1 Germany" and "the NTIN
  must be prefixed by a “0” to generate a 14-digit format" → `Ppn::toNtin()`, `FmdPackCode::pzn()`.
- 5.1: two versions, "A Structure in format 06 according to ISO/IEC 15434 and Data Identifier (DI) according to
  ISO/IEC 15418" or "B System Identifier “FNC1” and Application Identifier (AI)"; "The sequence of the data elements
  is discretionary." → order kept as given; other DIs kept.
- 5.2: product code DI 9N / AI 01 (example `110375286414` / `04150037528643`), serial DI S / AI 21, batch DI 1T /
  AI 10, expiry DI D / AI 17 "YYMMDD … the dates are for the 21st century (2000–2099)", "b) Expiry date listing the
  month and year (DD = 00)". `DD = 00` is read as the last day of the month, as GS1 reads AI (17): a package decision
  (IFA's example "210600" is the same value in both formats).
- 5.3.3: "If there are several country-specific numbers in the format of a GTIN or NTIN, the additional data
  identifiers (8P) are included in the DMC multiple times."
- Appendix A: PPN "AN … 11 - 28 … 0 – 9; A – Z"; NTIN 8P/01 "N 14"; serial S/21 and batch 1T/10 "AN 1 – 20";
  expiry D/17 "YYMMDD 6". Footnote 35: "The technically processed characters are in accordance with “GS1 AI encodable
  character set 82”" → S and 1T are checked against CSET 82.
- Appendix C.5, Example 3 (scanner output, ASC): `Mac069N111234567842Gs 1T1A234B5Gs D151231Gs S1234567890123456`
  (Macro 06 = `[)>` RS `06` GS … RS EOT); Example 2 (GS1): `FNC104150123456782 101A234B5FNC1 17151231
  211234567890123456`. Both are the test fixtures; their product codes are the same PZN (`Ppn::toNtin()` equals the
  GTIN).

IFA Check Digit Calculations of PZN, PPN and Basic UDI-DI (26 January 2024), re-read 2026-11-19:

> The check digit of the PZN is calculated based on mod 11. … The whole number remainder is the check digit. If the
> remainder is the number 10, this digit sequence is not used as PZN.

(The table weights the seven digits 1 to 7.) Example `27580899`. PPN: "The check digit of the PPN and the Basic
UDI-DI is calculated based on mod 97. … The weighting of the digits starts on the left with two and increases by one
for each following digit." Example PRA `11` + PZN `03752864`, sum 3409, remainder 14, "this is the complete PPN:
110375286414". `Ppn` checks the PPN check digits always and the PZN check digit for PRA Code 11.

### Implementing Regulation (EU) 2018/574 — `TobaccoUnitIdentifier`

- Article 8(1): unit level UI "as short a sequence of alphanumeric characters as possible, not exceeding 50
  characters"; (a) "the ID issuer identification code … in the first position", (b) serial number, (c) product code,
  (d) "the time stamp in the last position … in the format YYMMDDhh, indicating the date and time of manufacture".
- Article 8(4): "Where ID issuers use encryption or compression for the generation of unit level UIs, they shall
  inform the competent authorities" → the inner structure is not public, format only.
- Article 2: time stamp "the date and time of occurrence of a particular event recorded in UTC (Coordinated
  Universal Time)" → `manufacturedAt()` is UTC.
- Article 3(4): the ID issuer code "shall be composed of alphanumeric characters and comply with … ISO/IEC
  15459-2:2015"; no length given, so `issuerPart()` is everything before the time stamp.
- Article 21(1)-(3): unit level UIs are encoded in a Data Matrix, QR Code or DotCode, by manufacturers and importers
  (electronically delivered UIs) or by the ID issuer (physically delivered ones). Article 21(4): "By way of
  derogation from paragraph 1, manufacturers and importers may add the time stamp separately from the data carrier"
  "in the format YYMMDDhh as a human readable code." So a data carrier may legally hold the UI without its time
  stamp → `TobaccoUnitIdentifierWithoutTimeStamp` (3 to 42 alphanumeric characters, no time-stamp accessors; trailing
  digits are never guessed to be a time stamp), joined with `withTimeStamp()` (UTC) into a `TobaccoUnitIdentifier`.
  No character set beyond "alphanumeric" is given.

Package decisions: `[0-9A-Za-z]`; minimum length 11 (three elements of at least one character plus eight) for the full
UI, 3 without the time stamp; century 2000 (`withTimeStamp()` refuses other years).

## 9. Russian marking (Task 8) — `Marking\RU\`, `Internal\RuMarkingSyntax`

### Sources and attempts (2026-11-19 UTC)

- The operator's knowledge base on markirovka.ru («Честный знак», designated by the Government) publishes «Состав
  кода маркировки» per product group, each citing the group's decree. Read with an HTML fetch (no JavaScript needed).
- Decree No. 224 (tobacco) text: quoted from the КонтурЭкстерн mirror. Fix round 1 read the current edition,
  `kontur-extern.ru/info/normativ/document/1/505024-postanovlenie-pravitelstva-rf-ot-28-02-2019-n-224`: "Редакция от
  29.12.2025", status "Действует", in force from 01.03.2026, amended last by No. 1848 of 20.11.2025 and No. 2192 of
  29.12.2025. Paragraphs 43-45 carry no amendment marks and read as quoted below (the edition of 28.03.2025, read
  first, has the same text). publication.pravo.gov.ru and legalacts.ru closed the socket, consultant.ru showed only
  the decree's cover text.
- docs.crpt.ru "Описание вариантов передачи КМ / КИ / КиЗ в разрезе товарных групп" (linked from the operator's
  retail page): not found by search.
- The operator pages give format lines, none prints a real code. The tests build one code per structure from those
  lines (GTIN `04600439931256`).

### Tobacco — Decree No. 224 of 28.02.2019, Section VII

- p. 43 (consumer package): "первая группа состоит из 14 цифр и содержит код товара", "вторая группа состоит из 7
  символов (цифр, строчных и прописных букв латинского алфавита, а также специальных символов)", "третья группа
  состоит из 4 символов" (the maximum retail price "в перекодированном виде"), "четвертая группа состоит из 4
  символов" (check code); the first three groups are the identification code. FNC1 is used "как признака
  соответствия данных типовому формату идентификаторов применения" except for Section I products → a leading `]d2`,
  `]d1` or GS before the pack code is set aside.
- p. 44 (group package): AI '01' 14 digits, '21' 7 characters, '8005' the sum of maximum retail prices (6), '93'
  "код проверки (4 символа)"; "первые 3 группы образуют код идентификации групповой упаковки"; "произвольное
  количество дополнительных групп данных". p. 45 (set): the same four groups. → `TobaccoGroupPack`.
- p. 2: "код товара" is the catalogue code; p. 44 carries it in AI (01), so the pack's 14 digits are checked as a GTIN.
- Pack character set: Decree No. 224 does not list its "специальных символов". CSET 82 is used on the strength of an
  answer in the comments of the operator's **packaged water** page (2024-02-19: serial characters follow the "GS1
  subset of ISO/IEC 646 … GS1 General Specifications, section 7.11"); it is not a tobacco-specific statement. Pack-
  shaped input (29 characters, 14 leading digits, no GS) with any other character is refused with its own message.
- p. 43 also says the first three groups "образуют код идентификации потребительской упаковки" (current edition).

### GS1-structured groups (operator pages; page date, decree)

| Product group | Page date | Decree (paragraph) | (21) | Long: (91)+(92) | Short: (93) | Optional |
|---|---|---|---|---|---|---|
| Light industry | 2024-10-01 | No. 1956 of 31.12.2019 (p. 30) | 13 | 4 + 44 | — | |
| Footwear | 2024-10-01 | No. 860 of 05.07.2019 (p. 38) | 13 | 4 + **88** | — | |
| Tyres | 2024-10-02 | No. 1958 of 31.12.2019 (p. 28) | 13 | 4 + 44 | — | |
| Perfume | 2026-04-03 | No. 1957 of 31.12.2019 (p. 28) | 13 | 4 + 44 | — | |
| Cameras | 2026-01-29 | No. 1953 of 31.12.2019 (p. 33) | **20** | 4 + 44 ("Состоит из 90 символов") | — | |
| Dairy | 2026-01-29 | No. 2099 of 15.12.2020 (p. 46) | 6 | — | 4 | (3103) after (93) |
| Packaged water | 2026-11-10 | No. 841 of 31.05.2021 (p. 45) | 13 | — | 4 | |
| Beer | 2024-09-26 | No. 2173 of 30.11.2022 (p. 45) | 7 | — | 4 | (335Y) after (93) |
| Non-alcoholic beer | 2024-10-22 | No. 678 of 27.05.2024 (p. 47) | 7 | — | 4 | |
| Non-alcoholic beverages | 2024-10-09 | No. 887 of 31.05.2023 (p. 47) | 13 | — | 4 | |
| BAD | 2026-08-06 | No. 886 of 31.05.2023 (pp. 49-50) | 13 | 4 + 44 | 4 | |
| Antiseptics | 2026-08-06 | No. 870 of 30.05.2023 (pp. 48-49) | 13 | 4 + 44 | 4 | |
| Medical devices | 2024-10-07 | No. 894 of 31.05.2023 (p. 53) | 13 | 4 + 44 | — | |
| Medicines (MDLP) | 2024-11-29 | No. 1556 (p. 5) | 13 | 4 + 44 | — | |
| Veterinary medicines | 2024-10-21 | No. 675 of 27.05.2024 (p. 49) | 13 | 4 + 44 | — | |
| Canned food | 2026-08-06 | No. 677 of 27.05.2024 (pp. 49-50) | 6 | 4 + 44 ("Состоит из 76 символов") | 4 | |
| Sweets | 2026-08-06 | No. 818 of 31.05.2025 (pp. 50-51) | 13 / 6 | 4 + 44 | 4 | |
| Grocery | 2026-08-06 | No. 1682 of 30.11.2024 (pp. 48-49) | 13 | 4 + 44 | 4 | |
| Meat | 2026-08-24 | No. 205 of 28.02.2026 (pp. 51-52) | 13 / 6 | 4 + 44 | 4 | (3103) after (92) or (93) |
| Caviar | 2026-08-06 | No. 2028 of 29.11.2023 (pp. 49-50) | 6 | 4 + 44 | 4 | |
| Pet food | 2026-08-06 | No. 674 of 27.05.2024 (pp. 49-50) | 6 | 4 + 44 | 4 | |
| Vegetable oils | 2026-08-06 | No. 676 of 27.05.2024 (pp. 53-54) | 13 | 4 + 44 | 4 | |
| Sports nutrition | 2026-08-06 | No. 811 of 31.05.2025 (pp. 50-51) | 13 | 4 + 44 | 4 | |
| Construction materials | 2026-08-06 | No. 820 of 31.05.2025 (pp. 49-50) | 13 | 4 + 44 | 4 | |
| Toys | 2026-10-14 | No. 819 of 31.05.2025 (pp. 49-50) | 13 / 6 | 4 + 44 | 4 | |
| Motor oils | 2026-08-10 | No. 1683 of 30.11.2024 (pp. 49-50) | 13 | 4 + 44 | 4 | |
| Cosmetics, household chemicals | 2026-08-10 | No. 1681 of 30.11.2024 (pp. 49-50) | 6 | 4 + 44 | 4 | |
| Bicycles | 2024-10-07 | No. 645 of 23.05.2024 (p. 40) | 13 | 4 + 44 | — | |
| Technical rehabilitation equipment | 2024-10-09 | No. 744 of 31.05.2024 (p. 53) | 13 | 4 + 44 | — | |
| Radio-electronics | 2026-09-23 | No. 1954 of 28.11.2025 (pp. 46-47) | **20** | 4 + 44 | 4 | |
| Electronic nicotine delivery systems | 2026-05-27 | No. 577 of 21.05.2026 (pp. 46-47) | 13 | 4 + 44 | 4 | |
| Household goods | 2026-08-06 | experiment, methodological recommendations p. 43 | 13 / 6 | 4 + 44 | 4 | |
| Cables | 2026-08-06 | experiment, methodological recommendations p. 41 | 13 | 4 + 44 | 4 | |
| Optical fibre | 2026-09-23 | experiment, methodological recommendations p. 41 | 13 | 4 + 44 | 4 | |
| Polymer pipes | 2026-08-06 | methodological recommendations p. 43 | 6 | 4 + 44 ("Состоит из 76 символов") | 4 | |

"13 / 6": the long structure has 13, the short one 6. Typical wording (light industry): "1-я группа состоит из 14
цифр", "Завершающим символом для этой группы данных является разделитель, имеющий код 29 в таблице символов ASCII",
"3-я группа состоит из 4 символов", "4-я группа состоит из 44 символов"; "1-я и 2-я группы образуют код
идентификации". Medicines: "Группы данных должны располагаться последовательно - от первой к четвертой" → the order
is enforced for every structure. Bicycles: the paragraph also says the operator's serial "9 или 13 символов" while the
group is "состоит из 13 символов" and the format line gives 12 + 1; 13 is used.

Merged structures (same AIs, same lengths, different groups), the published structures being matched first: `Standard` = (01) + (21) of 6, 13 or 20 + (91) of 4 +
(92) of 44 or 88, with an optional (3103); `Shortened` = (01) + (21) of 6, 7, 13 or 20 + (93) of 4, with an optional
(3103) or (335Y). Only the optional groups the pages publish are accepted, at most one. The structures cannot overlap
with the pack code: every GS1-structured code needs a GS (or brackets) after the variable-length (21), the pack is 29
characters of CSET 82. The one exception is 29 GS-free characters that are also `01` + GTIN + `21` + 5 + `93` + 4 (a shortened code with
a 5-character serial whose separator was lost): when the pack reading (GTIN check digit of the first 14) is valid too,
`MarkingCode` throws `AmbiguousValueException`, otherwise it refuses the lost separator. `MarkingIdentifier::
fromDocumentForm()` throws it for 25 characters that read both as a pack КИ (14 + 7 + 4) and as `01` + GTIN + `21` +
7; the constructor takes only the unambiguous canonical forms.

Controller ruling (fix round 1): a GS1-structured code with intact separators that fits no published structure
(another serial length, further AIs, other tail lengths) is accepted as `GenericGs1` on the AI table's checks, if it
starts with (01) and (21) and carries (92) or (93). Published lengths are used for the pack and to detect lost
separators.

### Identification code (КИ) in documents

"Состав кода идентификации для указания в документах в разделе "Маркировка"" (page date 2026-03-05): "КИ должен
состоять из 31 символа", "01 (идентификатор применения) + GTIN (код товара, состоящий из 14 цифр) + 21
(идентификатор применения) + серийный номер (13 символов) = 01xxxxxxxxxxxxxx21xxxxxxxxxxxxx". The same page family
gives 24 characters for the 6-character serial (dairy) and 25 for МОТП/АТП (7). → `MarkingIdentifier::toDocumentForm()`.

### Lost GS separators (Review Focus 4)

No operator page documents a recovery rule (splitting by fixed lengths); the footwear page says only that the trailing
GS "может не присутствовать в конце буквенно-цифровой последовательности КМ". So nothing is recovered. Rule (fix round 1): a group that holds, at
one of its published lengths, the AI a published structure puts next is refused as a lost separator — (21) at 6, 7, 13
or 20 followed by `91`, `93` or `8005`; (91) at 4 by `92`; (92) at 44 or 88 by `3103`; (93) at 4 by `3103` or `335y`.
The same rule applies to identifiers. A serial that really contains such a sequence at such an offset is refused too
(never mis-split). A GS-less code with no tail is refused for lacking (92)/(93).

## Unreachable or deferred

| Source | Date (UTC) | What was tried | Effect |
|---|---|---|---|
| EUR-Lex (2016/161, 2018/574) | 2026-11-19 | HTML, PDF and ELI URLs: HTTP 202, empty body; Task 8 retry (CELEX HTML, ELI): empty body | `@see` links on legislation.gov.uk |
| publication.pravo.gov.ru, legalacts.ru (Decree No. 224) | 2026-11-19 | document view: socket closed | quoted from the КонтурЭкстерн mirror |
| docs.crpt.ru "Описание вариантов передачи КМ / КИ / КиЗ" | 2026-11-19 | search | not found; the per-group operator pages are the source |
| Operator pages of 10 groups (auto parts, pharmaceutical raw materials, frozen food, heating appliances, titanium, fertilisers, pyrotechnics, fire safety, printed matter, wheelchairs) | 2026-11-19 | group index pages and search | no «Состав кода маркировки» page found; listed as not covered in the README |
| ICCBBA ST-011 v1.9.0 | 2026-11-19 | the registry page linking it is on a host outside the sandbox allowlist | not needed: ST-017 defines every UDI structure (Task 7) |
| ICCBBA RT017 (specified compound messages) | 2026-11-19 | published in ICCBBA's password-protected area | sequences other than 034-037 (ST-017 Table 3) are not order-checked |
| HHS HIPAA rule 65 FR 50312 | 2026-11-19 | not fetched | the 5-4-2 billing form is quoted from the FDA final rule's description of it |
| GS1 prefix allocation list (MO per prefix) | 2026-11-19 | not fetched | `Gs1Prefix` decodes the General Specifications tables only; Member Organisations are not named |
