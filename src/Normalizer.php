<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Normalizer {
	public static function target_path( mixed $value ): string {
		$value = is_string( $value ) ? trim( wp_unslash( $value ) ) : '';
		$path  = wp_parse_url( $value, PHP_URL_PATH );
		$path  = is_string( $path ) && '' !== $path ? $path : '/';
		$path  = preg_replace( '#/+#', '/', $path ) ?? '/';
		$path  = '/' . ltrim( $path, '/' );
		$path  = preg_replace( '/[\x00-\x1F\x7F]/u', '', $path ) ?? '/';

		if ( strlen( $path ) > 300 ) {
			$path = function_exists( 'mb_strcut' ) ? mb_strcut( $path, 0, 300, 'UTF-8' ) : substr( $path, 0, 300 );
		}

		return '' !== $path ? $path : '/';
	}

	public static function referrer_domain( mixed $value ): string {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return '';
		}

		$host = wp_parse_url( trim( wp_unslash( $value ) ), PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			$host = wp_parse_url( 'https://' . ltrim( trim( $value ), '/' ), PHP_URL_HOST );
		}
		if ( ! is_string( $host ) ) {
			return '';
		}

		$host      = strtolower( rtrim( $host, '.' ) );
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( self::comparable_host( $host ) === self::comparable_host( $home_host ) ) {
			return '';
		}

		if ( strlen( $host ) > 190 || ! preg_match( '/^(?:[a-z0-9\x80-\xff](?:[a-z0-9\x80-\xff-]{0,61}[a-z0-9\x80-\xff])?\.)*[a-z0-9\x80-\xff](?:[a-z0-9\x80-\xff-]{0,61}[a-z0-9\x80-\xff])?$/iu', $host ) ) {
			return '';
		}

		return $host;
	}

	private static function comparable_host( string $host ): string {
		return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}
}
