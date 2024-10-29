<?php

if ( filter_input( INPUT_GET, 'app-builder-return' ) ) {
	require_once ABSPATH . '/wp-load.php';
	$success = filter_input( INPUT_GET, 'success' );
	if ( $success ) {
		$data = json_decode( get_option( 'app_builder_test_site' ), true );
		delete_option( 'app_builder_test_site' );
		// build the url to redirect to.
		$qr  = build_query( $data );
		$app = "cirilla://?$qr";
		header( 'Location: ' . $app );
		exit;
	} else {
		echo 'Error';
		exit;
	}
}

if ( filter_input( INPUT_GET, 'app-builder-callback' ) ) {
	require_once ABSPATH . '/wp-load.php';
	// Validate and sanitize the input data.
	$input_data = file_get_contents( 'php://input' );
	if ( json_decode( $input_data ) === null ) {
		wp_send_json_error( 'Invalid JSON data' );
		exit;
	}
	update_option( 'app_builder_test_site', $input_data );
	exit;
}
