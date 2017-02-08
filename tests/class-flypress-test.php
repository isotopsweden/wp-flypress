<?php

namespace Isotop\Tests\Flypress;

use Isotop\Flypress\Flypress;
use League\Flysystem\Adapter\Local;

class Flypress_Test extends \WP_UnitTestCase {

	public function test_fly() {
		$fly = new Flypress;
		$this->assertNull( $fly->get_adapter() );
	}

	public function test_filter_upload_url() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'fly://uploads', $out['basedir'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_filter_upload_url_base_path() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );

		add_filter( 'flypress_base_path', function () {
			return 'fly://bwh';
		} );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'fly://bwh/uploads', $out['basedir'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_filter_upload_url_upload_url() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );

		add_filter( 'flypress_upload_url', function () {
			return 'http://localhost/';
		} );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'http://localhost/uploads', $out['baseurl'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_filter_delete_file() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );
		$dir = wp_upload_dir();

		$out = $fly->filter_delete_file( $dir['basedir'] . '/2017/02/me.jpg' );
		$this->assertSame( '2017/02/me.jpg', $out );
	}

	public function test_get_attachment_url_default() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );

		$url = 'http://example.org/wp-content/uploads/2017/02/me.jpg';
		$out = $fly->get_attachment_url( $url );
		$this->assertSame( $url, $out );
	}

	public function test_get_attachment_custom_upload_url() {
		$fly = new Flypress( new Local( __DIR__ . '/trunk' ) );
		$url = 'http://example.org/wp-content/uploads/2017/02/me.jpg';

		add_filter( 'flypress_upload_url', function () {
			return 'http://localhost/';
		} );

		$out = $fly->get_attachment_url( $url );

		$this->assertSame( 'http://localhost/uploads/2017/02/me.jpg', $out );
	}
}
