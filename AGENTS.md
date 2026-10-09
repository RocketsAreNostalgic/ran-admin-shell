# RAN Admin Shell

This repository is a build-time Composer library for shared Rockets Are
Nostalgic WordPress admin-shell resources. It is not an activatable WordPress
plugin and must not register runtime hooks, globals, services or assets.

Only the product name is required by the rendering contract. Optional values
must disappear structurally when absent: no empty wrapper, control, grid track
or asset enqueue.

Consumers install this package as a development dependency, run the explicit
sync command, commit the synchronized PHP/CSS/provenance files and ship those
consumer-owned files. Consumers must not ship this package or Composer vendor
files in WordPress plugin archives.

Run `composer check` and `pnpm check` before handoff. Use Node 24.21.0 /
pnpm 11.13.1 with the frozen strict-peer lock. CSS lint and formatting cover
`resources/**/*.css`; fix authoritative source rather than generated consumer
copies. Source CSS alignment authorized under #28 requires reviewed immutable
consumer resynchronization; preserve resource PHP bytes. Use Conventional
Commits. Do not tag,
publish to Packagist or create a release without separate owner authorization.

## External AI agent prohibition

Do not invoke, delegate work to, tag, enable, or otherwise use Blacksmith [code]smith,
`@codesmith-bot`, Blacksmith Autofix, Blacksmith CI Tuning, Blacksmith Testbox agents,
or any other Blacksmith AI/agent feature.

Blacksmith may be used only as infrastructure for ordinary GitHub Actions runners where
the repository workflow explicitly specifies a Blacksmith runner.

Do not click or trigger "Enable autofix", do not ask [code]smith to investigate or repair
CI, and do not call Blacksmith agent/MCP/CLI/API features that perform AI inference.

If CI fails, inspect GitHub Actions logs directly and diagnose/fix the failure yourself.

This prohibition is a cost-control requirement and must not be overridden by convenience,
CI failure, review comments, or suggestions from GitHub/Blacksmith UI.


## Maintained PHP profiles

Common WordPress-derived formatting, owned snake_case naming and Yoda conditions
apply to all maintained PHP, including tools, tests, preview fixtures and the
extensionless Composer CLI. RANOwnedMethods covers inherited owned declarations.
Preserve the exact PHPUnit/PHPCS overrides and native DOM/ZipArchive properties
with declaration/occurrence-local diagnostic reasons; ordinary helpers are owned.

Resources retain RANWordPressLibrary and PHPCompatibilityWP. Standalone tooling,
tests and previews use RAN plus WordPress-Extra conventions and full native
PHPCompatibility: WordPress polyfills must not mask PHP 8.0 incompatibilities.
Keep check/fix scopes aligned and the real-binary controls meaningful. Native
filesystem/stream/process operations, immutable JSON bytes and internal exception
data retain exact local reasons, never blanket category waivers. Read-only preview
selection uses its existing allowlist and does not require a mutation nonce.

`check:coverage` requires direct level-5 PHPStan coverage of every maintained PHP
file, including tests and previews, and rejects blanket/persistent/legacy suppression
comments without executing source. Preserve resource PHP bytes and the reviewed
CSS alignment/synchronization boundary, immutable reference checks, destination/link fences, atomic replacement, cleanup and consumer removal
proofs. Read docs/quality-acceptance.md before widening these boundaries. No package
publication or consumer lock/provenance update follows from profile acceptance.
