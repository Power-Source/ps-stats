<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Privacy {
	public static function route( mixed $value ): ?string {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}
		$parts = wp_parse_url( $value );
		if ( false === $parts || ( isset( $parts['scheme'] ) && ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) ) {
			return null;
		}
		$host = $parts['host'] ?? '';
		if ( $host && strtolower( $host ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return null;
		}
		$path = Normalizer::target_path( $value );
		$decoded = rawurldecode( $path );
		$query = array();
		wp_parse_str( $parts['query'] ?? '', $query );
		if ( array_intersect( array_map( 'strtolower', array_keys( $query ) ), array( 'p', 'page_id', 'attachment_id', 'orderid', 'token', 'key', 'reset', 'download', 'action' ) ) ) {
			return null;
		}
		// Reject identifiers and private routes before resolving public content.
		if ( preg_match( '~(?:^|/)(?:wp-admin|wp-login\.php|wp-json|xmlrpc\.php|members|member|mitglieder|users|user|author|autoren|account|my-account|mein-konto|konto|checkout|kasse|order|orders|bestellung|bestellungen|cart|warenkorb|download|downloads|messages|nachrichten|reset-password)(?:/|$)~i', $decoded )
			|| preg_match( '/[@\x00-\x20\x7f]/', $decoded ) ) {
			return null;
		}
		foreach ( Plugin::settings()['excluded_paths'] as $excluded ) {
			if ( '/' === $excluded || $path === $excluded || str_starts_with( $path, rtrim( $excluded, '/' ) . '/' ) ) {
				return null;
			}
		}
		if ( $path === Normalizer::target_path( home_url( '/' ) ) ) {
			return $path;
		}
		$post_id = url_to_postid( Normalizer::local_url( $path ) );
		$post = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password ) {
			return null;
		}
		$type = get_post_type_object( $post->post_type );
		if ( ! $type || ! $type->public || ! $type->publicly_queryable || 'attachment' === $post->post_type ) {
			return null;
		}
		$canonical = Normalizer::target_path( get_permalink( $post_id ) );
		return $path === $canonical ? $canonical : null;
	}

	public static function referrer( mixed $value ): string {
		if ( ! Plugin::settings()['collect_referrers'] ) {
			return '';
		}
		$domain = Normalizer::referrer_domain( $value );
		return in_array( $domain, Plugin::settings()['referrer_domains'], true ) ? $domain : '';
	}

	public static function register_text(): void {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'PS Stats', wpautop( self::text() ) );
		}
	}

	public static function text(): string {
		$settings = Plugin::settings();
		$retention = $settings['retention']
			? sprintf( __( 'Statistikdaten werden nach %d Tagen automatisch bereinigt, sofern die geplante Bereinigung ausgeführt wird.', 'ps-stats' ), $settings['retention'] )
			: __( 'Für Statistikdaten ist derzeit keine automatische Löschfrist eingestellt.', 'ps-stats' );
		return __( 'Textvorschlag zur Prüfung durch den Websitebetreiber; keine rechtliche Freigabe.', 'ps-stats' ) . "\n\n"
			. __( 'Diese Website verwendet PS Stats zur lokalen Auswertung von Seitenaufrufen. Gespeichert werden der Kalendertag in der Zeitzone der Website, freigegebene öffentliche Inhaltspfade und die aggregierte Anzahl der Aufrufe. Das Plugin setzt für die Zählung keine Cookies, verwendet keine Besucher- oder Sitzungskennungen und speichert in seinen Statistiktabellen keine IP-Adressen, User-Agents oder vollständigen Referrer-URLs. HTTP-Header können während der Anfrage zur Bot-Erkennung und zur optionalen Referrer-Zuordnung ausgewertet werden.', 'ps-stats' ) . "\n\n"
			. ( $settings['collect_referrers']
				? __( 'Die Referrer-Auswertung ist aktiv. Nur ausdrücklich freigegebene externe Domains werden gespeichert; alle anderen werden ohne Herkunftsangabe gezählt.', 'ps-stats' )
				: __( 'Die Erfassung neuer Referrer-Domains ist deaktiviert.', 'ps-stats' ) ) . "\n\n"
			. ( $settings['marketpress_metrics']
				? __( 'Die optionale MarketPress-Quelle zeigt vorhandene Bestellwerte und Gratis-Download-Ereignisse ausschließlich als Monatsaggregate für Administratoren an. PS Stats erfasst keine zusätzlichen Shop-Ereignisse und speichert keine Kopien dieser Quelldaten. MarketPress verarbeitet die zugrunde liegenden Bestell- und Benutzerinformationen eigenständig; dafür sind eine gesonderte Rechtsgrundlage, Information und Löschregelung zu prüfen.', 'ps-stats' )
				: __( 'Die zusätzliche MarketPress-Quellenauswertung ist deaktiviert.', 'ps-stats' ) ) . "\n\n"
			. $retention . "\n\n"
			. __( 'PS Stats sendet keine Statistikdaten an externe Dienste. Personenbezogene Inhalte können dennoch in öffentlichen Pfaden oder älteren Daten vorkommen; diese sind vom Betreiber zu prüfen. Deaktivierte Erfassungsoptionen löschen keine vorhandenen Daten. Serverprotokolle, Daten anderer Plugins und Sicherungskopien unterliegen gesonderten Regeln. Der Betreiber muss Zweck, Rechtsgrundlage, mögliche Einwilligungsanforderungen, Empfänger, Betroffenenrechte und die Fristen für Sicherungskopien passend zum konkreten Betrieb ergänzen. Der Verzicht auf Cookies allein ist keine Garantie für Einwilligungsfreiheit oder DSGVO-Konformität.', 'ps-stats' );
	}
}
