<?php
/** Reject maintained PHP that is outside the direct PHPStan and PHPCS scopes. */

$root = dirname( __DIR__ );

try {
	$analysis = file_get_contents( $root . '/phpstan.neon.dist' );
	if ( false === $analysis || preg_match( '/^\s*(excludePaths|includes)\s*:/m', $analysis ) ) {
		throw new RuntimeException( 'PHPStan coverage configuration is missing or needs review.' );
	}
	if ( ! preg_match( '/^([ \t]*)paths:[ \t]*$/m', $analysis, $match, PREG_OFFSET_CAPTURE ) ) {
		throw new RuntimeException( 'PHPStan direct paths are missing or need review.' );
	}
	$indent = strlen( $match[1][0] );
	$remainder = substr( $analysis, $match[0][1] + strlen( $match[0][0] ) );
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

	$standards_paths = array();
	$standards_exclusions = array();
	foreach ( array( 'phpcs.xml.dist', 'phpcs-tooling.xml.dist' ) as $ruleset ) {
		$document = new DOMDocument();
		if ( ! $document->load( $root . '/' . $ruleset, LIBXML_NONET ) ) {
			throw new RuntimeException( 'PHPCS ruleset cannot be read: ' . $ruleset );
		}
		if ( null === $document->documentElement ) {
			throw new RuntimeException( 'PHPCS ruleset has no root: ' . $ruleset );
		}
		foreach ( $document->documentElement->childNodes as $entry ) {
			if ( 'file' === $entry->nodeName ) {
				$standards_paths[] = trim( $entry->textContent );
			} elseif ( 'exclude-pattern' === $entry->nodeName && $entry instanceof DOMElement ) {
				$standards_exclusions[] = array(
					'pattern' => trim( $entry->textContent ),
					'type'    => $entry->getAttribute( 'type' ),
				);
			}
		}
	}
	if ( ! $standards_paths ) {
		throw new RuntimeException( 'PHPCS has no source paths.' );
	}

	$maintained = array();
	$excluded_directories = array( '.git', '.phpstan-cache', '.phpunit.cache', 'vendor', 'node_modules', 'tests', 'fixtures' );
	$directory = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
	$filter = new RecursiveCallbackFilterIterator( $directory, static function ( $file ) use ( $excluded_directories, $root ) {
		$relative = substr( $file->getPathname(), strlen( $root ) + 1 );
		if ( $file->isLink() && 'php' === $file->getExtension() ) {
			throw new RuntimeException( 'Maintained PHP is a symbolic link: ' . $relative );
		}
		return ! $file->isLink() && ( ! $file->isDir() || ! in_array( $relative, $excluded_directories, true ) );
	} );
	foreach ( new RecursiveIteratorIterator( $filter ) as $file ) {
		if ( $file->isFile() && 'php' === $file->getExtension() ) {
			$maintained[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
		}
	}
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

	foreach ( $maintained as $path ) {
		if ( ! is_file( $root . '/' . $path ) ) {
			throw new RuntimeException( 'Maintained PHP source is missing: ' . $path );
		}
		if ( ! covered_by( $path, $analysis_paths, $root ) ) {
			throw new RuntimeException( 'PHPStan does not directly cover: ' . $path );
		}
		if ( ! covered_by( $path, $standards_paths, $root ) ) {
			throw new RuntimeException( 'PHPCS does not cover: ' . $path );
		}
		if ( excluded_by_phpcs( $path, $standards_exclusions, $root ) ) {
			throw new RuntimeException( 'PHPCS excludes maintained PHP: ' . $path );
		}
	}
	fwrite( STDOUT, 'Maintained PHP coverage: ' . count( $maintained ) . " paths.\n" );
} catch ( Throwable $error ) {
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
		$location = realpath( $repository . '/' . $scope );
		$normalized_location = false === $location ? false : str_replace( '\\', '/', $location );
		$position = false === $normalized_location ? false : ( '\\' === DIRECTORY_SEPARATOR ? stripos( $normalized_location, $normalized_root . '/' ) : strpos( $normalized_location, $normalized_root . '/' ) );
		if ( 0 !== $position ) {
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
		$replacements = array( '\\,' => ',', '*' => '.*' );
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$replacements['/'] = '\\\\';
		}
		$pattern = strtr( $exclusion['pattern'], $replacements );
		$absolute = realpath( $repository . '/' . $path );
		if ( false === $absolute ) {
			throw new RuntimeException( 'Maintained PHP source is missing: ' . $path );
		}
		$target = 'relative' === $exclusion['type'] ? str_replace( '/', DIRECTORY_SEPARATOR, $path ) : $absolute;
		$match = @preg_match( '`' . $pattern . '`i', $target );
		if ( false === $match ) {
			throw new RuntimeException( 'PHPCS exclusion pattern needs review: ' . $exclusion['pattern'] );
		}
		if ( 1 === $match ) {
			return true;
		}
	}
	return false;
}
