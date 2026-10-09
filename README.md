# RAN Admin Shell

Build-time admin-shell resources for Rockets Are Nostalgic WordPress plugins.
The package provides an optional-first visual vocabulary without a live
cross-plugin runtime dependency.

Only a non-empty `name` is required. `home_url`, `strapline`, `logo`,
`background`, `version`, `navigation` and `actions` are independently optional.
Blank or invalid optional values emit no feature markup and reserve no layout
space. Native contextual Help is consumer-owned.

Consumers install this as a development dependency, create
`ran-admin-shell.json`, then explicitly synchronize and verify committed
runtime files:

```sh
vendor/bin/ran-admin-shell sync --config=ran-admin-shell.json
vendor/bin/ran-admin-shell check --config=ran-admin-shell.json --immutable
```

The package and `vendor/` stay out of installed WordPress sites.

For a full-width admin header, render the synchronized shell before the
consumer's `.wrap`; keep notices, forms and page content inside `.wrap`.
The shell offsets WordPress's 20px desktop and 10px responsive `#wpcontent`
start padding using a logical margin, while `overflow: clip` lets the native
Screen Options and Help controls float over the background without narrowing
the header surface. The consumer must keep `#screen-meta-links` above the shell
in its exact-screen stylesheet.

## Optional navigation scaffold

Navigation is an ordered consumer-owned array. Nothing is rendered when the
array is absent or empty. Consumers can append an optional catch-all tab only
when they have a real destination for it:

```php
$navigation = array(
	array(
		'label'   => __( 'Overview', 'consumer-text-domain' ),
		'url'     => $overview_url,
		'current' => true,
	),
);

if ( $other_url ) {
	$navigation[] = array(
		'label' => __( 'Other', 'consumer-text-domain' ),
		'url'   => $other_url,
	);
}

$ran_admin_shell = array(
	'name'             => __( 'RAN Example', 'consumer-text-domain' ),
	'navigation_label' => __( 'Plugin sections', 'consumer-text-domain' ),
	'navigation'       => $navigation,
);
```

The consumer owns the labels, URLs, current-page decision and permissions. The
shell only validates and renders supplied items, and marks at most one item as
current.

## Development quality commands

Install the tracked dependencies with `composer install`, then run
`composer check`. The aggregate verifies maintained-PHP coverage, then runs the
independent syntax sweep, standards check, static analysis and existing
render/synchronization tests, in that order.

- `composer check:coverage` compares actual maintained PHP and Composer CLI
  entries with the direct PHPStan and PHPCS source scopes. A newly added maintained PHP
  path outside either gate, or a standards exclusion of maintained
  PHP, fails the check. Blanket, persistent (`phpcs:disable`) and legacy suppression comments also fail.
  New extensionless Composer commands or imported PHPStan configurations require
  a reviewed guard update. Tests and previews require the same direct Level 8 analysis as production
  and tooling. Installed dependencies remain excluded.
- `composer lint:syntax` parses PHP in `resources/`, `tools/`, `tests/` and
  `fixtures/`, plus the extensionless `bin/ran-admin-shell` entrypoint. Missing
  required roots or entrypoint and parser failures fail the command. It does
  not execute the selected source or inspect dependency directories.
- `composer standards` runs PHPCS; `composer standards:fix` runs PHPCBF against
  the same rules and paths. These replace the former `composer phpcs` command.
- `composer test` runs PHPUnit, including render, immutable synchronization
  and syntax-sweep failure contracts.

Standards consume published `ran/coding-standards` v1 through
`RANWordPressLibrary` for shipped resources and `RAN` plus WordPress-Extra conventions for standalone tooling, tests and previews.
PHPCompatibilityWP applies to resources; full PHPCompatibility applies to standalone tools, tests, previews and the CLI. Common formatting, owned naming and Yoda conditions apply to both scopes. Separate rulesets prevent WordPress
polyfill exclusions from leaking into standalone checks. Both `standards` and
`standards:fix` run the resource and tooling rulesets in the same order. PHP support and the
WordPress floor remain local settings.
The preview fixtures receive the same common conventions and full native PHP compatibility as tooling/tests.
PHP 8.0 remains the supported floor; CI also runs the aggregate on PHP 8.5.
The compatibility packages are explicitly root-pinned to the shared profile's
reviewed alpha generation (PHPCompatibility 10 / WP 3 / Paragonie 2); Composer
stability remains unchanged for other dependencies. Upgrades require a reviewed
lock update and PHP-floor/current CI. RANOwnedMethods explicitly checks owned methods in both scopes, including inherited classes. Required PHPUnit/PHPCS signatures and native properties have exact local exceptions. The completed initial analysis adoption remains recorded in #13.

`standards:fix` preserves PHPCBF exit semantics (0 unchanged, 1 successfully
fixed, greater than 1 failure) while continuing to the second scope after
successful fixes in the first.

`composer test` also runs the real PHPCS/PHPCBF binaries in a temporary fixture
layout: incompatible CLI code must fail, an unrelated extensionless file stays
excluded, and the adopted spacing rule proves the same CLI is fixed once and then remains byte-stable. Additional controls enforce naming/conditions at current and future tooling, test, preview and resource paths.

### CSS quality

Use Node 24.21.0 and pnpm 11.13.1. Install with
`pnpm install --frozen-lockfile`, then run `pnpm check`. Stylelint recursively
checks the authoritative CSS under `resources/`; Prettier uses the shared
WordPress configuration on the same paths. Run `pnpm format` for formatting
and `pnpm lint:css --fix` for supported Stylelint fixes.

The shared configuration is pinned to an immutable reviewed candidate. Existing
BEM element/modifier names retain a narrow class-name accommodation.
`rule-empty-line-before` retains the upstream after-comment exception and also
ignores the first nested rule: WordPress Prettier removes the blank line that
WordPress Stylelint requests at the start of a media block. This local formatting
accommodation keeps both tools stable; other blank-line checks and semantic CSS
rules remain active. The configuration therefore includes these two documented
local accommodations rather than claiming exact upstream parity. The
PHP and frontend lanes both feed the required terminal Quality check.
Node dependencies and configuration stay out of Composer exports.

### Static analysis

`composer analyze` runs locked PHPStan at blocking Level 8 with a PHP 8.0
language target and a 512 MB memory limit; `composer check` includes it.
The CLI requires registered command-line arguments and exits with a clear
diagnostic if `$argv` is unavailable (for example, when `register_argc_argv`
is disabled), rather than passing an undefined variable into the sync command.
Direct analysis covers the extensionless CLI and all PHP under `resources/`
and `tools/`, `tests/` and `fixtures/`, including future files in each role.
An independently discovered maintained file outside these roots fails coverage
until its whole role is included or a concrete exemption is reviewed.
WordPress 6.5-generation stubs
and PHPCS source provide
symbol discovery only; dependency bodies are not first-party analysis roots.
The stubs describe APIs, not proof of an installed WordPress runtime.

Tests and preview fixtures are now included in direct analysis: all 16 maintained
PHP entrypoints pass Level 8, with no file exemptions or ignored diagnostics.
The coverage gate rejects a level below 8 and development-role omissions.
Their existing syntax, common standards, compatibility and PHPUnit checks remain.
Consumer-owned synchronized copies are verified through
existing render/sync/provenance tests rather than scanned in sibling checkouts.
No baseline or ignored PHPStan errors are introduced.

The initial level-6 probe reported 28 missing parameter/return/iterable-value
type declarations in `tools/SyncCommand.php`. That is historical optional analysis sizing, not an active task in closed #13. Accurate contract typing and any subsequent level increase require separately scoped work under the existing quality programme;
the renderer and synchronization implementation remain unchanged.

### Package distribution acceptance

The ordinary test suite exports committed `HEAD` with `git archive`, checks the
package boundary and installs that exact ZIP into an isolated Composer consumer.
It uses a local package repository with Packagist disabled; no package is
published and no real consumer lock is changed. Git, Composer and the ZIP
extension are required alongside PHP (the existing PHP 8.0/8.5 CI provides them).
Commit export-affecting edits before running this proof: it tests the committed
archive, not uncommitted working-tree bytes.

The real Composer binary proxy must synchronize resources, pass immutable
verification against installed metadata, retain byte-stable provenance on repeat
sync, reject resource drift and reject a mismatched locked source reference.
A production-only Composer install then removes the development package while
preserving the consumer-owned PHP, CSS and provenance bytes. Package development
dependencies, test/configuration trees and workflows are not installed with the
export. Each actual plugin still owns its archive allowlist and must exclude
`vendor/` and this build-time package from its release ZIP. This isolated fixture
does not claim installed WordPress or interactive UI acceptance.


### Next-beta standards acceptance

[docs/quality-acceptance.md](docs/quality-acceptance.md) records the standalone/preview
profile, exact retained exception groups, existing safety evidence and remaining
qualification boundaries under organisation #65/#128. This does not update any
consumer pin, publish the package or claim installed UI acceptance.
