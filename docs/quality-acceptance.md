# Admin Shell next-beta PHP profile acceptance

## Maintained-file analysis correction — 6 October 2026

The current owner instruction requires every maintained PHP file at level 5,
including development PHP. The historical production-only boundary below is
superseded: all sixteen entrypoints are directly analyzed, including seven
test/bootstrap and two preview files. The locked checker reports no diagnostics;
no baseline, ignore or fixture exemption is required. Directory scopes include
new development files automatically, and independent recursive discovery rejects
any unselected root or split-out file. Lowering the configured level below five
also fails coverage. Existing real-checker stub/extension safeguards remain.

The change extends existing configuration and coverage controls only. Runtime,
resource, dependency and consumer-provenance bytes are unchanged. Whole-ecosystem
exception acceptance remains unfinished under #65/#128. Earlier evidence and
production-only descriptions below are historical, not current scope.


This tranche continues organisation #65/#128 on main
`ff6e933b031d5d537defcfad7a00e12e4e2ce2ec`. Earlier command, analysis, coverage,
distribution and stat-cache work (#14–20) remains delivered. No dependency or
consumer adoption, release, installed UI acceptance or analysis-level increase
is part of this proposal.

## Scope and exposure

Sixteen maintained PHP files/entrypoints are accounted for: one authoritative
renderer; one extensionless CLI; five tools; seven test/bootstrap files; two
browser preview files. The existing CSS resource also remains selected by the
resource ruleset. PHPStan level 5 / PHP 8.0 retains its seven production/tool
paths; tests and previews receive standards, syntax and full native compatibility.

The fresh common-convention probe exposes 252 reports across all 15 standalone,
test and preview PHP files (64 errors / 188 warnings). These are exposure counts,
not 252 defects. Existing RAN tooling selection supplied syntax plus compatibility
but omitted common conventions. The two preview files had syntax-only checks.
The new local scope uses existing WordPress-Extra and RANOwnedMethods with RAN,
retaining shared PSR-4 filename decisions. It deliberately does not inherit
PHPCompatibilityWP into standalone code: its WordPress-polyfill exclusions would
hide native PHP 8.0 incompatibilities. No new public profile or dependency is
needed. Resource rules also explicitly enable owned-method checking.

## Dispositions and safeguards

Mechanical alignment/array/spacing changes preserve executable contracts and
fixture values. Two standalone top-level locals no longer collide with WordPress
global names. The Composer executable fallback evaluates its environment lookup
once and preserves the same falsy fallback. Owned helpers already use snake_case.
No public identifier, persisted schema, synchronized resource or provenance byte
changes are included.

| Retained boundary | Concrete reason and evidence |
| --- | --- |
| Foreign declarations/properties | Ten PHPUnit lifecycle methods and PHPCS shouldProcessFile retain required names. Native DOM and ZipArchive property accesses preserve extension contracts. Exact local codes leave owned naming active. |
| Native files and streams | Standalone tools cannot require a WordPress filesystem initialization. Native reads verify exact resource/configuration/archive bytes; temporary write/rename preserves atomic replacement; tests observe real directories, links, consumer installation/removal and owned cleanup. Existing sync/distribution tests remain. |
| Subprocesses | Argument-vector process launches execute the real parser, checker, fixer, Composer consumer and CLI. Stream closure and observed exit status remain unchanged. |
| JSON and generated PHP | Native JSON retains serialization flags and immutable provenance bytes without WordPress; the controlled WordPress JSON stub must not call itself recursively. var_export emits quoted local paths into isolated test subprocess PHP. |
| Exception/status output | Internal CLI exception payloads go to STDERR, not HTML. exit expressions return integer process status. Actual preview HTML remains escaped at rendering. |
| Mutable traversal / cleanup | Parent-path length must be rechecked as the traversal moves. Suppressed invalid regex results are explicitly rejected; failed replacement cleanup remains best-effort and preserves the original failure. |
| Browser preview | Query input selects only an existing allowlisted case, with no mutation. Its fixed stylesheet and template include run without a WordPress lifecycle; exact local exceptions preserve that boundary. |

No blanket all-rule suppression or broad native-operation waiver is introduced.
The existing coverage tool now checks standards inclusion/exclusions for tests
and previews as well as production, while maintaining their distinct analysis
boundary. Comment-token inspection rejects blanket, persistent and legacy PHPCS bypasses;
fixture string literals are not mistaken for operative annotations.

Existing real-PHPCS/PHPCBF controls prove current/future CLI, tool, test, preview
and resource conventions, inherited owned-method enforcement, native compatibility,
matching fix scope and repeatability. Coverage controls prove new development
paths need standards coverage and blanket suppressions fail. No second checker,
profile registry or compliance service is introduced.

## Consumer boundary and qualification

Core still pins `ran/admin-shell` at
`7fee7a1cebb24c8dcbf1bfd1c9b9c9455fa73efb`. The synchronized PHP and CSS hashes
match this repository's unchanged resource bytes. No Core dependency or generated
copy update is warranted by development-profile changes alone.

Baseline canonical checks passed 26 tests / 173 assertions. The final candidate
requires canonical Composer, actual committed-archive installation/provenance
and no-dev removal tests, PHP 8.0/8.5 native CI and independent actual-pair review.
The existing archive test operates on committed HEAD, so final evidence must be
collected after committing export-affecting changes. Browser preview is not an
installed WordPress/UI acceptance claim. Optional higher-level PHPStan work stays
separate; the historical level-6 probe does not reopen closed #13.

Local candidate checks pass on PHP 8.3.6: all 16 PHP entrypoints parse and have
standards coverage, PHPCS and PHPStan level 5 are clean, and PHPUnit passes
30 tests / 250 assertions. Focused checker/coverage controls pass 12 / 126;
the preview suite passes 5 / 25, including no checker annotations in emitted
HTML. Token comparison preserves CLI, synchronization, filter and syntax-tool
behavior, allowing mechanical trailing commas; fixer status-variable renaming
and unchanged preview case values are explicitly accounted for. Resource PHP/CSS
hashes are unchanged. Independent preliminary review found and corrected two
preview annotation lines accidentally emitted as text; the new render control
protects that finding. Actual published-pair review and PHP 8.0/8.5 native CI
remain separate qualification.


## Review corrections: persistent suppression and profile identity

The coverage gate rejects every `phpcs:disable` directive in maintained PHP,
including named categories, individual codes and subsequently re-enabled spans.
This repository uses occurrence-local `phpcs:ignore` exceptions; its current
sources need no persistent disables. Exact local ignores remain accepted.

Coverage paths and exclusions are retained separately per ruleset. Resources
must be covered by the resource profile; CLI, tools, tests and previews must be
covered by the standalone tooling profile. Resource coverage cannot compensate
for a missing or excluded tooling path, so WordPress polyfills cannot silently
replace the required full native compatibility checks. Regression controls cover
both tests and previews covered only by the resource ruleset, tooling exclusions
masked by resource coverage, and named/pairwise re-enabled disable directives.


Corrected canonical checks pass 30 tests / 250 assertions; focused coverage
checks pass 6 / 74. Differential controls reproduce the old category-disable,
resource-only test and resource-only preview bypasses, and reject all three with
the corrected guard. A tooling exclusion remains rejected under both versions.

## Review correction: exact local exceptions

Comment-token inspection now treats PHPCS directives case-insensitively. An
occurrence-local ignore must name complete four-part diagnostic codes and carry
a nonempty written rationale; standard/category selectors and missing reasons
fail coverage. A reason's factual justification still requires source review.
Local XML rule options require review too: the only accepted nested options are
the two existing WordPress-Extra PSR-4 filename exclusions in the tooling ruleset.
Locked shared-profile internals remain unchanged; this guard does not reinterpret
their inheritance or replace PHPCS.

Actual-checker controls reproduce case-variant/broad/reasonless comment bypasses
and rule-level excludes/severity-zero bypasses, then require the coverage gate
to reject them. A justified exact ignore remains accepted while the next line
and a different native-operation diagnostic remain reported. Existing directory
scope includes future resources/tools/tests/previews, and discovery still rejects
unaccounted new roots; there is no per-file coverage allowlist. Resource bytes,
dependencies, runtime contracts and the seven-path production analysis boundary
remain unchanged.


## Review correction: independent discovery and effective selection

Discovery inspects a bounded PHP/shebang header as well as case-insensitive PHP
extensions. New uppercase or nonstandard PHP entrypoints fail until explicitly
supported by both existing checker profiles; the existing extensionless Composer
CLI remains covered. Directory-inclusive scope and profile separation remain.
PHPStan coverage now uses the locked container's actual FileFinder selection
rather than inferring analysis from path strings. Configured stubFiles require
explicit coverage review; none are used by the accepted profile. Controls reject
extension filtering and body-analysis omissions through stubFiles.
Local PHPCS arguments are restricted to the current presentation options and the
reviewed standalone filter; actual checker controls reproduce and reject exclude,
sniffs and ignore argument bypasses. This does not change resource bytes, locked
dependencies or the seven-path production analysis boundary.
