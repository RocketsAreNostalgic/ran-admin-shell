<?php
/** Run both formatter scopes; PHPCBF status 1 means successful fixes, not failure. */

$changed = false;
foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $standard ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
	$process = proc_open(
		array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/phpcbf', '--standard=' . $standard ),
		array( STDIN, STDOUT, STDERR ),
		$pipes,
		dirname( __DIR__ )
	);
	if ( ! is_resource( $process ) ) {
		exit( 2 );
	}
	$fixer_status = proc_close( $process );
	if ( 0 !== $fixer_status && 1 !== $fixer_status ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The expression returns an integer process exit status, not rendered HTML.
		exit( $fixer_status > 1 ? $fixer_status : 2 );
	}
	$changed = $changed || 1 === $fixer_status;
}
exit( $changed ? 1 : 0 );
