<?php
/** Prove newly maintained PHP cannot silently bypass direct analysis or standards. */

use PHPUnit\Framework\TestCase;

final class CoverageContractTest extends TestCase {
	private $root;

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/admin coverage ' . bin2hex( random_bytes( 8 ) );
		foreach ( array( 'bin', 'resources', 'tools' ) as $directory ) {
			mkdir( $this->root . '/' . $directory, 0777, true );
		}
		copy( dirname( __DIR__ ) . '/tools/check-coverage.php', $this->root . '/tools/check-coverage.php' );
		file_put_contents( $this->root . '/bin/ran-admin-shell', "#!/usr/bin/env php\n<?php\n" );
		file_put_contents( $this->root . '/resources/admin-shell.php', '<?php return 1;' );
		file_put_contents( $this->root . '/composer.json', '{"bin":["bin/ran-admin-shell"]}' );
		file_put_contents( $this->root . '/phpstan.neon.dist', "parameters:\n    paths:\n        - bin/ran-admin-shell\n        - resources\n        - tools\n" );
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file></ruleset>' );
		file_put_contents( $this->root . '/phpcs-tooling.xml.dist', '<ruleset><file>bin/ran-admin-shell</file><file>tools</file></ruleset>' );
	}

	protected function tearDown(): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
		}
		rmdir( $this->root );
	}

	public function test_future_resource_is_covered_by_directory_scope(): void {
		file_put_contents( $this->root . '/resources/second.php', '<?php return 2;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_new_source_and_narrowed_analysis_fail(): void {
		file_put_contents( $this->root . '/NewSource.php', '<?php return 3;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: NewSource.php', $output );
		unlink( $this->root . '/NewSource.php' );
		mkdir( $this->root . '/feature/tests', 0777, true );
		file_put_contents( $this->root . '/feature/tests/NewSource.php', '<?php return 4;' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: feature/tests/NewSource.php', $output );
		unlink( $this->root . '/feature/tests/NewSource.php' );
		rmdir( $this->root . '/feature/tests' );
		rmdir( $this->root . '/feature' );

		file_put_contents( $this->root . '/resources/second.php', '<?php return 2;' );
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		file_put_contents( $this->root . '/phpstan.neon.dist', str_replace( "- resources\n", "- resources/admin-shell.php\n", $configuration ) );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPStan does not directly cover: resources/second.php', $output );
	}

	public function test_standards_exclusion_fails(): void {
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><exclude-pattern>admin-shell\\.php$</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS excludes maintained PHP: resources/admin-shell.php', $output );
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><exclude-pattern type="relative">^resources/.*</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS excludes maintained PHP: resources/admin-shell.php', $output );
	}

	private function check_coverage(): array {
		$process = proc_open( array( PHP_BINARY, $this->root . '/tools/check-coverage.php' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $pipes );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		$output = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );
		$output .= stream_get_contents( $pipes[2] );
		fclose( $pipes[2] );
		return array( proc_close( $process ), $output );
	}
}
