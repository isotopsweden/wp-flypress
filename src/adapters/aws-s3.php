<?php

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

/**
 * Configure flypress to use a different upload url.
 *
 * @return string
 */
add_filter( 'flypress_upload_url', function () {
	// Use predefined AWS S3 url if any.
	if ( defined( 'AWS_S3_BUCKET_URL' ) ) {
		return AWS_S3_BUCKET_URL;
	}

	// Use predefined AWS S3 region if any.
	if ( defined( 'AWS_S3_REGION' ) ) {
		return sprintf( '%s://%s.%s.amazonaws.com', ( is_ssl() ? 'https' : 'http' ), strtolower( AWS_S3_BUCKET ), strtolower( AWS_S3_REGION ) );
	}

	// If no custom url or no predefined region, then use standard AWS S3 url.
	return sprintf( '%s://%s.s3.amazonaws.com', ( is_ssl() ? 'https' : 'http' ), strtolower( AWS_S3_BUCKET ) );
} );

/**
 * Configure flypress to use AWS S3 Adapter.
 *
 * @return \League\Flysystem\AdapterInterface
 */
add_filter( 'flypress_adapter', function () {
	// Bail if no AWS S3 bucket.
	if ( ! defined( 'AWS_S3_BUCKET' ) ) {
		return;
	}

	// Bail if no AWS S3 key or secret.
	if ( ! defined( 'AWS_S3_KEY' ) || ! defined( 'AWS_S3_SECRET' ) ) {
		return;
	}

	$params = [
		'version'     => 'latest',
		'region'      => 'us-east-1',
		'signature'   => 'v4',
		'credentials' => [
			'key'    => AWS_S3_KEY,
			'secret' => AWS_S3_SECRET
		]
	];

	// Add custom endpoint if defined.
	if ( defined( 'AWS_S3_ENDPOINT' ) ) {
		$params['endpoint'] = strtolower( AWS_S3_ENDPOINT );
	}

	// Add custom region if defined.
	if ( defined( 'AWS_S3_REGION' ) ) {
		$params['region'] = strtolower( AWS_S3_REGION );
	}

	// Add proxy settings if defined.
	if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {
		$proxy_auth    = '';
		$proxy_address = WP_PROXY_HOST . ':' . WP_PROXY_PORT;

		if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
			$proxy_auth = WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD . '@';
		}

		$params['request.options']['proxy'] = $proxy_auth . $proxy_address;
	}

	// Create a new AWS S3 client with flysystem adapter.
	return new AwsS3Adapter( new S3Client( $params ), AWS_S3_BUCKET );
} );
