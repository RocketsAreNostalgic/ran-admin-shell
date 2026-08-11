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
