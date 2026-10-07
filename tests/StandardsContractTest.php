<?php
/** Exercise actual PHPCS/PHPCBF selection using an isolated repository layout. */

use PHPUnit\Framework\TestCase;

final class StandardsContractTest extends TestCase {
	private $root;

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin standards ' . bin2hex( random_bytes( 8 ) );
		foreach ( array( 'bin', 'resources', 'tools', 'tests', 'fixtures' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
			mkdir( $this->root . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/StandardsFilter.php', $this->root . '/tools/StandardsFilter.php' );
		copy( dirname( __DIR__ ) . '/phpcs-tooling.xml.dist', $this->root . '/phpcs.xml.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', "<?php\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/unrelated', '<?php each( array() );' );
	}

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function tearDown(): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
			$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $this->root );
	}

	public function test_cli_compatibility_fails_and_unrelated_extensionless_file_is_excluded(): void {
		list( $status ) = $this->run_standard( 'phpcs' );
		$this->assertSame( 0, $status );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php each( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'bin/ran-admin-shell', $output );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
	}

	public function test_configured_paths_discover_incompatible_cli_without_cli_path_arguments(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php each( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs', false );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'bin/ran-admin-shell', $output );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
	}

	public function test_removing_filter_hides_actual_cli_diagnostic(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The unsupported native call remains inert while the locked checker observes CLI selection.
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php each( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs', false );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated tooling profile for the filter-removal control.
		$profile = file_get_contents( $this->root . '/phpcs.xml.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Remove only the custom filter to demonstrate why the independent guard must require it.
		file_put_contents( $this->root . '/phpcs.xml.dist', str_replace( '<arg name="filter" value="tools/StandardsFilter.php"/>', '', $profile ) );
		list( $status, $output ) = $this->run_standard( 'phpcs', false );
		$this->assertSame( 0, $status, $output );
		$this->assertStringNotContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
	}

	public function test_standalone_cli_cannot_rely_on_wordpress_polyfills(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php array_is_list( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.NewFunctions.array_is_listFound', $output );
	}

	public function test_fixer_selects_same_cli_and_is_repeatable(): void {
		// Exercise the adopted spacing rule with the same real check/fix selection.
		$cli = $this->root . '/bin/ran-admin-shell';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $cli, "<?php\n\$value=1;\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$before                  = file_get_contents( $cli );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'WordPress.WhiteSpace.OperatorSpacing', $output );
		list( $status ) = $this->run_standard( 'phpcbf' );
		$this->assertSame( 1, $status ); // PHPCBF uses 1 when all fixable errors were fixed.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$fixed = file_get_contents( $cli );
		$this->assertNotSame( $before, $fixed );
		list( $status ) = $this->run_standard( 'phpcs' );
		$this->assertSame( 0, $status );
		list( $status ) = $this->run_standard( 'phpcbf' );
		$this->assertSame( 0, $status );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( $fixed, file_get_contents( $cli ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( '<?php each( array() );', file_get_contents( $this->root . '/bin/unrelated' ) );
	}

	public function test_aggregate_fixer_continues_after_successful_first_scope_fixes(): void {
		copy( dirname( __DIR__ ) . '/tools/fix-standards.php', $this->root . '/tools/fix-standards.php' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
		mkdir( $this->root . '/vendor/bin', 0777, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Encode a controlled local path as PHP source for the isolated subprocess fixture; not debug output.
		file_put_contents( $this->root . '/vendor/bin/phpcbf', '<?php require ' . var_export( dirname( __DIR__ ) . '/vendor/bin/phpcbf', true ) . ';' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$tooling = file_get_contents( $this->root . '/phpcs.xml.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs-tooling.xml.dist', $tooling );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset name="Resource fixture"><file>resources</file><rule ref="WordPress.WhiteSpace.OperatorSpacing"/></ruleset>' );
		$source = "<?php\n\$value=1;\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/example.php', $source );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', $source );
		$log = $this->root . '/fix.log';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( array( PHP_BINARY, $this->root . '/tools/fix-standards.php' ), array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( 1, proc_close( $process ), file_get_contents( $log ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertNotSame( $source, file_get_contents( $this->root . '/resources/example.php' ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertNotSame( $source, file_get_contents( $this->root . '/bin/ran-admin-shell' ) );
	}

	public function test_common_convention_checks_current_and_future_standalone_paths(): void {
		$source = "<?php\nclass RAN_AdminShell_ProfileProbe extends Exception {\n public function ownedMethod( \$ownedValue ) { if ( \$ownedValue === 1 ) { return \$ownedValue; } return 0; }\n}\n";
		foreach ( array( 'bin/ran-admin-shell', 'tools/Future/probe.php', 'tests/Future/probe.php', 'fixtures/Future/probe.php' ) as $path ) {
			$file = $this->root . '/' . $path;
			if ( ! is_dir( dirname( $file ) ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
				mkdir( dirname( $file ), 0777, true );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $file, $source );
			list( $status, $output ) = $this->run_standard( 'phpcs', false );
			$this->assertNotSame( 0, $status );
			foreach ( array( 'RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase', 'WordPress.NamingConventions.ValidVariableName.', 'WordPress.PHP.YodaConditions.NotYoda' ) as $code ) {
				$this->assertStringContainsString( $code, $output, $path );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $file, "<?php\n" );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/probe.php', $source );
		copy( dirname( __DIR__ ) . '/phpcs.xml.dist', $this->root . '/phpcs.xml.dist' );
		list( $status, $output ) = $this->run_standard( 'phpcs', false );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase', $output );
	}

	private function run_standard( $binary, $explicit_paths = true ): array {
		// Explicit bin directory also proves that the custom filter is not an extensionless wildcard.
		$command = array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/' . $binary, '--standard=phpcs.xml.dist', '-s' );
		if ( $explicit_paths ) {
			$command = array_merge( $command, array( 'bin', 'tools' ) );
		}
		$log = $this->root . '/output.log';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( $command, array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes, $this->root );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		$status = proc_close( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		return array( $status, file_get_contents( $log ) );
	}
}
