<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	private static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
		$file     = __DIR__ . '/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
