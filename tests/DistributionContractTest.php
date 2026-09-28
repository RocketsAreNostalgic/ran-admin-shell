<?php
/** Exercise the committed package export through a real Composer consumer. */

use PHPUnit\Framework\TestCase;

final class DistributionContractTest extends TestCase {
	private $root;

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin distribution ' . bin2hex( random_bytes( 8 ) );
		mkdir( $this->root . '/consumer', 0777, true );
	}

	protected function tearDown(): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			$file->isDir() && ! $file->isLink() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		rmdir( $this->root );
	}

	public function test_export_installs_syncs_and_checks_immutable_consumer_resources(): void {
		$repository = dirname( __DIR__ );
		list( $status, $reference ) = $this->run_command( array( 'git', 'rev-parse', 'HEAD' ), $repository );
		$this->assertSame( 0, $status, $reference );
		$reference = trim( $reference );
		$archive = $this->root . '/package.zip';
		list( $status, $output ) = $this->run_command( array( 'git', 'archive', '--format=zip', '--output=' . $archive, $reference ), $repository );
		$this->assertSame( 0, $status, $output );

		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $archive ) );
		foreach ( array( 'bin/ran-admin-shell', 'tools/SyncCommand.php', 'resources/admin-shell.php', 'resources/admin-shell.css', 'LICENSE', 'composer.json' ) as $path ) {
			$this->assertNotFalse( $zip->locateName( $path ), $path );
		}
		foreach ( array( 'tests/', 'fixtures/', 'docs/', '.github/', 'vendor/', 'phpcs.xml.dist', 'phpcs-tooling.xml.dist', 'phpstan.neon.dist', 'phpunit.xml.dist' ) as $path ) {
			$this->assertFalse( $zip->locateName( $path ), $path );
		}
		$package = json_decode( $zip->getFromName( 'composer.json' ), true, 512, JSON_THROW_ON_ERROR );
		$zip->close();
		$package = array_intersect_key( $package, array_flip( array( 'name', 'description', 'type', 'license', 'require', 'bin' ) ) );
		$package['version'] = 'dev-acceptance';
		$package['source'] = array( 'type' => 'git', 'url' => $repository, 'reference' => $reference );
		$package['dist'] = array( 'type' => 'zip', 'url' => 'file://' . $archive, 'reference' => $reference, 'shasum' => sha1_file( $archive ) );
		$consumer = $this->root . '/consumer';
		$this->write_json( $consumer . '/composer.json', array(
			'name' => 'ran/distribution-fixture',
			'require-dev' => array( 'ran/admin-shell' => 'dev-acceptance' ),
			'repositories' => array( array( 'type' => 'package', 'package' => $package ), array( 'packagist.org' => false ) ),
			'config' => array( 'allow-plugins' => false ),
		) );
		$composer = getenv( 'COMPOSER_BINARY' ) ?: 'composer';
		list( $status, $output ) = $this->run_command( array( $composer, 'update', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--prefer-dist' ), $consumer );
		$this->assertSame( 0, $status, $output );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/phpstan' );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/phpunit' );
		$this->assertFalse( is_link( $consumer . '/vendor/ran/admin-shell' ) );
		$this->write_json( $consumer . '/ran-admin-shell.json', array(
			'schema' => 1,
			'php' => 'includes/generated/ran-admin-shell.php',
			'css' => 'assets/ran-admin-shell.css',
			'provenance' => 'includes/generated/ran-admin-shell.provenance.json',
		) );
		$cli = array( PHP_BINARY, $consumer . '/vendor/bin/ran-admin-shell' );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'sync' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		$provenance_path = $consumer . '/includes/generated/ran-admin-shell.provenance.json';
		$provenance_bytes = file_get_contents( $provenance_path );
		$provenance = json_decode( $provenance_bytes, true, 512, JSON_THROW_ON_ERROR );
		$this->assertSame( $reference, $provenance['reference'] );
		foreach ( $provenance['files'] as $path => $digest ) {
			$this->assertSame( 'sha256:' . hash_file( 'sha256', $consumer . '/' . $path ), $digest );
		}
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'sync' ) ), $consumer );
		$this->assertSame( 0, $status, $output );
		$this->assertSame( $provenance_bytes, file_get_contents( $provenance_path ) );
		$css = $consumer . '/assets/ran-admin-shell.css';
		$original_css = file_get_contents( $css );
		file_put_contents( $css, 'drift', FILE_APPEND );
		list( $status ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 1, $status );
		file_put_contents( $css, $original_css );

		$lock_path = $consumer . '/composer.lock';
		$lock_bytes = file_get_contents( $lock_path );
		$lock = json_decode( $lock_bytes, true, 512, JSON_THROW_ON_ERROR );
		$lock['packages-dev'][0]['source']['reference'] = str_repeat( '0', 40 );
		$this->write_json( $lock_path, $lock );
		list( $status, $output ) = $this->run_command( array_merge( $cli, array( 'check', '--immutable' ) ), $consumer );
		$this->assertSame( 2, $status, $output );
		$this->assertStringContainsString( 'Installed package reference does not match composer.lock', $output );
		file_put_contents( $lock_path, $lock_bytes );
		list( $status, $output ) = $this->run_command( array( $composer, 'install', '--no-dev', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts' ), $consumer );
		$this->assertSame( 0, $status, $output );
		$this->assertDirectoryDoesNotExist( $consumer . '/vendor/ran/admin-shell' );
		$this->assertSame( $provenance_bytes, file_get_contents( $provenance_path ) );
		foreach ( $provenance['files'] as $path => $digest ) {
			$this->assertSame( 'sha256:' . hash_file( 'sha256', $consumer . '/' . $path ), $digest );
		}
	}

	private function write_json( $path, array $data ): void {
		file_put_contents( $path, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ) . "\n" );
	}

	private function run_command( array $command, $directory ): array {
		$log = $this->root . '/command.log';
		$process = proc_open( $command, array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes, $directory );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		$status = proc_close( $process );
		return array( $status, file_get_contents( $log ) );
	}
}
