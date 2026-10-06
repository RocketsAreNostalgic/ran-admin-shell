<?php
/** Prove newly maintained PHP cannot silently bypass direct analysis or standards. */

use PHPUnit\Framework\TestCase;

final class CoverageContractTest extends TestCase {
	private $root;

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin coverage ' . bin2hex( random_bytes( 8 ) );
		foreach ( array( 'bin', 'resources', 'tools' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
			mkdir( $this->root . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/check-coverage.php', $this->root . '/tools/check-coverage.php' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', "#!/usr/bin/env php\n<?php\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/admin-shell.php', '<?php return 1;' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/composer.json', '{"bin":["bin/ran-admin-shell"]}' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpstan.neon.dist', "parameters:\n    paths:\n        - bin/ran-admin-shell\n        - resources\n        - tools\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file></ruleset>' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs-tooling.xml.dist', '<ruleset><file>bin/ran-admin-shell</file><file>tools</file></ruleset>' );
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

	public function test_future_resource_is_covered_by_directory_scope(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/second.php', '<?php return 2;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_new_source_and_narrowed_analysis_fail(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/NewSource.php', '<?php return 3;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: NewSource.php', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		unlink( $this->root . '/NewSource.php' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
		mkdir( $this->root . '/feature/tests', 0777, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/feature/tests/NewSource.php', '<?php return 4;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: feature/tests/NewSource.php', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		unlink( $this->root . '/feature/tests/NewSource.php' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $this->root . '/feature/tests' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $this->root . '/feature' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/second.php', '<?php return 2;' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpstan.neon.dist', str_replace( "- resources\n", "- resources/admin-shell.php\n", $configuration ) );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: resources/second.php', $output );
	}

	public function test_standards_exclusion_fails(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><exclude-pattern>admin-shell\\.php$</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS excludes maintained PHP: resources/admin-shell.php', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><exclude-pattern type="relative">^resources/.*</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS excludes maintained PHP: resources/admin-shell.php', $output );
	}

	public function test_imported_analysis_and_additional_composer_cli_require_review(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$analysis = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpstan.neon.dist', "includes:\n    - shared.neon\n" . $analysis );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan coverage configuration is missing or needs review', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpstan.neon.dist', $analysis );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/second-command', "#!/usr/bin/env php\n<?php\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/composer.json', '{"bin":["bin/ran-admin-shell","bin/second-command"]}' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS extensionless CLI filter needs review: bin/second-command', $output );
	}

	public function test_development_paths_need_standards_but_not_production_analysis(): void {
		foreach ( array( 'tests', 'fixtures' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
			mkdir( $this->root . '/' . $directory );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $this->root . '/' . $directory . '/future.php', '<?php return 1;' );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS does not cover: ' . $directory . '/future.php', $output );
			$config = $this->root . '/phpcs-tooling.xml.dist';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
			file_put_contents( $config, str_replace( '</ruleset>', '<file>' . $directory . '</file></ruleset>', file_get_contents( $config ) ) );
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 0, $status, $output );
		}
	}

	public function test_blanket_and_legacy_suppressions_fail_without_executing_source(): void {
		foreach ( array( '// phpcs:ignoreFile', '// phpcs:disable -- blanket', '// phpcs:ignore', '// @codingStandardsIgnoreLine', '/* phpcs:disable */', '/** phpcs:ignore */' ) as $annotation ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $this->root . '/resources/probe.php', "<?php\n" . $annotation . "\nthrow new RuntimeException('must not execute');\n" );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'Blanket or legacy standards suppression: resources/probe.php', $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/probe.php', '<?php // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Specific diagnostic allowance.' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	private function check_coverage(): array {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( array( PHP_BINARY, $this->root . '/tools/check-coverage.php' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $pipes );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		$output = stream_get_contents( $pipes[1] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[1] );
		$output .= stream_get_contents( $pipes[2] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[2] );
		return array( proc_close( $process ), $output );
	}
}
