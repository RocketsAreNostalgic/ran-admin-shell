<?php
/** Exercise actual PHPCS/PHPCBF selection using an isolated repository layout. */

use PHPUnit\Framework\TestCase;

final class StandardsContractTest extends TestCase {
	private $root;

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin standards ' . bin2hex( random_bytes( 8 ) );
		foreach ( array( 'bin', 'resources', 'tools', 'tests' ) as $directory ) {
			mkdir( $this->root . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/StandardsFilter.php', $this->root . '/tools/StandardsFilter.php' );
		copy( dirname( __DIR__ ) . '/phpcs-tooling.xml.dist', $this->root . '/phpcs.xml.dist' );
		file_put_contents( $this->root . '/bin/ran-admin-shell', "<?php\n" );
		file_put_contents( $this->root . '/bin/unrelated', '<?php each( array() );' );
	}

	protected function tearDown(): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		rmdir( $this->root );
	}

	public function test_cli_compatibility_fails_and_unrelated_extensionless_file_is_excluded(): void {
		list( $status ) = $this->run_standard( 'phpcs' );
		$this->assertSame( 0, $status );
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php each( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'bin/ran-admin-shell', $output );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
	}

	public function test_configured_paths_discover_incompatible_cli_without_cli_path_arguments(): void {
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php each( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs', false );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'bin/ran-admin-shell', $output );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.RemovedFunctions', $output );
	}

	public function test_standalone_cli_cannot_rely_on_wordpress_polyfills(): void {
		file_put_contents( $this->root . '/bin/ran-admin-shell', '<?php array_is_list( array() );' );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCompatibility.FunctionUse.NewFunctions.array_is_listFound', $output );
	}

	public function test_fixer_selects_same_cli_and_is_repeatable(): void {
		// A fixture-only fixable sniff makes selection observable without adding production style policy.
		$config = $this->root . '/phpcs.xml.dist';
		file_put_contents( $config, str_replace( '</ruleset>', '<rule ref="Generic.WhiteSpace.DisallowTabIndent"/></ruleset>', file_get_contents( $config ) ) );
		$cli = $this->root . '/bin/ran-admin-shell';
		file_put_contents( $cli, "<?php\n\t\$value = 1;\n" );
		$before = file_get_contents( $cli );
		list( $status, $output ) = $this->run_standard( 'phpcs' );
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'Generic.WhiteSpace.DisallowTabIndent', $output );
		list( $status ) = $this->run_standard( 'phpcbf' );
		$this->assertSame( 1, $status ); // PHPCBF uses 1 when all fixable errors were fixed.
		$fixed = file_get_contents( $cli );
		$this->assertNotSame( $before, $fixed );
		list( $status ) = $this->run_standard( 'phpcs' );
		$this->assertSame( 0, $status );
		list( $status ) = $this->run_standard( 'phpcbf' );
		$this->assertSame( 0, $status );
		$this->assertSame( $fixed, file_get_contents( $cli ) );
		$this->assertSame( '<?php each( array() );', file_get_contents( $this->root . '/bin/unrelated' ) );
	}

	public function test_aggregate_fixer_continues_after_successful_first_scope_fixes(): void {
		copy( dirname( __DIR__ ) . '/tools/fix-standards.php', $this->root . '/tools/fix-standards.php' );
		mkdir( $this->root . '/vendor/bin', 0777, true );
		file_put_contents( $this->root . '/vendor/bin/phpcbf', '<?php require ' . var_export( dirname( __DIR__ ) . '/vendor/bin/phpcbf', true ) . ';' );
		$tooling = str_replace( '</ruleset>', '<rule ref="Generic.WhiteSpace.DisallowTabIndent"/></ruleset>', file_get_contents( $this->root . '/phpcs.xml.dist' ) );
		file_put_contents( $this->root . '/phpcs-tooling.xml.dist', $tooling );
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset name="Resource fixture"><file>resources</file><rule ref="Generic.WhiteSpace.DisallowTabIndent"/></ruleset>' );
		$source = "<?php\n\t\$value = 1;\n";
		file_put_contents( $this->root . '/resources/example.php', $source );
		file_put_contents( $this->root . '/bin/ran-admin-shell', $source );
		$log = $this->root . '/fix.log';
		$process = proc_open( array( PHP_BINARY, $this->root . '/tools/fix-standards.php' ), array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		$this->assertSame( 1, proc_close( $process ), file_get_contents( $log ) );
		$this->assertNotSame( $source, file_get_contents( $this->root . '/resources/example.php' ) );
		$this->assertNotSame( $source, file_get_contents( $this->root . '/bin/ran-admin-shell' ) );
	}

	private function run_standard( $binary, $explicit_paths = true ): array {
		// Explicit bin directory also proves that the custom filter is not an extensionless wildcard.
		$command = array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/' . $binary, '--standard=phpcs.xml.dist', '-s' );
		if ( $explicit_paths ) {
			$command = array_merge( $command, array( 'bin', 'tools' ) );
		}
		$log = $this->root . '/output.log';
		$process = proc_open( $command, array( array( 'pipe', 'r' ), array( 'file', $log, 'w' ), array( 'file', $log, 'a' ) ), $pipes, $this->root );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		$status = proc_close( $process );
		return array( $status, file_get_contents( $log ) );
	}
}
