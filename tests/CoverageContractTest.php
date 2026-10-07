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
		file_put_contents( $this->root . '/phpstan.neon.dist', "parameters:\n    level: 5\n    phpVersion: 80000\n    paths:\n        - bin/ran-admin-shell\n        - resources\n        - tools\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern></ruleset>' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs-tooling.xml.dist', '<ruleset><arg name="filter" value="tools/StandardsFilter.php"/><config name="testVersion" value="8.0-"/><file>bin/ran-admin-shell</file><file>tools</file><rule ref="RAN"/><rule ref="WordPress-Extra"/><rule ref="RANOwnedMethods"/><rule ref="PHPCompatibility"/><exclude-pattern>vendor/*</exclude-pattern></ruleset>' );
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
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><exclude-pattern>admin-shell\\.php$</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS root exclusion needs review: phpcs.xml.dist', $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><exclude-pattern type="relative">^resources/.*</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status );
		$this->assertStringContainsString( 'PHPCS root exclusion needs review: phpcs.xml.dist', $output );
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
			$this->assertStringContainsString( 'PHPCS root exclusion needs review: phpcs-tooling.xml.dist', $output );
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
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><rule ref="WordPress.WP.AlternativeFunctions"/></ruleset>' );
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
		$rules = '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><rule ref="WordPress.NamingConventions.PrefixAllGlobals"><properties><property name="prefixes" type="array"><element value="approved"/></property></properties></rule></ruleset>';
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
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern></ruleset>' );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'Blanket, persistent or legacy standards suppression: resources/probe.php', $output );
		}
	}

	public function test_exact_ignore_with_reason_leaves_next_line_and_other_diagnostics_checked(): void {
		$code = 'WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Configure the real checker to observe exact diagnostic boundaries.
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><rule ref="WordPress.WP.AlternativeFunctions"/></ruleset>' );
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
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern>' . $override . '</ruleset>' );
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
		file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern></ruleset>' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_mandatory_profiles_cannot_be_removed_or_replaced(): void {
		$profiles = array(
			'phpcs.xml.dist'         => array( 'RANWordPressLibrary', 'RANOwnedMethods' ),
			'phpcs-tooling.xml.dist' => array( 'RAN', 'WordPress-Extra', 'RANOwnedMethods', 'PHPCompatibility' ),
		);
		foreach ( $profiles as $profile => $rules ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated healthy profile for each missing-rule mutation.
			$original = file_get_contents( $this->root . '/' . $profile );
			foreach ( $rules as $rule ) {
				foreach ( array( '', '<rule ref="Generic.PHP.Syntax"/>' ) as $replacement ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Remove or replace exactly one required ancestor in the disposable profile.
					file_put_contents( $this->root . '/' . $profile, str_replace( '<rule ref="' . $rule . '"/>', $replacement, $original ) );
					list( $status, $output ) = $this->check_coverage();
					$this->assertNotSame( 0, $status, $output );
					$this->assertStringContainsString( 'PHPCS mandatory rule missing: ' . $profile . ' ' . $rule, $output );
				}
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore only this isolated profile before inspecting the other one.
			file_put_contents( $this->root . '/' . $profile, $original );
		}
	}

	public function test_missing_resource_baseline_hides_real_security_diagnostics(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Mutate only the isolated resource profile while retaining the owned-method rule.
		$original = file_get_contents( $this->root . '/phpcs.xml.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Supply inert unescaped request output to the locked checker without executing it.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\n// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Controlled preescaped fixture.\necho \$preescaped;\necho \$_GET['x'];\n" );
		list( $status, $output ) = $this->check_coverage( true );
		$this->assertNotSame( 0, $status, $output );
		$baseline = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
		$this->assertNotEmpty( $baseline['files'] );
		$messages = array_values( $baseline['files'] )[0]['messages'];
		$this->assertSame(
			1,
			count(
				array_filter(
					$messages,
					static function ( array $message ): bool {
						return 'WordPress.Security.EscapeOutput.OutputNotEscaped' === $message['source'];
					}
				)
			),
			'The precise fixture allowance must leave the next output checked.'
		);
		foreach ( array( 'WordPress.Security.NonceVerification.Recommended', 'WordPress.Security.EscapeOutput.OutputNotEscaped' ) as $code ) {
			$this->assertStringContainsString( $code, $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Reproduce loss of inherited security rules while preserving a valid active checker.
		file_put_contents( $this->root . '/phpcs.xml.dist', str_replace( '<rule ref="RANWordPressLibrary"/>', '<rule ref="Generic.PHP.Syntax"/>', $original ) );
		list( $status, $output ) = $this->check_coverage( true );
		$this->assertSame( 0, $status, $output );
		$report = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
		$this->assertNotEmpty( $report['files'] );
		$this->assertSame( 0, $report['totals']['errors'] );
		$this->assertSame( 0, $report['totals']['warnings'] );
		list( $status, $output ) = $this->check_coverage();
		$this->assertNotSame( 0, $status, $output );
		$this->assertStringContainsString( 'PHPCS mandatory rule missing: phpcs.xml.dist RANWordPressLibrary', $output );
	}

	public function test_future_root_exclusions_require_review_before_a_file_exists(): void {
		foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $profile ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the actual isolated dependency exclusion before scope mutations.
			$original = file_get_contents( $this->root . '/' . $profile );
			foreach ( array(
				str_replace( '</ruleset>', '<exclude-pattern>*/not-yet-created/*</exclude-pattern></ruleset>', $original ),
				str_replace( 'vendor/*', '*/vendor/*', $original ),
				str_replace( '<exclude-pattern>', '<exclude-pattern type="relative">', $original ),
			) as $mutation ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Prove preventive rejection without creating a matching maintained file.
				file_put_contents( $this->root . '/' . $profile, $mutation );
				list( $status, $output ) = $this->check_coverage();
				$this->assertNotSame( 0, $status, $output );
				$this->assertStringContainsString( 'PHPCS root exclusion needs review: ' . $profile, $output );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the unchanged fixture profile after its mutations.
			file_put_contents( $this->root . '/' . $profile, $original );
		}
	}

	public function test_conditional_xml_elements_cannot_disable_checker_rules(): void {
		$code  = 'WordPress.WP.AlternativeFunctions.json_encode_json_encode';
		$rules = '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern></ruleset>';
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
			$this->assertSame( 0, $status, $output );
			$this->assertIsArray( json_decode( $output, true, 512, JSON_THROW_ON_ERROR ) );
			$this->assertStringNotContainsString( $code, $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status, $output );
			$this->assertStringContainsString( 'PHPCS conditional element needs review: phpcs.xml.dist', $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the normal resource rule before probing other profile elements.
		file_put_contents( $this->root . '/phpcs.xml.dist', $rules );
		foreach ( array( '<ruleset phpcbf-only="true"><file>bin/ran-admin-shell</file><file>tools</file></ruleset>', '<ruleset><config name="testVersion" value="8.0-"/><file phpcs-only="false">bin/ran-admin-shell</file><file>tools</file></ruleset>' ) as $mutation ) {
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

	public function test_effective_ignored_errors_cannot_hide_actual_diagnostics(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated native analyzer configuration for the suppression controls.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Create inert source whose invalid return is analyzed, never executed.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\nfunction ran_admin_shell_ignored_error_probe(): int { return 'invalid'; }\n" );
		foreach ( array( '', "    ignoreErrors:\n        - '#.*#'\n", "    ignoreErrors:\n        - identifier: return.type\n" ) as $override ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write only the isolated effective configuration used to demonstrate analyzer suppression.
			file_put_contents( $this->root . '/phpstan.neon.dist', $configuration . $override );
			$lines = array();
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Run the locked analyzer against the inert probe and retain its exit status and JSON evidence.
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/vendor/bin/phpstan' ) . ' analyse --no-progress --error-format=json --configuration=' . escapeshellarg( $this->root . '/phpstan.neon.dist' ) . ' ' . escapeshellarg( $this->root . '/resources/probe.php' ), $lines, $status );
			$report = json_decode( implode( "\n", $lines ), true, 512, JSON_THROW_ON_ERROR );
			if ( '' === $override ) {
				$this->assertSame( 1, $status );
				$this->assertSame( 'return.type', $report['files'][ $this->root . '/resources/probe.php' ]['messages'][0]['identifier'] );
				continue;
			}
			$this->assertSame( 0, $status );
			$this->assertSame( 0, $report['totals']['file_errors'] );
			$this->assertSame( array(), $report['errors'] );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPStan ignored errors need explicit review.', $output );
		}
	}

	public function test_executable_bootstrap_cannot_exit_before_analysis(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated analyzer configuration while reproducing executable bootstrap termination.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write inert invalid source for actual analyzer diagnostic evidence.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\nfunction ran_admin_bootstrap_probe(): int { return 'invalid'; }\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- The isolated bootstrap exits only its dedicated analyzer subprocess and never the test runner.
		file_put_contents( $this->root . '/tools/bootstrap.php', '<?php exit( 0 );' );
		foreach ( array( false, true ) as $with_bootstrap ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Change only the effective bootstrap setting in the isolated analyzer configuration.
			file_put_contents( $this->root . '/phpstan.neon.dist', $configuration . ( $with_bootstrap ? "    bootstrapFiles:\n        - tools/bootstrap.php\n" : '' ) );
			$lines = array();
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Execute the locked analyzer in process so the bootstrap's successful early termination is directly observable.
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/vendor/bin/phpstan' ) . ' analyse --debug --no-progress --error-format=json --configuration=' . escapeshellarg( $this->root . '/phpstan.neon.dist' ) . ' ' . escapeshellarg( $this->root . '/resources/probe.php' ), $lines, $status );
			$this->assertSame( $with_bootstrap ? 0 : 1, $status );
			if ( $with_bootstrap ) {
				$this->assertSame( array(), $lines );
			} else {
				$this->assertStringContainsString( 'return.type', implode( "\n", $lines ) );
			}
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( $with_bootstrap ? 1 : 0, $status, $output );
			if ( $with_bootstrap ) {
				$this->assertStringContainsString( 'PHPStan executable bootstrap files need explicit review.', $output );
			}
		}
	}

	public function test_bundled_bootstrap_identities_and_multiplicity_are_fixed(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated configuration while checking the exact locked runtime bootstrap inventory.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		$runtime       = 'phar://' . realpath( dirname( __DIR__ ) . '/vendor/phpstan/phpstan/phpstan.phar' ) . '/stubs/runtime/';
		foreach ( array(
			"    bootstrapFiles:\n        - '" . $runtime . "ReflectionUnionType.php'\n",
			"    bootstrapFiles:\n        - '" . $runtime . "Unreviewed.php'\n",
			"    bootstrapFiles!: []\n",
		) as $override ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Duplicate, add or remove only isolated bootstrap entries; none are executed by coverage.
			file_put_contents( $this->root . '/phpstan.neon.dist', $configuration . $override );
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 1, $status );
			$this->assertStringContainsString( 'PHPStan executable bootstrap files need explicit review.', $output );
		}
	}

	public function test_xml_arguments_cannot_disable_actual_diagnostics(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Supply inert native-operation input to the actual locked checker.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\nfile_get_contents( 'fixture' );\n" );
		foreach ( array( '<arg name="exclude" value="WordPress.WP.AlternativeFunctions"/>', '<arg name="sniffs" value="Generic.PHP.LowerCaseConstant"/>', '<arg name="ignore" value="*/probe.php"/>' ) as $argument ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Demonstrate argument-level checker suppression in the isolated resource ruleset.
			file_put_contents( $this->root . '/phpcs.xml.dist', '<ruleset><config name="minimum_wp_version" value="6.5"/><config name="testVersion" value="8.0-"/><file>resources</file><rule ref="RANWordPressLibrary"/><rule ref="RANOwnedMethods"/><exclude-pattern>vendor/*</exclude-pattern><rule ref="WordPress.WP.AlternativeFunctions"/><rule ref="Generic.PHP.LowerCaseConstant"/>' . $argument . '</ruleset>' );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertSame( 0, $status, $output );
			list( $status, $output ) = $this->check_coverage();
			$this->assertNotSame( 0, $status );
			$this->assertStringContainsString( 'PHPCS local argument needs review:', $output );
		}
	}

	public function test_compatibility_targets_cannot_hide_newer_native_apis(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated compatibility configuration for controlled target mutations.
		$configuration = file_get_contents( $this->root . '/phpstan.neon.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write inert PHP 8.1 API usage for the locked analyzers, never execute it.
		file_put_contents( $this->root . '/tools/probe.php', "<?php\nfunction ran_compatibility_probe( array \$values ): bool { return array_is_list( \$values ); }\n" );
		foreach ( array( 80000, 80100 ) as $version ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Change only the isolated analyzer compatibility target.
			file_put_contents( $this->root . '/phpstan.neon.dist', str_replace( '80000', (string) $version, $configuration ) );
			$lines = array();
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Observe the locked analyzer JSON and exit status for the same inert API at both compatibility targets.
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/vendor/bin/phpstan' ) . ' analyse --no-progress --error-format=json --configuration=' . escapeshellarg( $this->root . '/phpstan.neon.dist' ) . ' ' . escapeshellarg( $this->root . '/tools/probe.php' ), $lines, $status );
			$report = json_decode( implode( "\n", $lines ), true, 512, JSON_THROW_ON_ERROR );
			$this->assertSame( 80000 === $version ? 1 : 0, $status, implode( "\n", $lines ) );
			$this->assertSame( 80000 === $version ? 1 : 0, $report['totals']['file_errors'] );
			if ( 80000 === $version ) {
				$this->assertSame( 'function.notFound', $report['files'][ $this->root . '/tools/probe.php' ]['messages'][0]['identifier'] );
			}
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 80000 === $version ? 0 : 1, $status, $output );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the accepted PHPStan target before independently testing each PHPCS profile.
		file_put_contents( $this->root . '/phpstan.neon.dist', $configuration );
		foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $ruleset ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated profile for independent compatibility mutations.
			$original = file_get_contents( $this->root . '/' . $ruleset );
			foreach ( array( '', '<config name="testVersion" value="8.1-"/>', '<config name="testVersion" value="8.0-"/><config name="testVersion" value="8.1-"/>' ) as $replacement ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Remove, raise or duplicate only the isolated PHPCS compatibility setting.
				file_put_contents( $this->root . '/' . $ruleset, str_replace( '<config name="testVersion" value="8.0-"/>', $replacement, $original ) );
				list( $status, $output ) = $this->check_coverage();
				$this->assertSame( 1, $status );
				$this->assertStringContainsString( 'PHPCS compatibility target must remain 8.0-: ' . $ruleset, $output );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the isolated profile before checking the other profile.
			file_put_contents( $this->root . '/' . $ruleset, $original );
		}
		foreach ( array( '8.0-', '8.1-' ) as $version ) {
			$lines = array();
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Observe the real standalone compatibility diagnostic disappear only when its target is improperly raised.
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/vendor/bin/phpcs' ) . ' --standard=PHPCompatibility --runtime-set testVersion ' . escapeshellarg( $version ) . ' --report=json -q ' . escapeshellarg( $this->root . '/tools/probe.php' ), $lines, $status );
			$report = json_decode( implode( "\n", $lines ), true, 512, JSON_THROW_ON_ERROR );
			$this->assertSame( '8.0-' === $version ? 1 : 0, $report['totals']['errors'] );
			$this->assertSame( '8.0-' === $version ? 1 : 0, $status );
		}
	}

	public function test_html_preambles_do_not_hide_unsupported_php_templates(): void {
		foreach ( array( 'resources/template.phtml', 'tools/template', 'tools/template.html', 'tools/template.inc', 'tools/template.tpl', 'tools/undeclared.sh' ) as $path ) {
			foreach ( array( '<main>', str_repeat( '<main></main>', 100 ), "\xEF\xBB\xBF" ) as $preamble ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Create inert unsupported templates, including tags beyond the old bounded header.
				file_put_contents( $this->root . '/' . $path, $preamble . '<?php throw new RuntimeException("never execute");' );
				list( $status, $output ) = $this->check_coverage();
				$this->assertSame( 1, $status );
				$this->assertStringContainsString( 'PHP entrypoint requires explicit checker support: ' . $path, $output );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only the unsupported temporary template after its independent controls.
			unlink( $this->root . '/' . $path );
		}
		foreach ( array( 'example.md', 'example.json' ) as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Quoted PHP examples in documentation and data are not executable maintained templates.
			file_put_contents( $this->root . '/' . $path, '"Example: <?php echo documentation;"' );
		}
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	public function test_short_tags_and_xml_boundaries_are_independent_of_runtime_ini(): void {
		$declaration = '<?xml version="1.0"?>';
		foreach ( array( 0, 1 ) as $short_open_tag ) {
			foreach ( array(
				'<? echo 1;',
				'<main><?xmlfoo echo 1;',
				"\xEF\xBB\xBF<? echo 1;",
				str_repeat( '<main></main>', 100 ) . '<? echo 1;',
				'<?xmlfoo echo 1;',
				$declaration . '<root><? echo 1;</root>',
				'<main><?php echo 1;',
			) as $source ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Supply inert short-tag and XML-lookalike templates without executing their contents.
				file_put_contents( $this->root . '/tools/template.custom', $source );
				list( $status, $output ) = $this->check_coverage( false, $short_open_tag );
				$this->assertSame( 1, $status, $output );
				$this->assertStringContainsString( 'PHP entrypoint requires explicit checker support: tools/template.custom', $output );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- A genuine leading XML declaration remains inert data under either runtime setting.
			file_put_contents( $this->root . '/tools/template.custom', $declaration . '<root/>' );
			list( $status, $output ) = $this->check_coverage( false, $short_open_tag );
			$this->assertSame( 0, $status, $output );
		}
	}

	public function test_required_wordpress_floor_and_cli_filter_cannot_disappear(): void {
		foreach ( array(
			'phpcs.xml.dist'         => array( '<config name="minimum_wp_version" value="6.5"/>', '<config name="minimum_wp_version" value="7.0"/>', 'PHPCS WordPress compatibility floor must remain 6.5.' ),
			'phpcs-tooling.xml.dist' => array( '<arg name="filter" value="tools/StandardsFilter.php"/>', '', 'PHPCS requires its sole extensionless CLI filter.' ),
		) as $ruleset => $control ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated profile while testing required configuration multiplicity and values.
			$original = file_get_contents( $this->root . '/' . $ruleset );
			foreach ( array( '', $control[1], $control[0] . $control[0] ) as $replacement ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Mutate only the isolated required configuration element.
				file_put_contents( $this->root . '/' . $ruleset, str_replace( $control[0], $replacement, $original ) );
				list( $status, $output ) = $this->check_coverage();
				$this->assertSame( 1, $status );
				$this->assertStringContainsString( $control[2], $output );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Restore the accepted profile before testing the other profile.
			file_put_contents( $this->root . '/' . $ruleset, $original );
		}
	}

	public function test_active_wordpress_floor_controls_real_deprecation_severity(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Preserve the isolated resource profile for the active-key versus obsolete-key diagnostic control.
		$original = file_get_contents( $this->root . '/phpcs.xml.dist' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Probe a known WordPress 6.6 deprecation without executing WordPress.
		file_put_contents( $this->root . '/resources/probe.php', "<?php\nwp_render_elements_support();\n" );
		foreach ( array( 'minimum_wp_version', 'minimum_supported_wp_version' ) as $key ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Demonstrate that the obsolete key falls back to the locked checker default rather than enforcing 6.5.
			file_put_contents( $this->root . '/phpcs.xml.dist', str_replace( 'minimum_wp_version', $key, $original ) );
			list( $status, $output ) = $this->check_coverage( true );
			$this->assertNotSame( 0, $status );
			$report = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
			$types  = array();
			foreach ( $report['files'] as $file ) {
				foreach ( $file['messages'] as $message ) {
					if ( 'WordPress.WP.DeprecatedFunctions.wp_render_elements_supportFound' === $message['source'] ) {
						$types[] = $message['type'];
					}
				}
			}
			$this->assertSame( array( 'minimum_wp_version' === $key ? 'WARNING' : 'ERROR' ), $types );
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 'minimum_wp_version' === $key ? 0 : 1, $status, $output );
		}
	}

	public function test_linked_templates_cannot_be_pruned_before_discovery(): void {
		foreach ( array( 'tools/linked.phtml', 'tools/linked', 'tools/linked.inc', 'tools/linked.html', 'tools/linked.htm', 'tools/linked.PHP', 'tools/linked.txt', 'tools/linked-directory' ) as $path ) {
			symlink( 'tools/linked-directory' === $path ? $this->root . '/resources' : $this->root . '/resources/admin-shell.php', $this->root . '/' . $path );
			list( $status, $output ) = $this->check_coverage();
			$this->assertSame( 1, $status );
			$this->assertStringContainsString( 'Maintained PHP is a symbolic link: ' . $path, $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only the isolated link, never its target.
			unlink( $this->root . '/' . $path );
		}
		$this->assertFileExists( $this->root . '/resources/admin-shell.php' );
		symlink( $this->root . '/composer.json', $this->root . '/documentation.md' );
		list( $status, $output ) = $this->check_coverage();
		$this->assertSame( 0, $status, $output );
	}

	private function check_coverage( bool $use_checker = false, int $short_open_tag = 0 ): array {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the existing isolated CLI/checker command with its argument vector and observed exit status.
		$process = proc_open( $use_checker ? array( PHP_BINARY, dirname( __DIR__ ) . '/vendor/bin/phpcs', '--standard=' . $this->root . '/phpcs.xml.dist', '--report=json', '--no-colors', '-q', $this->root . '/resources/probe.php' ) : array( PHP_BINARY, '-d', 'short_open_tag=' . $short_open_tag, $this->root . '/tools/check-coverage.php' ), array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $pipes );
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
