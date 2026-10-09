<?php
/** Exercise the committed package export through a real Composer consumer. */

use PHPUnit\Framework\TestCase;

final class DistributionContractTest extends TestCase {
	/** @var string */
	private $root;

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin distribution ' . bin2hex( random_bytes( 8 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
		mkdir( $this->root . '/consumer', 0777, true );
	}

	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPUnit lifecycle override.
	protected function tearDown(): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
			$file->isDir() && ! $file->isLink() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
		rmdir( $this->root );
	}

	public function test_export_installs_syncs_and_checks_immutable_consumer_resources(): void {
		$repository                 = dirname( __DIR__ );
		list( $status, $reference ) = $this->run_command( array( 'git', 'rev-parse', 'HEAD' ), $repository );
		$this->assertSame( 0, $status, $reference );
		$reference               = trim( $reference );
		$archive                 = $this->root . '/package.zip';
		list( $status, $output ) = $this->run_command( array( 'git', 'archive', '--format=zip', '--output=' . $archive, $reference ), $repository );
		$this->assertSame( 0, $status, $output );

		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $archive ) );
		foreach ( array( 'bin/ran-admin-shell', 'tools/SyncCommand.php', 'resources/admin-shell.php', 'resources/admin-shell.css', 'LICENSE', 'composer.json' ) as $path ) {
			$this->assertNotFalse( $zip->locateName( $path ), $path );
		}
		foreach ( array( 'tests/', 'fixtures/', 'docs/', '.github/', '.agents/', 'AGENTS.md', 'vendor/', 'phpcs.xml.dist', 'phpcs-tooling.xml.dist', 'phpstan.neon.dist', 'phpunit.xml.dist', 'tools/check-coverage.php', 'node_modules/', 'package.json', 'pnpm-lock.yaml', 'pnpm-workspace.yaml', '.node-version', '.stylelintrc.json' ) as $path ) {
			$this->assertFalse( $zip->locateName( $path ), $path );
		}
		$package = json_decode( $zip->getFromName( 'composer.json' ), true, 512, JSON_THROW_ON_ERROR );
		$zip->close();
		// Build-time resources must never acquire Composer runtime loading.
		foreach ( array( 'autoload', 'include-path', 'target-dir' ) as $field ) {
			$this->assertArrayNotHasKey( $field, $package, $field );
		}
		$this->assertSame( 'library', $package['type'] );
		$package['version'] = 'dev-acceptance';
		$package['source']  = array(
			'type'      => 'git',
			'url'       => $repository,
			'reference' => $reference,
		);
		$package['dist']    = array(
			'type'      => 'zip',
			'url'       => 'file://' . $archive,
			'reference' => $reference,
			'shasum'    => sha1_file( $archive ),
		);
		$consumer           = $this->root . '/consumer';
		$this->write_json(
			$consumer . '/composer.json',
			array(
				'name'         => 'ran/distribution-fixture',
				'require-dev'  => array( 'ran/admin-shell' => 'dev-acceptance' ),
				'repositories' => array(
					array(
						'type'    => 'package',
						'package' => $package,
					),
					array( 'packagist.org' => false ),
				),
				'config'       => array( 'allow-plugins' => false ),
			)
		);
		$composer                = getenv( 'COMPOSER_BINARY' );
		$composer                = $composer ? $composer : 'composer';
		list( $status, $output ) = $this->run_command( array( $composer, 'update', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--prefer-dist' ), $consumer );
		$this->assertSame( 0, $status, $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$installed = json_decode( file_get_contents( $consumer . '/vendor/composer/installed.json' ), true, 512, JSON_THROW_ON_ERROR );
		$this->assertCount( 1, $installed['packages'] );
		$this->assertSame( 'dist', $installed['packages'][0]['installation-source'] );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/ran/admin-shell/tests' );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/ran/admin-shell/.git' );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/ran/admin-shell/.agents' );
		$this->assertFileDoesNotExist( $consumer . '/vendor/ran/admin-shell/AGENTS.md' );
		$this->assertTrue( $zip->open( $archive ) );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
		for ( $index = 0; $index < $zip->numFiles; ++$index ) {
			$path = $zip->getNameIndex( $index );
			if ( '/' !== substr( $path, -1 ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
				$this->assertSame( $zip->getFromIndex( $index ), file_get_contents( $consumer . '/vendor/ran/admin-shell/' . $path ), $path );
			}
		}
		$zip->close();
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/phpstan' );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/phpunit' );
		$this->assertFalse( is_link( $consumer . '/vendor/ran/admin-shell' ) );
		$this->write_json(
			$consumer . '/ran-admin-shell.json',
			array(
				'schema'     => 1,
				'php'        => 'includes/generated/ran-admin-shell.php',
				'css'        => 'assets/ran-admin-shell.css',
				'provenance' => 'includes/generated/ran-admin-shell.provenance.json',
			)
		);
		$cli                     = array( PHP_BINARY, $consumer . '/vendor/bin/ran-admin-shell' );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'sync' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		$provenance_path = $consumer . '/includes/generated/ran-admin-shell.provenance.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$provenance_bytes = file_get_contents( $provenance_path );
		$provenance       = json_decode( $provenance_bytes, true, 512, JSON_THROW_ON_ERROR );
		$this->assertSame( $reference, $provenance['reference'] );
		foreach ( $provenance['files'] as $path => $digest ) {
			$this->assertSame( 'sha256:' . hash_file( 'sha256', $consumer . '/' . $path ), $digest );
		}
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'sync' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( $provenance_bytes, file_get_contents( $provenance_path ) );
		$css = $consumer . '/assets/ran-admin-shell.css';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$original_css = file_get_contents( $css );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $css, 'drift', FILE_APPEND );
		list( $status ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 1, $status );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $css, $original_css );

		$lock_path = $consumer . '/composer.lock';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$lock_bytes = file_get_contents( $lock_path );
		$lock       = json_decode( $lock_bytes, true, 512, JSON_THROW_ON_ERROR );
		$lock['packages-dev'][0]['source']['reference'] = str_repeat( '0', 40 );
		$this->write_json( $lock_path, $lock );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 2, $status, $output );
		$this->assertStringContainsString( 'Installed package reference does not match composer.lock', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $lock_path, $lock_bytes );
		list( $status, $output ) = $this->run_command( array( $composer, 'install', '--no-dev', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts' ), $consumer );
		$this->assertSame( 0, $status, $output );
		// Composer removed this directory in a child process; discard the earlier stat result.
		clearstatcache( true, $consumer . '/vendor/ran/admin-shell' );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/ran/admin-shell' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$this->assertSame( $provenance_bytes, file_get_contents( $provenance_path ) );
		foreach ( $provenance['files'] as $path => $digest ) {
			$this->assertSame( 'sha256:' . hash_file( 'sha256', $consumer . '/' . $path ), $digest );
		}
	}

	/**
	 * @param string $path Owned fixture path.
	 * @param array<string, mixed> $data Fixture manifest or configuration.
	 */
	private function write_json( $path, array $data ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
		file_put_contents( $path, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ) . "\n" );
	}

	/**
	 * @param list<string> $command Executable argument vector.
	 * @param string $directory Consumer working directory.
	 * @return array{int, string|false}
	 */
	private function run_command( array $command, $directory ): array {
		$log = $this->root . '/command.log';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( $command, array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes, $directory );
		$this->assertIsResource( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the owned subprocess pipe; WordPress filesystem wrappers do not own this stream.
		fclose( $pipes[0] );
		$status = proc_close( $process );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		return array( $status, file_get_contents( $log ) );
	}
}
