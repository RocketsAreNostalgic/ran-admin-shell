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
`composer check`. The aggregate runs the independent syntax sweep, standards
check and existing render/synchronization tests, in that order.

- `composer lint:syntax` parses PHP in `resources/`, `tools/`, `tests/` and
  `fixtures/`, plus the extensionless `bin/ran-admin-shell` entrypoint. Missing
  required roots or entrypoint and parser failures fail the command. It does
  not execute the selected source or inspect dependency directories.
- `composer standards` runs PHPCS; `composer standards:fix` runs PHPCBF against
  the same rules and paths. These replace the former `composer phpcs` command.
- `composer test` runs PHPUnit, including render, immutable synchronization
  and syntax-sweep failure contracts.

Standards consume published `ran/coding-standards` v1 through
`RANWordPressLibrary` for shipped resources and `RAN` for standalone tooling.
The existing distinction remains: WordPress rules apply to resources, while
PHPCompatibilityWP applies to resources and full PHPCompatibility applies to
standalone tools, tests and the CLI. Separate rulesets prevent WordPress
polyfill exclusions from leaking into standalone checks. Both `standards` and
`standards:fix` run the resource and tooling rulesets in the same order. PHP support and the
WordPress floor remain local settings.
The preview fixtures receive syntax coverage without new style enforcement.
PHP 8.0 remains the supported floor; CI also runs the aggregate on PHP 8.5.
The compatibility packages are explicitly root-pinned to the shared profile's
reviewed alpha generation (PHPCompatibility 10 / WP 3 / Paragonie 2); Composer
stability remains unchanged for other dependencies. Upgrades require a reviewed
lock update and PHP-floor/current CI. No owned-method naming enforcement is
implicitly enabled by this adoption. Static analysis remains tracked in #13.

`standards:fix` preserves PHPCBF exit semantics (0 unchanged, 1 successfully
fixed, greater than 1 failure) while continuing to the second scope after
successful fixes in the first.

`composer test` also runs the real PHPCS/PHPCBF binaries in a temporary fixture
layout: incompatible CLI code must fail, an unrelated extensionless file stays
excluded, and a fixture-only fixable rule proves the same CLI is fixed once and
then remains byte-stable. The fixture rule does not alter production policy.
