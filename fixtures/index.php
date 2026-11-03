<?php
/** Browser fixture for the optional-first shell. */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$cases = require __DIR__ . '/cases.php';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only browser preview selects an existing allowlisted fixture key and performs no protected mutation.
$case = isset( $_GET['case'] ) && is_string( $_GET['case'] ) && isset( $cases[ $_GET['case'] ] ) ? $_GET['case'] : 'name-only';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview accepts only a fixed palette choice.
$color_scheme                    = isset( $_GET['color_scheme'] ) && is_string( $_GET['color_scheme'] ) && in_array( $_GET['color_scheme'], array( 'light', 'inverse' ), true ) ? $_GET['color_scheme'] : 'light';
$ran_admin_shell                 = $cases[ $case ];
$ran_admin_shell['color_scheme'] = $color_scheme;
if ( 'inverse' === $color_scheme && isset( $ran_admin_shell['logo'] ) ) {
	$ran_admin_shell['logo']['url'] = '/fixtures/nozzle-inverse.svg';
}
?><!doctype html>
<html lang="en"<?php echo 'rtl' === $case ? ' dir="rtl"' : ''; ?>>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>RAN Admin Shell — <?php echo esc_html( $case ); ?></title>
	<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone preview has no WordPress enqueue lifecycle; this fixed local stylesheet is its fixture resource. ?>
	<link rel="stylesheet" href="../resources/admin-shell.css" />
	<style>
		body { padding-top: 32px; background: #f0f0f1; color: #1d2327; font: 14px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; }
		.fixture-toolbar { background: #1d2327; color: #fff; position: fixed; top: 0; left: 0; right: 0; height: 32px; z-index: 100; overflow: auto; white-space: nowrap; }
		.fixture-toolbar a { color: #fff; margin-inline-end: 12px; }
		.fixture-toolbar a[aria-current="page"] { font-weight: 700; text-decoration-thickness: 3px; text-underline-offset: 3px; }
		.fixture-canvas { margin-inline-start: 160px; padding-inline: 20px; }
		.fixture-sidebar { position: fixed; top: 32px; bottom: 0; width: 160px; background: #1d2327; color: #fff; }
		.fixture-content { min-height: 180vh; background: #fff; padding: 20px; }
		body.fixture-classic-scrollbar .ran-admin-shell__navigation::-webkit-scrollbar { height: 17px; }
		body.fixture-classic-scrollbar .ran-admin-shell__navigation::-webkit-scrollbar-thumb { background: #50575e; }
		@media (max-width: 782px) { body { padding-top: 46px; } .fixture-toolbar { height: 46px; } .fixture-sidebar { display: none; } .fixture-canvas { margin: 0; padding-inline: 10px; } }
		@media (max-width: 600px) { .fixture-toolbar { position: absolute; } }
	</style>
</head>
<body class="admin-bar<?php echo 'classic-scrollbar' === $case ? ' fixture-classic-scrollbar' : ''; ?>">
	<nav class="fixture-toolbar" aria-label="Fixture cases">
		<a href="notice-placement.php">Full-page notice placement</a>
		<?php foreach ( array( 'light', 'inverse' ) as $scheme ) : ?>
			<a href="?case=<?php echo esc_attr( (string) $case ); ?>&amp;color_scheme=<?php echo esc_attr( $scheme ); ?>"<?php echo $scheme === $color_scheme ? ' aria-current="page"' : ''; ?>><?php echo esc_html( ucfirst( $scheme ) ); ?></a>
		<?php endforeach; ?>
		<?php foreach ( array_keys( $cases ) as $case_name ) : ?>
			<a href="?case=<?php echo esc_attr( (string) $case_name ); ?>&amp;color_scheme=<?php echo esc_attr( $color_scheme ); ?>"><?php echo esc_html( (string) $case_name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<aside class="fixture-sidebar">WordPress admin menu</aside>
	<main class="fixture-canvas" id="wpbody-content">
		<p>Standalone preview, not installed WordPress proof. <a href="#content">Skip to content</a> <button type="button">Help</button> <button type="button">Screen Options</button></p>
		<?php
		// phpcs:ignore PEAR.Files.IncludingFile.UseRequire -- Preserve the preview template include behavior while exercising the authoritative renderer.
		include dirname( __DIR__ ) . '/resources/admin-shell.php';
		?>
		<div class="fixture-content" id="content" tabindex="-1">Consumer-owned page content</div>
	</main>
</body>
</html>
