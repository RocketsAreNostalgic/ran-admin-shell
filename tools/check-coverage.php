<?php
/** Reject maintained PHP that is outside the direct PHPStan and PHPCS scopes. */

$root = dirname( __DIR__ );

try {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
	$analysis = file_get_contents( $root . '/phpstan.neon.dist' );
	if ( false === $analysis || preg_match( '/^\s*(excludePaths|includes)\s*:/m', $analysis ) ) {
		throw new RuntimeException( 'PHPStan coverage configuration is missing or needs review.' );
	}
	require_once $root . '/vendor/autoload.php';
	$container = ( new \PHPStan\DependencyInjection\ContainerFactory( $root ) )->create(
		$root . '/.phpstan-cache/coverage',
		array( $root . '/phpstan.neon.dist' ),
		array()
	);
	$level     = $container->getParameter( 'level' );
	if ( 'max' !== $level && (int) $level < 5 ) {
		throw new RuntimeException( 'Maintained PHP requires PHPStan level 5 or higher.' );
	}
	if ( array() !== $container->getParameter( 'ignoreErrors' ) ) {
		throw new RuntimeException( 'PHPStan ignored errors need explicit review.' );
	}
	$bundled_runtime     = 'phar://' . realpath( $root . '/vendor/phpstan/phpstan/phpstan.phar' ) . '/stubs/runtime/';
	$expected_bootstraps = array(
		$bundled_runtime . 'ReflectionUnionType.php',
		$bundled_runtime . 'ReflectionAttribute.php',
		$bundled_runtime . 'Attribute85.php',
		$bundled_runtime . 'ReflectionIntersectionType.php',
	);
	$actual_bootstraps   = $container->getParameter( 'bootstrapFiles' );
	sort( $expected_bootstraps );
	sort( $actual_bootstraps );
	if ( $expected_bootstraps !== $actual_bootstraps ) {
		throw new RuntimeException( 'PHPStan executable bootstrap files need explicit review.' );
	}
	if ( 80000 !== $container->getParameter( 'phpVersion' ) ) {
		throw new RuntimeException( 'PHPStan compatibility target must remain PHP 8.0.' );
	}
	$analysis_files = $container->getService( 'fileFinderAnalyse' )->findFiles( $container->getParameter( 'paths' ) )->getFiles();
	$bundled_stubs  = 'phar://' . realpath( $root . '/vendor/phpstan/phpstan/phpstan.phar' ) . '/stubs/';
	foreach ( $container->getParameter( 'stubFiles' ) as $stub ) {
		if ( 0 !== strpos( $stub, $bundled_stubs ) ) {
			throw new RuntimeException( 'PHPStan configured stub files need explicit coverage review.' );
		}
	}

	$standards_paths      = array();
	$standards_exclusions = array();
	foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $ruleset ) {
		$standards_paths[ $ruleset ]      = array();
		$standards_exclusions[ $ruleset ] = array();
		$document                         = new DOMDocument();
		if ( ! $document->load( $root . '/' . $ruleset, LIBXML_NONET ) ) {
			throw new RuntimeException( 'PHPCS ruleset cannot be read: ' . $ruleset );
		}
		foreach ( $document->getElementsByTagName( '*' ) as $element ) {
			if ( $element->hasAttribute( 'phpcs-only' ) || $element->hasAttribute( 'phpcbf-only' ) ) {
				throw new RuntimeException( 'PHPCS conditional element needs review: ' . $ruleset );
			}
		}
		foreach ( $document->getElementsByTagName( 'arg' ) as $argument ) {
			$name  = $argument->getAttribute( 'name' );
			$value = $argument->getAttribute( 'value' );
			if ( ( 'basepath' === $name && '.' === $value ) || ( 'colors' === $name && '' === $value )
				|| ( '' === $name && 'sp' === $value )
				|| ( 'phpcs-tooling.xml.dist' === $ruleset && 'filter' === $name && 'tools/StandardsFilter.php' === $value ) ) {
				continue;
			}
			throw new RuntimeException( 'PHPCS local argument needs review: ' . $ruleset . ' ' . $name );
		}
		// Local rule overrides need review; locked shared-profile internals remain authoritative.
		foreach ( $document->getElementsByTagName( 'rule' ) as $rule ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM exposes childNodes for inspecting local rule overrides.
			foreach ( $rule->childNodes as $override ) {
				if ( ! $override instanceof DOMElement ) {
					continue;
				}
				if ( 'phpcs-tooling.xml.dist' === $ruleset && 'WordPress-Extra' === $rule->getAttribute( 'ref' )
					&& 'exclude' === $override->tagName // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM exposes the XML element tagName.
					&& in_array( $override->getAttribute( 'name' ), array( 'WordPress.Files.FileName.NotHyphenatedLowercase', 'WordPress.Files.FileName.InvalidClassFileName' ), true )
					&& 1 === $override->attributes->length && ! $override->hasChildNodes() ) {
					continue;
				}
				throw new RuntimeException( 'PHPCS local rule override needs review: ' . $ruleset . ' ' . $rule->getAttribute( 'ref' ) );
			}
		}
		$xpath          = new DOMXPath( $document );
		$required_rules = 'phpcs.xml.dist' === $ruleset
			? array( 'RANWordPressLibrary', 'RANOwnedMethods' )
			: array( 'RAN', 'WordPress-Extra', 'RANOwnedMethods', 'PHPCompatibility' );
		$active_rules   = array();
		foreach ( $xpath->query( '/ruleset/rule[@ref]' ) as $rule ) {
			if ( ! $rule instanceof DOMElement ) {
				throw new RuntimeException( 'PHPCS rule element cannot be read: ' . $ruleset );
			}
			$active_rules[] = $rule->getAttribute( 'ref' );
		}
		foreach ( $required_rules as $required_rule ) {
			if ( ! in_array( $required_rule, $active_rules, true ) ) {
				throw new RuntimeException( 'PHPCS mandatory rule missing: ' . $ruleset . ' ' . $required_rule );
			}
		}
		$compatibility = $xpath->query( '//config[@name="testVersion"]' );
		if ( 1 !== count( $compatibility ) || ! $compatibility->item( 0 ) instanceof DOMElement || '8.0-' !== $compatibility->item( 0 )->getAttribute( 'value' )
			|| 2 !== $compatibility->item( 0 )->attributes->length ) {
			throw new RuntimeException( 'PHPCS compatibility target must remain 8.0-: ' . $ruleset );
		}
		if ( 'phpcs.xml.dist' === $ruleset ) {
			$wp_floor = $xpath->query( '//config[@name="minimum_wp_version" or @name="minimum_supported_wp_version"]' );
			if ( 1 !== count( $wp_floor ) || ! $wp_floor->item( 0 ) instanceof DOMElement
				|| 'minimum_wp_version' !== $wp_floor->item( 0 )->getAttribute( 'name' )
				|| '6.5' !== $wp_floor->item( 0 )->getAttribute( 'value' ) || 2 !== $wp_floor->item( 0 )->attributes->length ) {
				throw new RuntimeException( 'PHPCS WordPress compatibility floor must remain 6.5.' );
			}
		} else {
			$cli_filter = $xpath->query( '/ruleset/arg[@name="filter"]' );
			if ( 1 !== count( $cli_filter ) || ! $cli_filter->item( 0 ) instanceof DOMElement
				|| 'tools/StandardsFilter.php' !== $cli_filter->item( 0 )->getAttribute( 'value' ) || 2 !== $cli_filter->item( 0 )->attributes->length ) {
				throw new RuntimeException( 'PHPCS requires its sole extensionless CLI filter.' );
			}
		}
		$root_exclusions = $xpath->query( '/ruleset/exclude-pattern' );
		if ( 1 !== count( $root_exclusions ) || 'vendor/*' !== (string) $root_exclusions->item( 0 )->nodeValue
			|| 0 !== $root_exclusions->item( 0 )->attributes->length ) {
			throw new RuntimeException( 'PHPCS root exclusion needs review: ' . $ruleset );
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
		if ( null === $document->documentElement ) {
			throw new RuntimeException( 'PHPCS ruleset has no root: ' . $ruleset );
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
		foreach ( $document->documentElement->childNodes as $entry ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
			if ( 'file' === $entry->nodeName ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
				$standards_paths[ $ruleset ][] = trim( $entry->textContent );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
			} elseif ( 'exclude-pattern' === $entry->nodeName && $entry instanceof DOMElement ) {
				$standards_exclusions[ $ruleset ][] = array(
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
					'pattern' => trim( $entry->textContent ),
					'type'    => $entry->getAttribute( 'type' ),
				);
			}
		}
	}
	if ( ! array_filter( $standards_paths ) ) {
		throw new RuntimeException( 'PHPCS has no source paths.' );
	}

	// A genuine leading XML declaration is data; any subsequent opening tag still needs review.
	$xml_declaration      = '~\A(?:\xEF\xBB\xBF)?<\?xml[ \t\r\n]+version[ \t\r\n]*=[ \t\r\n]*(?:"1\.[01]"|\'1\.[01]\')(?:[ \t\r\n]+encoding[ \t\r\n]*=[ \t\r\n]*(?:"[A-Za-z][A-Za-z0-9._-]*"|\'[A-Za-z][A-Za-z0-9._-]*\'))?(?:[ \t\r\n]+standalone[ \t\r\n]*=[ \t\r\n]*(?:"(?:yes|no)"|\'(?:yes|no)\'))?[ \t\r\n]*\?>~';
	$has_php_opening      = static function ( SplFileInfo $file, bool $linked = false ) use ( $xml_declaration ): bool {
		$stream = new SplFileObject( $file->getPathname(), 'rb' );
		$header = $stream->fread( 8192 );
		if ( false === $header ) {
			throw new RuntimeException( 'Cannot inspect maintained candidate.' );
		}
		$extension = strtolower( $file->getExtension() );
		// Validate small JSON as data; larger JSON stays fully scanned rather than allocating its whole body.
		if ( ! $linked && 'json' === $extension && $file->getSize() <= 1048576 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Validate at most one MiB of local JSON data; all larger bodies use bounded streaming discovery.
			$json = file_get_contents( $file->getPathname(), false, null, 0, 1048576 );
			if ( false !== $json && null !== json_decode( $json ) && JSON_ERROR_NONE === json_last_error() ) {
				return false;
			}
		}
		$inert = ! $linked && ( 'md' === $extension
			|| ( 'sh' === $extension && 1 === preg_match( '~\A#!(?:/usr/bin/env[ \t]+bash|/bin/bash)(?:[ \t][^\r\n]*)?\r?\n~', $header ) ) );
		// Declarations exceeding this bounded initial header require review; never discard an incomplete declaration.
		$chunk = preg_replace( $xml_declaration, '', $header ) ?? $header;
		if ( $inert && preg_match( '/\A(?:\xEF\xBB\xBF)?#!/', $header ) && ! str_contains( $header, "\n" ) ) {
			// An incomplete shebang cannot authorize skipping the rest of a documentation file.
			return true;
		}
		if ( $inert ) {
			$chunk = preg_replace( '/\A(?:\xEF\xBB\xBF)?(?:#![^\r\n]*\R)?/', '', $chunk ) ?? $chunk;
		}
		while ( true ) {
			if ( $inert ) {
				$chunk = ltrim( $chunk, " \t\n\r\x0B\x0C" );
				if ( '' !== $chunk && '<' !== $chunk ) {
					return 0 === strpos( $chunk, '<?' );
				}
			} elseif ( false !== strpos( $chunk, '<?' ) ) {
				return true;
			}
			if ( $stream->eof() ) {
				return false;
			}
			$overlap = substr( $chunk, -2 );
			$next    = $stream->fread( 8192 );
			if ( false === $next ) {
				throw new RuntimeException( 'Cannot inspect maintained candidate.' );
			}
			$chunk = $overlap . $next;
		}
	};
	$maintained           = array();
	$excluded_directories = array( '.git', '.phpstan-cache', '.phpunit.cache', 'vendor', 'node_modules' );
	$directory            = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
	$filter               = new RecursiveCallbackFilterIterator(
		$directory,
		static function ( $file ) use ( $excluded_directories, $root, $has_php_opening ) {
			$relative   = substr( $file->getPathname(), strlen( $root ) + 1 );
			$linked_php = false;
			if ( $file->isLink() && $file->isFile() && ! in_array( $relative, $excluded_directories, true ) ) {
				$linked_php = $has_php_opening( $file, true );
			}
			if ( $file->isLink() && ! in_array( $relative, $excluded_directories, true )
				&& ( $linked_php || $file->isDir() || in_array( strtolower( $file->getExtension() ), array( '', 'php', 'phtml', 'inc', 'html', 'htm' ), true ) ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
				throw new RuntimeException( 'Maintained PHP is a symbolic link: ' . $relative );
			}
			return ! $file->isLink() && ( ! $file->isDir() || ! in_array( $relative, $excluded_directories, true ) );
		}
	);
	foreach ( new RecursiveIteratorIterator( $filter ) as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$relative  = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
		$extension = strtolower( $file->getExtension() );
		if ( in_array( $extension, array( 'php', 'phtml' ), true ) || $has_php_opening( $file ) ) {
			if ( 'php' !== $file->getExtension() && 'bin/ran-admin-shell' !== $relative ) {
				throw new RuntimeException( 'PHP entrypoint requires explicit checker support: ' . $relative );
			}
			$maintained[] = $relative;
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
	$manifest = json_decode( file_get_contents( $root . '/composer.json' ), true, 512, JSON_THROW_ON_ERROR );
	foreach ( $manifest['bin'] ?? array() as $command ) {
		// StandardsFilter.php selects only this extensionless entrypoint.
		if ( 'bin/ran-admin-shell' !== $command ) {
			throw new RuntimeException( 'PHPCS extensionless CLI filter needs review: ' . $command );
		}
		$maintained[] = $command;
	}
	$maintained = array_unique( $maintained );
	sort( $maintained );
	if ( ! $maintained ) {
		throw new RuntimeException( 'No maintained PHP source was discovered.' );
	}

	$analysed_count = 0;
	foreach ( $maintained as $maintained_path ) {
		if ( ! is_file( $root . '/' . $maintained_path ) ) {
			throw new RuntimeException( 'Maintained PHP source is missing: ' . $maintained_path );
		}
		if ( ! in_array( $root . '/' . $maintained_path, $analysis_files, true ) ) {
			throw new RuntimeException( 'PHPStan does not directly cover: ' . $maintained_path );
		}
		++$analysed_count;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect maintained source comments without executing fixture or tool code.
		$source = file_get_contents( $root . '/' . $maintained_path );
		if ( false === $source ) {
			throw new RuntimeException( 'Cannot inspect maintained PHP: ' . $maintained_path );
		}
		foreach ( token_get_all( $source ) as $token ) {
			if ( ! is_array( $token ) || ! in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			if ( preg_match( '~(?:@codingStandards(?:Ignore\w*|ChangeSetting)|phpcs:set\b|phpcs:ignoreFile|phpcs:disable\b|phpcs:ignore[ \t]*(?:--[^\r\n]*)?(?:\*/)?[ \t]*$)~mi', $token[1] ) ) {
				throw new RuntimeException( 'Blanket, persistent or legacy standards suppression: ' . $maintained_path );
			}
			preg_match_all( '~phpcs:ignore\b([^\r\n]*)~i', $token[1], $ignores );
			foreach ( $ignores[1] as $ignore ) {
				$ignore     = preg_replace( '~\s*\*/\s*$~', '', $ignore );
				$parts      = explode( '--', $ignore, 2 );
				$diagnostic = '[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*){3}';
				if ( 2 !== count( $parts ) || ! preg_match( '~^\s*' . $diagnostic . '(?:\s*,\s*' . $diagnostic . ')*\s*$~', $parts[0] )
					|| ! preg_match( '/[A-Za-z0-9]/', $parts[1] ) ) {
					throw new RuntimeException( 'Standards ignore needs exact diagnostic codes and a concrete reason: ' . $maintained_path );
				}
			}
		}
		$required_ruleset = 0 === strpos( $maintained_path, 'resources/' ) ? 'phpcs.xml.dist' : 'phpcs-tooling.xml.dist';
		if ( ! covered_by( $maintained_path, $standards_paths[ $required_ruleset ], $root ) ) {
			throw new RuntimeException( 'PHPCS does not cover: ' . $maintained_path . ' in ' . $required_ruleset );
		}
		if ( excluded_by_phpcs( $maintained_path, $standards_exclusions[ $required_ruleset ], $root ) ) {
			throw new RuntimeException( 'PHPCS excludes maintained PHP: ' . $maintained_path );
		}
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
	fwrite( STDOUT, 'Maintained PHP standards coverage: ' . count( $maintained ) . ' paths; direct analysis: ' . $analysed_count . " paths.\n" );
} catch ( Throwable $error ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
	fwrite( STDERR, $error->getMessage() . PHP_EOL );
	exit( 1 );
}

/** A directory covers descendants, while a file covers only itself. */
function covered_by( $path, array $roots, $repository ) {
	$canonical_root = realpath( $repository );
	if ( false === $canonical_root ) {
		throw new RuntimeException( 'Quality repository root is missing.' );
	}
	$normalized_root = str_replace( '\\', '/', $canonical_root );
	foreach ( $roots as $scope ) {
		$location            = realpath( $repository . '/' . $scope );
		$normalized_location = false === $location ? false : str_replace( '\\', '/', $location );
		$position            = false === $normalized_location ? false : ( '\\' === DIRECTORY_SEPARATOR ? stripos( $normalized_location, $normalized_root . '/' ) : strpos( $normalized_location, $normalized_root . '/' ) );
		if ( 0 !== $position ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
			throw new RuntimeException( 'Quality source scope is missing or outside the repository: ' . $scope );
		}
		$relative = substr( $normalized_location, strlen( $normalized_root ) + 1 );
		if ( ( is_dir( $location ) && 0 === strpos( $path, $relative . '/' ) ) || $path === $relative ) {
			return true;
		}
	}
	return false;
}

/** Mirror PHPCS 3.13.6 global file exclusions, including relative patterns. */
function excluded_by_phpcs( $path, array $patterns, $repository ) {
	foreach ( $patterns as $exclusion ) {
		$replacements = array(
			'\\,' => ',',
			'*'   => '.*',
		);
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$replacements['/'] = '\\\\';
		}
		$pattern  = strtr( $exclusion['pattern'], $replacements );
		$absolute = realpath( $repository . '/' . $path );
		if ( false === $absolute ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
			throw new RuntimeException( 'Maintained PHP source is missing: ' . $path );
		}
		$target = 'relative' === $exclusion['type'] ? str_replace( '/', DIRECTORY_SEPARATOR, $path ) : $absolute;
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid configuration regex is explicitly detected by the false result and rejected.
		$match = @preg_match( '`' . $pattern . '`i', $target );
		if ( false === $match ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
			throw new RuntimeException( 'PHPCS exclusion pattern needs review: ' . $exclusion['pattern'] );
		}
		if ( 1 === $match ) {
			return true;
		}
	}
	return false;
}
