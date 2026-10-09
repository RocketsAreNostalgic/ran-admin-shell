<?php
/**
 * Explicit consumer resource synchronization.
 *
 * @package RAN_Admin_Shell
 */

namespace RAN\AdminShell\Tool;

/**
 * Synchronize canonical package resources into a consumer repository.
 */
final class SyncCommand {
	/** Package name recorded in consumer provenance. */
	const PACKAGE_NAME = 'ran/admin-shell';

	/**
	 * Run the command.
	 *
	 * @param array<int, string> $argv CLI arguments.
	 * @return int
	 */
	public static function main( array $argv ) {
		try {
			$options = self::parse_options( $argv );
			$command = $options['command'];
			$config  = self::load_configuration( $options['config'] );

			if ( 'sync' === $command ) {
				self::sync( $config );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
				fwrite( STDOUT, "RAN Admin Shell resources synchronized.\n" );
				return 0;
			}

			if ( self::check( $config, $options['immutable'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
				fwrite( STDOUT, "RAN Admin Shell resources are current.\n" );
				return 0;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
			fwrite( STDERR, "RAN Admin Shell resource drift detected.\n" );
			return 1;
		} catch ( \Throwable $error ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write standalone command diagnostics to the existing STDOUT/STDERR stream.
			fwrite( STDERR, 'RAN Admin Shell: ' . $error->getMessage() . "\n" );
			return 2;
		}
	}

	/**
	 * Copy canonical resources and write provenance.
	 *
	 * @param array{php:string,css:string,provenance:string,root:string} $config Validated configuration.
	 * @return void
	 */
	public static function sync( array $config ) {
		$resources = self::resources( $config );

		foreach ( $resources as $destination => $source ) {
			self::atomic_copy( $source, $destination, $config['root'] );
		}

		$provenance = self::provenance( $config, $resources );
		self::atomic_write(
			$config['provenance'],
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
			(string) json_encode( $provenance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n",
			$config['root']
		);
	}

	/**
	 * Check canonical bytes and provenance without changing the consumer.
	 *
	 * @param array{php:string,css:string,provenance:string,root:string} $config Validated configuration.
	 * @param bool  $immutable Require an immutable VCS lock reference.
	 * @return bool
	 */
	public static function check( array $config, $immutable = false ) {
		$resources = self::resources( $config );

		foreach ( $resources as $destination => $source ) {
			if ( ! is_file( $destination ) || is_link( $destination ) || ! hash_equals( hash_file( 'sha256', $source ), hash_file( 'sha256', $destination ) ) ) {
				return false;
			}
		}

		if ( $immutable ) {
			$locked = self::locked_metadata( $config['root'], true );
			self::assert_installed_metadata( $locked );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native JSON preserves the standalone serialization flags and bytes without loading WordPress.
		$expected = (string) json_encode( self::provenance( $config, $resources ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";

		return is_file( $config['provenance'] )
			&& ! is_link( $config['provenance'] )
			&& hash_equals( hash( 'sha256', $expected ), hash_file( 'sha256', $config['provenance'] ) );
	}

	/**
	 * Parse command-line options.
	 *
	 * @param array<int, string> $argv CLI arguments.
	 * @return array{command:string,config:string,immutable:bool}
	 */
	private static function parse_options( array $argv ) {
		$command   = isset( $argv[1] ) ? (string) $argv[1] : '';
		$config    = 'ran-admin-shell.json';
		$immutable = false;

		if ( ! in_array( $command, array( 'sync', 'check' ), true ) ) {
			throw new \RuntimeException( 'Usage: ran-admin-shell <sync|check> [--config=path] [--immutable]' );
		}

		foreach ( array_slice( $argv, 2 ) as $argument ) {
			if ( 0 === strpos( $argument, '--config=' ) ) {
				$config = substr( $argument, 9 );
			} elseif ( '--immutable' === $argument && 'check' === $command ) {
				$immutable = true;
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
				throw new \RuntimeException( 'Unknown command option: ' . $argument );
			}
		}

		return array(
			'command'   => $command,
			'config'    => $config,
			'immutable' => $immutable,
		);
	}

	/**
	 * Load and validate a consumer configuration.
	 *
	 * @param mixed $config_path Configuration path, converted to a string.
	 * @return array{php:string,css:string,provenance:string,root:string}
	 */
	public static function load_configuration( $config_path ) {
		$config_path = (string) $config_path;
		if ( '' === $config_path || ! is_file( $config_path ) || is_link( $config_path ) ) {
			throw new \RuntimeException( 'Configuration file is missing or unsafe.' );
		}

		$root = realpath( dirname( $config_path ) );
		if ( false === $root ) {
			throw new \RuntimeException( 'Unable to resolve the consumer root.' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$data = json_decode( (string) file_get_contents( $config_path ), true );
		if ( ! is_array( $data ) || 1 !== ( $data['schema'] ?? null ) ) {
			throw new \RuntimeException( 'Configuration schema must be 1.' );
		}

		$paths = array();
		foreach ( array( 'php', 'css', 'provenance' ) as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_string( $data[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
				throw new \RuntimeException( 'Configuration is missing ' . $key . '.' );
			}
			$paths[ $key ] = self::safe_destination( $root, $data[ $key ] );
		}

		if ( 3 !== count( array_unique( $paths ) ) ) {
			throw new \RuntimeException( 'Resource destinations must be unique.' );
		}

		return array_merge( $paths, array( 'root' => $root ) );
	}

	/**
	 * Resolve a safe consumer-owned destination.
	 *
	 * @param string $root Consumer root.
	 * @param mixed $relative Relative destination, converted to a string.
	 * @return string
	 */
	private static function safe_destination( $root, $relative ) {
		$relative = str_replace( '\\', '/', trim( (string) $relative ) );
		if ( '' === $relative || '/' === $relative[0] || preg_match( '/^[A-Za-z]:\//', $relative ) || in_array( '..', explode( '/', $relative ), true ) ) {
			throw new \RuntimeException( 'Resource destination must be a safe relative path.' );
		}

		$destination = $root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
		$current     = dirname( $destination );
		$root_length = strlen( $root );
		// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- Recheck the changing parent path length each iteration to preserve the traversal fence.
		while ( $current !== $root && strlen( $current ) >= $root_length ) {
			if ( is_link( $current ) ) {
				throw new \RuntimeException( 'Resource destination traverses a symbolic link.' );
			}
			$current = dirname( $current );
		}

		if ( is_link( $destination ) ) {
			throw new \RuntimeException( 'Resource destination is a symbolic link.' );
		}

		return $destination;
	}

	/**
	 * Return destination-to-source mapping.
	 *
	 * @param array{php:string,css:string,provenance:string,root:string} $config Validated configuration.
	 * @return array<string, string>
	 */
	private static function resources( array $config ) {
		$package_root = dirname( __DIR__ );
		return array(
			$config['php'] => $package_root . '/resources/admin-shell.php',
			$config['css'] => $package_root . '/resources/admin-shell.css',
		);
	}

	/**
	 * Build deterministic provenance.
	 *
	 * @param array{php:string,css:string,provenance:string,root:string} $config Validated configuration.
	 * @param array<string, string> $resources Destination-to-source mapping.
	 * @return array{schema:int,package:string,version:string,reference:string,files:array<string,string>}
	 */
	private static function provenance( array $config, array $resources ) {
		$metadata = self::locked_metadata( $config['root'], false );
		$files    = array();
		foreach ( $resources as $destination => $source ) {
			$relative           = str_replace( DIRECTORY_SEPARATOR, '/', substr( $destination, strlen( $config['root'] ) + 1 ) );
			$files[ $relative ] = 'sha256:' . hash_file( 'sha256', $source );
		}
		ksort( $files );

		return array(
			'schema'    => 1,
			'package'   => self::PACKAGE_NAME,
			'version'   => $metadata['version'],
			'reference' => $metadata['reference'],
			'files'     => $files,
		);
	}

	/**
	 * Read package metadata from the consumer lock file.
	 *
	 * @param string $root Consumer root.
	 * @param bool $immutable Require an immutable source reference.
	 * @return array{version:string,reference:string}
	 */
	private static function locked_metadata( $root, $immutable ) {
		$lock_path = $root . '/composer.lock';
		if ( ! is_file( $lock_path ) || is_link( $lock_path ) ) {
			if ( $immutable ) {
				throw new \RuntimeException( 'Immutable verification requires composer.lock.' );
			}
			return array(
				'version'   => 'unlocked',
				'reference' => 'unlocked',
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		$lock = json_decode( (string) file_get_contents( $lock_path ), true );
		foreach ( array_merge( $lock['packages'] ?? array(), $lock['packages-dev'] ?? array() ) as $package ) {
			if ( self::PACKAGE_NAME !== ( $package['name'] ?? '' ) ) {
				continue;
			}
			$reference = (string) ( $package['source']['reference'] ?? '' );
			$version   = (string) ( $package['version'] ?? '' );
			if ( $immutable && ( 'git' !== ( $package['source']['type'] ?? '' ) || 1 !== preg_match( '/^[a-f0-9]{40}$/i', $reference ) ) ) {
				throw new \RuntimeException( 'Immutable verification requires a full Git source reference.' );
			}
			return array(
				'version'   => $version,
				'reference' => $reference,
			);
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal standalone CLI exception data; the CLI reports it to STDERR, not HTML.
		throw new \RuntimeException( 'composer.lock does not contain ' . self::PACKAGE_NAME . '.' );
	}

	/**
	 * Require the installed package to match the immutable lock reference.
	 *
	 * @param array{version:string,reference:string} $locked Consumer lock metadata.
	 * @return void
	 */
	private static function assert_installed_metadata( array $locked ) {
		if ( ! class_exists( '\\Composer\\InstalledVersions' ) || ! \Composer\InstalledVersions::isInstalled( self::PACKAGE_NAME ) ) {
			throw new \RuntimeException( 'Immutable verification requires Composer installed metadata.' );
		}

		$installed_reference = (string) \Composer\InstalledVersions::getReference( self::PACKAGE_NAME );
		$install_path        = (string) \Composer\InstalledVersions::getInstallPath( self::PACKAGE_NAME );
		if ( '' === $install_path || is_link( $install_path ) ) {
			throw new \RuntimeException( 'Immutable verification rejects linked or unknown package installs.' );
		}
		if ( ! hash_equals( strtolower( $locked['reference'] ), strtolower( $installed_reference ) ) ) {
			throw new \RuntimeException( 'Installed package reference does not match composer.lock.' );
		}
	}

	/**
	 * Atomically copy one resource.
	 *
	 * @param string $source Canonical resource.
	 * @param string $destination Consumer destination.
	 * @param string $root Consumer root.
	 * @return void
	 */
	private static function atomic_copy( $source, $destination, $root ) {
		if ( ! is_file( $source ) ) {
			throw new \RuntimeException( 'Canonical package resource is missing.' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read exact native file bytes for standalone configuration, immutable resource verification or isolated fixture evidence.
		self::atomic_write( $destination, (string) file_get_contents( $source ), $root );
	}

	/**
	 * Atomically write bytes to a safe destination.
	 *
	 * @param string $destination Consumer destination.
	 * @param string $contents Resource bytes.
	 * @param string $root Consumer root.
	 * @return void
	 */
	private static function atomic_write( $destination, $contents, $root ) {
		$directory = dirname( $destination );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create native directories for standalone synchronization or the isolated fixture; retain mode and existence checks.
		if ( ! is_dir( $directory ) && ! mkdir( $directory, 0777, true ) && ! is_dir( $directory ) ) {
			throw new \RuntimeException( 'Unable to create resource destination directory.' );
		}
		self::safe_destination( $root, substr( $destination, strlen( $root ) + 1 ) );
		$temporary = tempnam( $directory, '.ran-admin-shell-' );
		if ( false === $temporary ) {
			throw new \RuntimeException( 'Unable to create a temporary resource file.' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.rename_rename -- Write exact bytes to the owned native temporary/fixture path; preserve filesystem and distribution observations. Native same-directory rename is the atomic resource replacement boundary.
		if ( false === file_put_contents( $temporary, $contents ) || ! rename( $temporary, $destination ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink -- Best-effort removal of the owned temporary file preserves the original replacement failure. Remove owned native fixture/temporary entries with the existing link and cleanup boundaries.
			@unlink( $temporary );
			throw new \RuntimeException( 'Unable to replace a synchronized resource.' );
		}
	}
}
