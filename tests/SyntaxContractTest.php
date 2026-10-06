<?php
/** Failure-path proof for the repository parser sweep. */

use PHPUnit\Framework\TestCase;

final class SyntaxContractTest extends TestCase {
	private $root;

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin syntax ' . bin2hex( random_bytes( 8 ) );
		foreach ( array( 'bin', 'resources', 'tools', 'tests', 'fixtures', 'vendor' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
			mkdir( $this->root . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/lint-syntax.php', $this->root . '/tools/lint-syntax.php' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', "#!/usr/bin/env php\n<?php\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/space name.php', '<?php return 1;' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/vendor/broken.php', '<?php broken syntax' );
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

	public function test_valid_sources_and_spaced_paths_pass_without_parsing_vendor(): void {
		$this->assertSame( 0, $this->lint() );
	}

	/** @dataProvider invalid_paths */
	public function test_invalid_maintained_sources_fail( $path ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/' . $path, '<?php broken syntax' );
		$this->assertNotSame( 0, $this->lint() );
	}

	public function invalid_paths(): array {
		return array_map(
			static function ( $path ) {
				return array( $path );
			},
			array( 'bin/ran-admin-shell', 'resources/space name.php', 'tools/bad.php', 'tests/bad.php', 'fixtures/bad.php' )
		);
	}

	public function test_missing_cli_fails(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		unlink( $this->root . '/bin/ran-admin-shell' );
		$this->assertNotSame( 0, $this->lint() );
	}

	public function test_missing_discovery_root_fails(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $this->root . '/fixtures' );
		$this->assertNotSame( 0, $this->lint() );
	}

	private function lint(): int {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( array( PHP_BINARY, $this->root . '/tools/lint-syntax.php' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $pipes );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		stream_get_contents( $pipes[1] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[1] );
		stream_get_contents( $pipes[2] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[2] );
		return proc_close( $process );
	}
}
