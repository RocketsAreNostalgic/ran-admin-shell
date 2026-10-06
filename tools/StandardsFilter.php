<?php
/** Include the one maintained extensionless PHP entrypoint in both PHPCS and PHPCBF. */

class StandardsFilter extends \PHP_CodeSniffer\Filters\Filter {
	// phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- Required PHPCS Filter override; preserve the checker callback signature.
	protected function shouldProcessFile( $path ) {
		if ( realpath( $path ) === realpath( dirname( __DIR__ ) . '/bin/ran-admin-shell' ) ) {
			return true;
		}
		return parent::shouldProcessFile( $path );
	}
}
