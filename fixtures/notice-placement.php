<?php
/** Standalone opt-in full-page notice composition; not an installed WordPress proof. */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';

$cases = array( 'global-only', 'owned-only', 'combined', 'empty', 'hidden', 'mixed', 'dismissed', 'css-hidden', 'updated', 'error' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview selects an allowlisted composition without protected mutation.
$case            = isset( $_GET['case'] ) && is_string( $_GET['case'] ) && in_array( $_GET['case'], $cases, true ) ? $_GET['case'] : 'combined';
$has_global      = in_array( $case, array( 'global-only', 'combined', 'mixed', 'updated', 'error' ), true );
$has_owned       = in_array( $case, array( 'owned-only', 'combined', 'mixed' ), true );
$notice_class    = in_array( $case, array( 'updated', 'error' ), true ) ? $case : 'notice notice-warning';
$ran_admin_shell = array(
	'name'       => 'RAN Notice Placement',
	'strapline'  => 'Manage settings and tools for your WordPress site.',
	'navigation' => array(
		array(
			'label'   => 'Overview',
			'url'     => '#content',
			'current' => true,
		),
	),
);
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Notice placement — <?php echo esc_html( $case ); ?></title>
	<style>
		/* Simplified native styles; consumer acceptance must use real WordPress. */
		body { background: #f0f0f1; color: #1d2327; font: 14px/1.4 sans-serif; margin: 0; }
		#wpcontent { padding-inline-start: 20px; }
		.wrap { margin: 10px 20px 0 2px; }
		.notice, div.updated, div.error { background: #fff; border-inline-start: 4px solid #dba617; margin: 5px 15px 2px; padding: 8px 12px; }
		.wrap .notice { margin: 5px 0 15px; }
		#screen-meta-links { float: right; margin: 0 20px 0 0; }
		#screen-options-link-wrap, #contextual-help-link-wrap { float: left; margin: 0 0 0 6px; }
		#screen-meta-links .screen-meta-toggle { position: relative; top: 0; }
		#screen-meta-links .show-settings { border: 1px solid #c3c4c7; border-top: none; height: auto; margin-bottom: 0; padding: 0 6px 0 16px; background: #fff; border-radius: 0 0 4px 4px; color: #646970; }
		#screen-meta-links .show-settings::after { content: "⌄"; display: inline-block; padding: 0 5px; line-height: 1.5; vertical-align: top; }
		@media (max-width: 782px) { #wpcontent { padding-inline-start: 10px; } #screen-meta-links { margin-right: 10px; margin-bottom: 20px; } }
	</style>
	<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone preview has no WordPress enqueue lifecycle; this fixed local stylesheet is its fixture resource. ?>
	<link rel="stylesheet" href="../resources/admin-shell.css" />
</head>
<body>
	<div id="wpcontent"><main id="wpbody-content">
		<div id="screen-meta-links">
			<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle"><button type="button" id="show-settings-link" class="button button-compact show-settings" aria-controls="screen-options-wrap" aria-expanded="false">Screen Options</button></div>
			<div id="contextual-help-link-wrap" class="hide-if-no-js screen-meta-toggle"><button type="button" id="contextual-help-link" class="button button-compact show-settings" aria-controls="contextual-help-wrap" aria-expanded="false">Help</button></div>
		</div>
		<?php if ( $has_global ) : ?>
			<div id="global-notice" class="<?php echo esc_attr( $notice_class ); ?>"><p>Unrelated global notice</p></div>
		<?php endif; ?>
		<?php if ( in_array( $case, array( 'hidden', 'mixed' ), true ) ) : ?>
			<div class="notice" hidden><p>Hidden notice placeholder</p></div>
			<div class="updated" hidden><p>Hidden legacy success</p></div>
			<div class="error" hidden><p>Hidden legacy error</p></div>
		<?php endif; ?>
		<?php if ( 'css-hidden' === $case ) : ?>
			<div class="notice" style="display:none"><p>CSS-hidden notice</p></div>
		<?php endif; ?>
		<div class="ran-admin-shell-notices"><hr class="wp-header-end"></div>
		<?php
		// phpcs:ignore PEAR.Files.IncludingFile.UseRequire -- Preview includes the authoritative renderer in its local input scope.
		include dirname( __DIR__ ) . '/resources/admin-shell.php';
		?>
		<div class="wrap" id="content">
			<?php if ( $has_owned ) : ?>
				<div id="owned-notice" class="notice inline"><p>Explicitly owned page notice</p></div>
			<?php endif; ?>
			<p><?php echo esc_html( 'dismissed' === $case ? 'Dismissed global notice has been removed.' : 'Consumer-owned content.' ); ?></p>
			<form><label>Example setting <input name="setting" aria-describedby="form-feedback" /></label><p id="form-feedback" class="notice inline">Feedback beside its form control.</p></form>
			<table><tbody><tr><td>Managed item <button type="button" aria-describedby="row-feedback">Update</button><div id="row-feedback" class="notice inline"><p>Feedback beside this row.</p></div></td></tr></tbody></table>
		</div>
	</main></div>
</body>
</html>
