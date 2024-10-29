<?php

namespace AppBuilder\Classs;

defined( 'ABSPATH' ) || exit;

/**
 * ItunesSearchApi Class.
 */
class ItunesSearchApi {
	private $iTunesDocumentationURL = 'https://affiliate.itunes.apple.com/resources/documentation/itunes-store-web-service-search-api/';
	private $lookupPrefixURL        = 'https://itunes.apple.com/lookup';
	private $searchPrefixURL        = 'https://itunes.apple.com/search';
	private $debugLogging           = false;

	public function lookupByBundleId( $bundleId, $country = 'US', $useCacheBuster = true ) {
		if ( empty( $bundleId ) ) {
			return null;
		}

		$url = $this->lookupURLByBundleId( $bundleId, $country, $useCacheBuster );

		try {
			$response = app_builder()->get( 'http' )->get( $url );
			$body     = wp_remote_retrieve_body( $response );

			$decodedResults = json_decode( $body, true );
			return $decodedResults;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	public function lookupURLByBundleId( $bundleId, $country = 'US', $useCacheBuster = true ) {
		if ( empty( $bundleId ) ) {
			return null;
		}

		return $this->lookupURLByQSP(
			array(
				'bundleId' => $bundleId,
				'country'  => strtoupper( $country ),
			),
			$useCacheBuster
		);
	}

	public function lookupURLByQSP( $qsp, $useCacheBuster = true ) {
		if ( empty( $qsp ) ) {
			return null;
		}

		$parameters = array();
		foreach ( $qsp as $key => $value ) {
			array_push( $parameters, $key . '=' . $value );
		}
		if ( $useCacheBuster ) {
			array_push( $parameters, '_cb=' . microtime( true ) );
		}
		$finalParameters = implode( '&', $parameters );

		return $this->lookupPrefixURL . '?' . $finalParameters;
	}
}
