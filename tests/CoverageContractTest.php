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
		symlink( dirname( __DIR__ ) . '/vendor', $this->root . '/vendor' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/bin/ran-admin-shell', "#!/usr/bin/env php\n<?php\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/admin-shell.php', '<?php return 1;' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/composer.json', '{"bin":["bin/ran-admin-shell"]}' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpstan.neon.dist', "parameters:\n    level: 5\n    paths:\n        - bin/ran-admin-shell\n        - resources\n        - tools\n" );
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
			( $file->isDir() && ! $file->isLink() ) ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
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

	public function test_analysis_level_cannot_drop_below_five(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated analysis configuration for the lower-level regression.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Lowering the real checker level must fail the maintained-file contract.
		file_put_contents( $this->root . '/phpstan.neon.dist', str_replace( 'level: 5', 'level: 4', $configuration ) );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'Maintained PHP requires PHPStan level 5 or higher.', $output );
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
		$this->assertStringContainsString( 'PHP entrypoint requires explicit checker support: bin/second-command', $output );
	}

	public function test_development_paths_need_direct_analysis_and_standalone_standards(): void {
		foreach ( array( 'tests', 'fixtures' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
			mkdir( $this->root . '/' . $directory );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $this->root . '/' . $directory . '/future.php', '<?php return 1;' );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPStan does not directly cover: ' . $directory . '/future.php', $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Admit the entire development role; future split files must be selected automatically.
			file_put_contents( $this->root . '/phpstan.neon.dist', '        - ' . $directory . "\n", FILE_APPEND );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS does not cover: ' . $directory . '/future.php', $output );
			$resource_config = $this->root . '/phpcs.xml.dist';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Exercise a misrouted fixture profile in the isolated checker layout.
			file_put_contents( $resource_config, str_replace( '</ruleset>', '<file>' . $directory . '</file></ruleset>', file_get_contents( $resource_config ) ) );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS does not cover: ' . $directory . '/future.php in phpcs-tooling.xml.dist', $output );
			$config = $this->root . '/phpcs-tooling.xml.dist';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
			file_put_contents( $config, str_replace( '</ruleset>', '<file>' . $directory . '</file></ruleset>', file_get_contents( $config ) ) );
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 0, $status, $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Save the exact isolated profile before testing its own exclusion.
			$tooling_config = file_get_contents( $config );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Resource coverage must not rescue a standalone exclusion.
			file_put_contents( $config, str_replace( '</ruleset>', '<exclude-pattern>' . $directory . '/future.php</exclude-pattern></ruleset>', $tooling_config ) );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS excludes maintained PHP: ' . $directory . '/future.php', $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the isolated standalone profile for the next scenario.
			file_put_contents( $config, $tooling_config );
		}
	}

	public function test_blanket_and_legacy_suppressions_fail_without_executing_source(): void {
		foreach ( array( '// phpcs:ignoreFile', '// phpcs:disable -- blanket', '// phpcs:ignore', '// @codingStandardsIgnoreLine', '// @codingstandardsignoreline', '/* phpcs:disable */', '/** phpcs:ignore */', '// phpcs:disable WordPress.WP.AlternativeFunctions', '/* phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged */', "// phpcs:disable WordPress.WP.AlternativeFunctions\n// phpcs:enable WordPress.WP.AlternativeFunctions" ) as $annotation ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
			file_put_contents( $this->root . '/resources/probe.php', "<?php\n" . $annotation . "\nthrow new RuntimeException('must not execute');\n" );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'Blanket, persistent or legacy standards suppression: resources/probe.php', $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/resources/probe.php', '<?php // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Specific diagnostic allowance.' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_case_variants_and_broad_ignores_cannot_hide_real_checker_findings(): void {
		$code = 'WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Configure the real locked checker against inert native-operation fixtures.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><rule ref="WordPress.WP.AlternativeFunctions"/></ruleset>' );
		foreach ( array( '// PHPCS:DISABLE WordPress', '// phpcs:ignorefile', '// phpcs:ignorefileXYZ', '// PHPCS:IGNORE', '// phpcs:ignore WordPress -- Hide a standard.', '// phpcs:ignore WordPress.WP.AlternativeFunctions -- Hide a category.', '// phpcs:ignore ' . $code, '// phpcs:ignore ' . $code . ' -- ', '/* phpcs:ignore ' . $code . ' -- */' ) as $annotation ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The fixture is inspected by PHPCS and coverage, never executed.
			file_put_contents(
				$this->root . '/resources/probe.php',
				'<?php
' . $annotation . "
file_get_contents( 'fixture' );
"
			);
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertSame( 0, $status, $annotation . $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status, $annotation );
			$this->assertMatchesRegularExpression( '/(?:standards suppression:|concrete reason:)/', $output );
		}
	}

	public function test_inline_property_changes_cannot_hide_checker_diagnostics(): void {
		$rules = '<ruleset><file>resources</file><rule ref="WordPress.NamingConventions.PrefixAllGlobals"><properties><property name="prefixes" type="array"><element value="approved"/></property></properties></rule></ruleset>';
		foreach ( array( 'phpcs:set', 'PHPCS:SET', '@codingStandardsChangeSetting', '@CODINGSTANDARDSCHANGESETTING' ) as $directive ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Configure the isolated real checker to require the approved prefix.
			file_put_contents( $this->root . '/phpcs.xml.dist', $rules );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Prove the unmodified diagnostic is active before testing the bypass.
			file_put_contents( $this->root . '/resources/probe.php', '<?php function rogue_function() {}' );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertNotSame( 0, $status, $output );
			$this->assertStringContainsString( 'NonPrefixedFunctionFound', $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The inert comment overrides the locked checker's prefix property.
			file_put_contents( $this->root . '/resources/probe.php', "<?php\n// " . $directive . " WordPress.NamingConventions.PrefixAllGlobals prefixes rogue\nfunction rogue_function() {}\n" );
			list( $status, $output ) = $this->check_coverage( true );
			// Locked PHPCS ignores uppercase legacy syntax; the independent guard rejects it too.
			$this->assertSame( '@CODINGSTANDARDSCHANGESETTING' === $directive ? 1 : 0, $status, $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Remove test-only XML customization so rejection must be for the source directive itself.
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file></ruleset>' );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'Blanket, persistent or legacy standards suppression: resources/probe.php', $output );
		}
	}

	public function test_exact_ignore_with_reason_leaves_next_line_and_other_diagnostics_checked(): void {
		$code = 'WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Configure the real checker to observe exact diagnostic boundaries.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><rule ref="WordPress.WP.AlternativeFunctions"/></ruleset>' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Emit inert PHP with a justified first read and two unsuppressed native operations.
		file_put_contents(
			$this->root . '/resources/probe.php',
			'<?php
// PHPCS:IGNORE ' . $code . " -- Read controlled fixture bytes without WordPress.
file_get_contents( 'one' );
file_get_contents( 'two' );
json_encode( array() );
"
		);
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
		list( $status, $output ) = $this->check_coverage( true );
		$this->assertNotSame( 0, $status );
		$report   = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
		$messages = array_values( $report['files'] )[0]['messages'];
		$this->assertSame( array( 4, 5 ), array_column( $messages, 'line' ) );
		$this->assertSame( array( $code, 'WordPress.WP.AlternativeFunctions.json_encode_json_encode' ), array_column( $messages, 'source' ) );
	}

	public function test_local_xml_rule_overrides_cannot_hide_real_checker_findings(): void {
		$code = 'WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The native read is inert input to the actual checker.
		file_put_contents(
			$this->root . '/resources/probe.php',
			"<?php
file_get_contents( 'fixture' );
"
		);
		foreach ( array( '<rule ref="WordPress.WP.AlternativeFunctions"><exclude name="' . $code . '"/></rule>', '<rule ref="' . $code . '"><severity>0</severity></rule>', '<rule ref="WordPress.WP.AlternativeFunctions"><exclude-pattern>probe.php</exclude-pattern></rule>' ) as $override ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Simulate a local profile waiver without modifying the real or locked shared rulesets.
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file>' . $override . '</ruleset>' );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertSame( 0, $status, $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS local rule override needs review:', $output );
		}
		// Current tooling's two reviewed PSR-4 filename exclusions remain accepted.
		copy( dirname( __DIR__ ) . '/phpcs-tooling.xml.dist', $this->root . '/phpcs-tooling.xml.dist' );
		foreach ( array( 'tests', 'fixtures' ) as $directory ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Supply current directory scopes in the isolated configuration fixture.
			mkdir( $this->root . '/' . $directory );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the resource profile after the negative XML probes.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_conditional_xml_elements_cannot_disable_checker_rules(): void {
		$code  = 'WordPress.WP.AlternativeFunctions.json_encode_json_encode';
		$rules = '<ruleset><file>resources</file><rule ref="RANWordPressLibrary"/></ruleset>';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Inspect inert native JSON input with the real locked checker.
		file_put_contents( $this->root . '/resources/probe.php', '<?php json_encode( array() );' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Establish the shared-profile diagnostic before mutating its conditional attributes.
		file_put_contents( $this->root . '/phpcs.xml.dist', $rules );
		list( $status, $output ) = $this->check_coverage( true );
		$this->assertNotSame( 0, $status, $output );
		$this->assertStringContainsString( $code, $output );
		foreach ( array( 'phpcbf-only="true"', 'phpcs-only="false"' ) as $condition ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Reproduce PHPCS skipping the otherwise required shared rule.
			file_put_contents( $this->root . '/phpcs.xml.dist', str_replace( 'ref="RANWordPressLibrary"', 'ref="RANWordPressLibrary" ' . $condition, $rules ) );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertStringNotContainsString( $code, $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status, $output );
			$this->assertStringContainsString( 'PHPCS conditional element needs review: phpcs.xml.dist', $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the normal resource rule before probing other profile elements.
		file_put_contents( $this->root . '/phpcs.xml.dist', $rules );
		foreach ( array( '<ruleset phpcbf-only="true"><file>bin/ran-admin-shell</file><file>tools</file></ruleset>', '<ruleset><file phpcs-only="false">bin/ran-admin-shell</file><file>tools</file></ruleset>' ) as $mutation ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Conditional selection must be rejected across both owned XML profiles and element types.
			file_put_contents( $this->root . '/phpcs-tooling.xml.dist', $mutation );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status, $output );
			$this->assertStringContainsString( 'PHPCS conditional element needs review: phpcs-tooling.xml.dist', $output );
		}
	}

	public function test_nonstandard_php_entrypoints_cannot_escape_discovery(): void {
		foreach ( array( 'resources/NewSource.PHP', 'tools/future-command', 'tests/future.inc' ) as $path ) {
			if ( ! is_dir( dirname( $this->root . '/' . $path ) ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create the isolated future test source directory.
				mkdir( dirname( $this->root . '/' . $path ) );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Inert PHP with a shebang tests header discovery outside lowercase .php.
			file_put_contents( $this->root . '/' . $path, "#!/usr/bin/env php\n<?php throw new RuntimeException('never execute');" );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHP entrypoint requires explicit checker support: ' . $path, $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only this inert isolated source fixture.
			unlink( $this->root . '/' . $path );
		}
	}

	public function test_effective_analysis_extensions_and_stubs_cannot_hide_source(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated PHPStan configuration for each omission control.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		foreach ( array(
			"    fileExtensions!: [inc]\n" => 'PHPStan does not directly cover: resources/admin-shell.php',
			"    stubFiles:\n        - resources/admin-shell.php\n" => 'PHPStan configured stub files need explicit coverage review.',
		) as $override => $expected ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Mutate the real container configuration without changing declared source roots.
			file_put_contents( $this->root . '/phpstan.neon.dist', $configuration . $override );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( $expected, $output );
		}
	}

	public function test_xml_arguments_cannot_disable_actual_diagnostics(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Supply inert native-operation input to the actual locked checker.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\nfile_get_contents( 'fixture' );\n" );
		foreach ( array( '<arg name="exclude" value="WordPress.WP.AlternativeFunctions"/>', '<arg name="sniffs" value="Generic.PHP.LowerCaseConstant"/>', '<arg name="ignore" value="*/probe.php"/>' ) as $argument ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Demonstrate argument-level checker suppression in the isolated resource ruleset.
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><file>resources</file><rule ref="WordPress.WP.AlternativeFunctions"/><rule ref="Generic.PHP.LowerCaseConstant"/>' . $argument . '</ruleset>' );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertSame( 0, $status, $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS local argument needs review:', $output );
		}
	}

	private function check_coverage( bool $use_checker = false ): array {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( $use_checker ? array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/phpcs', '--standard=' . $this->root . '/phpcs.xml.dist', '--report=json', '--no-colors', '-q', $this->root . '/resources/probe.php' ) : array( PHP_BINARY, $this->root . '/tools/check-coverage.php' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $pipes );
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
