<?php

namespace AppBuilder\Classs;

defined( 'ABSPATH' ) || exit;

/**
 * GooglePlayInfo Class.
 */
class GooglePlayInfo {

	/**
	 * Get Google Play Info
	 *
	 * @param string $package_name Package name.
	 * @param string $country Country.
	 *
	 * @return array
	 */
	public static function get_google_play_info( $package_name, $country = 'US' ) {
		$url  = 'https://play.google.com/store/apps/details?id=' . $package_name . '&hl=en' . '&gl=' . $country;
		$html = '';

		$res = app_builder()->get( 'http' )->get( $url );

		if ( is_wp_error( $res ) ) {
			return array();
		}

		$html = wp_remote_retrieve_body( $res );

		if ( empty( $html ) ) {
			return array();
		}

		// Get current version.
		$matches = array();
		preg_match( '/\[\[\[\"\d+\.\d+\.\d+/', $html, $matches );

		$verion = substr( current( $matches ), 4 );

		// Get changelogs.
		$matches   = array();
		$changelog = '';
		preg_match( '/<div itemprop="description">(.*?)<\/div>/', $html, $matches );
		if ( isset( $matches[1] ) ) {
			$changelog = $matches[1];
		}

		// Get date.
		$matches      = array();
		$updated_date = '';
		preg_match( '/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{1,2}, \d{4}/', $html, $matches );
		if ( isset( $matches[0] ) ) {
			// Format date same input date html.
			$date         = date_create( $matches[0] );
			$updated_date = date_format( $date, 'M d, Y' );
		}

		return array(
			'version'      => $verion,
			'updated_date' => $updated_date,
			'changelog'    => $changelog,
			'platform'     => 'android',
		);
	}
}
