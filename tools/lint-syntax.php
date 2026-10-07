<?php
/** Parse maintained PHP, including the extensionless CLI, without executing it. */

$root = dirname( __DIR__ );

try {
	$files = array( $root . '/bin/ran-admin-shell' );
	foreach ( array( 'resources', 'tools', 'tests', 'fixtures' ) as $directory ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root . '/' . $directory, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() ) {
				$files[] = $file->getPathname();
			}
		}
	}
	sort( $files );
	foreach ( $files as $file ) {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			throw new RuntimeException( 'Missing or unreadable PHP source: ' . $file );
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( array( PHP_BINARY, '-l', $file ), array( STDIN, STDOUT, STDERR ), $pipes );
		if ( ! is_resource( $process ) || 0 !== proc_close( $process ) ) {
			throw new RuntimeException( 'PHP syntax check failed: ' . $file );
		}
	}
} catch ( Throwable $error ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
	fwrite( STDERR, $error->getMessage() . PHP_EOL );
	exit( 1 );
}
