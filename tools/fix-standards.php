<?php
/** Run both formatter scopes; PHPCBF status 1 means successful fixes, not failure. */

$changed = false;
foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $standard ) {
	$process = proc_open(
		array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/phpcbf', '--standard=' . $standard ),
		array( STDIN, STDOUT, STDERR ),
		$pipes,
		dirname( __DIR__ )
	);
	if ( ! is_resource( $process ) ) {
		exit( 2 );
	}
	$status = proc_close( $process );
	if ( 0 !== $status && 1 !== $status ) {
		exit( $status > 1 ? $status : 2 );
	}
	$changed = $changed || 1 === $status;
}
exit( $changed ? 1 : 0 );
