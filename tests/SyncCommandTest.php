<?php
/** Synchronization contract tests. */

use PHPUnit\Framework\TestCase;
use RAN\AdminShell\Tool\SyncCommand;

final class SyncCommandTest extends TestCase {
	/** Temporary consumer root. */
	/** @var string */
	private $root;

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/ran-admin-shell-' . bin2hex( random_bytes( 8 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
		mkdir( $this->root, 0777, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents(
			$this->root . '/ran-admin-shell.json',
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
			json_encode(
				array(
					'schema'     => 1,
					'php'        => 'includes/generated/ran-admin-shell.php',
					'css'        => 'assets/ran-admin-shell.css',
					'provenance' => 'includes/generated/ran-admin-shell.provenance.json',
				),
				JSON_PRETTY_PRINT
			)
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents(
			$this->root . '/composer.lock',
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
			json_encode(
				array(
					'packages'     => array(),
					'packages-dev' => array(
						array(
							'name'    => 'ran/admin-shell',
							'version' => 'dev-main',
							'source'  => array(
								'type'      => 'git',
								'url'       => 'https://github.com/RocketsAreNostalgic/ran-admin-shell.git',
								'reference' => str_repeat( 'a', 40 ),
							),
						),
					),
				)
			)
		);
	}

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function tearDown(): void {
		$this->remove_tree( $this->root );
	}

	/** Sync and check are deterministic, and drift is detected. */
	public function test_sync_check_and_drift_detection(): void {
		$config = $this->load_configuration();
		SyncCommand::sync( $config );

		$this->assertTrue( SyncCommand::check( $config, false ) );
		$this->assertStringContainsString(
			"if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}",
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
			(string) file_get_contents( $config['php'] )
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$first = file_get_contents( $config['provenance'] );
		SyncCommand::sync( $config );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( $first, file_get_contents( $config['provenance'] ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $config['css'], 'drift', FILE_APPEND );
		$this->assertFalse( SyncCommand::check( $config, false ) );
	}

	/** Immutable mode requires matching Composer installed metadata. */
	public function test_immutable_check_rejects_missing_installed_metadata(): void {
		$config = $this->load_configuration();
		SyncCommand::sync( $config );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'does not match composer.lock' );
		SyncCommand::check( $config, true );
	}

	/** CLI rejects path traversal. */
	public function test_traversal_configuration_is_rejected(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents(
			$this->root . '/unsafe.json',
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
			json_encode(
				array(
					'schema'     => 1,
					'php'        => '../outside.php',
					'css'        => 'assets/a.css',
					'provenance' => 'provenance.json',
				)
			)
		);

		$this->assertSame( 2, SyncCommand::main( array( 'ran-admin-shell', 'check', '--config=' . $this->root . '/unsafe.json' ) ) );
	}

	/** Missing CLI arguments produce an intentional failure, not an undefined-variable error. */
	public function test_cli_rejects_unavailable_arguments(): void {
		$log     = $this->root . '/cli.log';
		$wrapper = $this->root . '/without-argv.php';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Encode a controlled local path as PHP source for the isolated subprocess fixture; not debug output.
		file_put_contents( $wrapper, '<?php unset($argv); require ' . var_export( dirname( __DIR__ ) . '/bin/ran-admin-shell', true ) . ';' );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open(
			array( PHP_BINARY, $wrapper ),
			array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ),
			$pipes
		);
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		$this->assertSame( 2, proc_close( $process ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( "RAN Admin Shell requires registered CLI arguments (register_argc_argv).\n", file_get_contents( $log ) );
	}

	public function test_unhashable_canonical_resource_fails_the_real_check_without_changing_consumer_bytes(): void {
		$config = $this->load_configuration();
		SyncCommand::sync( $config );
		$this->assertTrue( SyncCommand::check( $config ) );
		$package = $this->root . '/package';
		foreach ( array( 'bin', 'tools', 'resources/admin-shell.php' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create only the owned isolated package layout, including a deliberately unhashable resource directory.
			mkdir( $package . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/SyncCommand.php', $package . '/tools/SyncCommand.php' );
		copy( dirname( __DIR__ ) . '/bin/ran-admin-shell', $package . '/bin/ran-admin-shell' );
		copy( dirname( __DIR__ ) . '/resources/admin-shell.css', $package . '/resources/admin-shell.css' );
		$before = array();
		foreach ( array( 'php', 'css', 'provenance' ) as $key ) {
			$before[ $key ] = hash_file( 'sha256', $config[ $key ] );
		}
		$log = $this->root . '/hash-failure.log';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the exact CLI and synchronization implementation in the owned package mirror and observe its failure status.
		$process = proc_open( array( PHP_BINARY, $package . '/bin/ran-admin-shell', 'check', '--config=' . $this->root . '/ran-admin-shell.json' ), array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess input pipe before observing process completion.
		fclose( $pipes[0] );
		$this->assertSame( 1, proc_close( $process ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the owned process log after completion to verify real drift failure propagation.
		$output = file_get_contents( $log );
		$this->assertIsString( $output );
		$this->assertStringContainsString( 'RAN Admin Shell resource drift detected.', $output );
		foreach ( array( 'php', 'css', 'provenance' ) as $key ) {
			$this->assertSame( $before[ $key ], hash_file( 'sha256', $config[ $key ] ) );
		}
	}

	/** @return array{php:string,css:string,provenance:string,root:string} */
	private function load_configuration() {
		return SyncCommand::load_configuration( $this->root . '/ran-admin-shell.json' );
	}

	/**
	 * Remove the isolated test tree.
	 *
	 * @param string $path Owned fixture path.
	 * @return void
	 */
	private function remove_tree( $path ) {
		if ( ! is_dir( $path ) ) {
			return;
		}
		$items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $items as $item ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $path );
	}
}
