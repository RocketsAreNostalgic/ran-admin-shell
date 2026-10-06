<?php
/** Reject maintained PHP that is outside the direct PHPStan and PHPCS scopes. */

$root = dirname( __DIR__ );

try {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
	$analysis = file_get_contents( $root . '/phpstan.neon.dist' );
	if ( false === $analysis || preg_match( '/^\s*(excludePaths|includes)\s*:/m', $analysis ) ) {
		throw new RuntimeException( 'PHPStan coverage configuration is missing or needs review.' );
	}
	if ( ! preg_match( '/^([ \t]*)paths:[ \t]*$/m', $analysis, $match, PREG_OFFSET_CAPTURE ) ) {
		throw new RuntimeException( 'PHPStan direct paths are missing or need review.' );
	}
	$indent         = strlen( $match[1][0] );
	$remainder      = substr( $analysis, $match[0][1] + strlen( $match[0][0] ) );
	$analysis_paths = array();
	foreach ( preg_split( '/\R/', $remainder ) as $line ) {
		if ( '' === trim( $line ) || '#' === substr( ltrim( $line ), 0, 1 ) ) {
			continue;
		}
		$depth = strlen( $line ) - strlen( ltrim( $line, " \t" ) );
		if ( $depth <= $indent ) {
			break;
		}
		if ( ! preg_match( '/^[ \t]+-[ \t]+([A-Za-z0-9_.\/-]+)[ \t]*$/', $line, $entry ) ) {
			throw new RuntimeException( 'PHPStan direct paths need review: ' . trim( $line ) );
		}
		$analysis_paths[] = $entry[1];
	}
	if ( ! $analysis_paths ) {
		throw new RuntimeException( 'PHPStan has no direct source paths.' );
	}

	$standards_paths      = array();
	$standards_exclusions = array();
	foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $ruleset ) {
		$document = new DOMDocument();
		if ( ! $document->load( $root . '/' . $ruleset, LIBXML_NONET ) ) {
			throw new RuntimeException( 'PHPCS ruleset cannot be read: ' . $ruleset );
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
				$standards_paths[] = trim( $entry->textContent );
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
			} elseif ( 'exclude-pattern' === $entry->nodeName && $entry instanceof DOMElement ) {
				$standards_exclusions[] = array(
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Preserve the native DOM/ZipArchive property contract; this is not an owned property.
					'pattern' => trim( $entry->textContent ),
					'type'    => $entry->getAttribute( 'type' ),
				);
			}
		}
	}
	if ( ! $standards_paths ) {
		throw new RuntimeException( 'PHPCS has no source paths.' );
	}

	$maintained           = array();
	$excluded_directories = array( '.git', '.phpstan-cache', '.phpunit.cache', 'vendor', 'node_modules' );
	$directory            = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
	$filter               = new RecursiveCallbackFilterIterator(
		$directory,
		static function ( $file ) use ( $excluded_directories, $root ) {
			$relative = substr( $file->getPathname(), strlen( $root ) + 1 );
			if ( $file->isLink() && 'php' === $file->getExtension() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
				throw new RuntimeException( 'Maintained PHP is a symbolic link: ' . $relative );
			}
			return ! $file->isLink() && ( ! $file->isDir() || ! in_array( $relative, $excluded_directories, true ) );
		}
	);
	foreach ( new RecursiveIteratorIterator( $filter ) as $file ) {
		if ( $file->isFile() && 'php' === $file->getExtension() ) {
			$maintained[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
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
		$development = 0 === strpos( $maintained_path, 'tests/' ) || 0 === strpos( $maintained_path, 'fixtures/' );
		if ( ! $development && ! covered_by( $maintained_path, $analysis_paths, $root ) ) {
			throw new RuntimeException( 'PHPStan does not directly cover: ' . $maintained_path );
		}
		$analysed_count += $development ? 0 : 1;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect maintained source comments without executing fixture or tool code.
		$source = file_get_contents( $root . '/' . $maintained_path );
		if ( false === $source ) {
			throw new RuntimeException( 'Cannot inspect maintained PHP: ' . $maintained_path );
		}
		foreach ( token_get_all( $source ) as $token ) {
			if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) && preg_match( '~(?:@codingStandardsIgnore\w*|phpcs:ignoreFile|phpcs:(?:ignore|disable)[ \t]*(?:--[^\r\n]*)?(?:\*/)?[ \t]*$)~m', $token[1] ) ) {
				throw new RuntimeException( 'Blanket or legacy standards suppression: ' . $maintained_path );
			}
		}
		if ( ! covered_by( $maintained_path, $standards_paths, $root ) ) {
			throw new RuntimeException( 'PHPCS does not cover: ' . $maintained_path );
		}
		if ( excluded_by_phpcs( $maintained_path, $standards_exclusions, $root ) ) {
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
