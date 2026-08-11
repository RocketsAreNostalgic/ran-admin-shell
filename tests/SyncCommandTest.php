<?php
/** Synchronization contract tests. */

use PHPUnit\Framework\TestCase;
use RAN\AdminShell\Tool\SyncCommand;

final class SyncCommandTest extends TestCase {
	/** Temporary consumer root. */
	private $root;

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/ran-admin-shell-' . bin2hex( random_bytes( 8 ) );
		mkdir( $this->root, 0777, true );
		file_put_contents(
			$this->root . '/ran-admin-shell.json',
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
		file_put_contents(
			$this->root . '/composer.lock',
			json_encode(
				array(
					'packages'     => array(),
					'packages-dev' => array(
						array(
							'name'    => 'ran/admin-shell',
							'version' => 'dev-main',
							'source'  => array( 'type' => 'git', 'url' => 'https://github.com/RocketsAreNostalgic/ran-admin-shell.git', 'reference' => str_repeat( 'a', 40 ) ),
						),
					),
				)
			)
		);
	}

	protected function tearDown(): void {
		$this->remove_tree( $this->root );
	}

	/** Sync and check are deterministic, and drift is detected. */
	public function test_sync_check_and_drift_detection() {
		$config = $this->load_configuration();
		SyncCommand::sync( $config );

		$this->assertTrue( SyncCommand::check( $config, true ) );
		$first = file_get_contents( $config['provenance'] );
		SyncCommand::sync( $config );
		$this->assertSame( $first, file_get_contents( $config['provenance'] ) );

		file_put_contents( $config['css'], 'drift', FILE_APPEND );
		$this->assertFalse( SyncCommand::check( $config, false ) );
	}

	/** CLI rejects path traversal. */
	public function test_traversal_configuration_is_rejected() {
		file_put_contents(
			$this->root . '/unsafe.json',
			json_encode( array( 'schema' => 1, 'php' => '../outside.php', 'css' => 'assets/a.css', 'provenance' => 'provenance.json' ) )
		);

		$this->assertSame( 2, SyncCommand::main( array( 'ran-admin-shell', 'check', '--config=' . $this->root . '/unsafe.json' ) ) );
	}

	/** Load a valid configuration through the public command seam. */
	private function load_configuration() {
		return SyncCommand::load_configuration( $this->root . '/ran-admin-shell.json' );
	}

	/** Remove the isolated test tree. */
	private function remove_tree( $path ) {
		if ( ! is_dir( $path ) ) {
			return;
		}
		$items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $items as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		rmdir( $path );
	}
}
