<?php

namespace Isotop\Tests\Flypress;

use Isotop\Flypress\Flypress;
use League\Flysystem\Adapter\Local;

class Flypress_Test extends \WP_UnitTestCase {

	public function create_image( $name ) {
		$img = imagecreatetruecolor( 120, 20 );
		$bg = imagecolorallocate( $img, 255, 255, 255 );
		imagefilledrectangle( $img, 0, 0, 120, 20, $bg );
		ob_start();
		imagepng( $img );
		$contents =  ob_get_contents();
		ob_end_clean();
		$stream = tmpfile();
		fwrite( $stream, $contents );
		rewind( $stream );
		return $stream;
	}


	public function test_fly() {
		$fly = new Flypress;
		$this->assertNull( $fly->adapter() );

		$fly = new Flypress( new Local( '/tmp/flypress' ) );
		$this->assertInstanceOf( '\League\Flysystem\Filesystem', $fly->filesystem() );
	}

	public function test_filter_read_image_metadata() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		if ( $fly->filesystem()->has( 'test.png' ) ) {
			$fly->filesystem()->delete( 'test.png' );
		}

		$img = $this->create_image( 'fly://test.png' );

		$fly->filesystem()->writeStream( 'test.png', $img );

		fclose( $img );

		$out = $fly->filter_read_image_metadata( [], 'fly://test.png' );
		$this->assertNotEmpty( $out );
	}

	public function test_filter_upload_url() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'fly://uploads', $out['basedir'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_filter_image_editors() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		$out = $fly->filter_image_editors( ['WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'] );

		$this->assertSame( ['Isotop\\Flypress\\Image_Editor_GD', 'Isotop\\Flypress\\Image_Editor_Imagick'], $out );
	}

	public function test_filter_handle_upload_prefilter() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		$file = [
			'name' => 'path/to/file'
		];

		$this->assertSame( $file, $fly->filter_handle_upload_prefilter( $file ) );

		$file = [
			'name' => 'path/to/file.png'
		];

		$this->assertNotSame( $file, $fly->filter_handle_upload_prefilter( $file ) );
	}

	public function test_filter_upload_url_base_path() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		add_filter( 'flypress_base_path', function () {
			return 'fly://bwh';
		} );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'fly://bwh/uploads', $out['basedir'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_filter_upload_url_upload_url() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		add_filter( 'flypress_upload_url', function () {
			return 'http://localhost/';
		} );

		$dir = wp_upload_dir();
		$out = $fly->filter_upload_dir( $dir );
		$this->assertSame( 'http://localhost/uploads', $out['baseurl'] );
		$this->assertFalse( $out['error'] );
	}

	public function test_get_attachment_url_default() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );

		$url = 'http://example.org/wp-content/uploads/2017/02/me.jpg';
		$out = $fly->get_attachment_url( $url );
		$this->assertSame( $url, $out );
	}

	public function test_get_attachment_custom_upload_url() {
		$fly = new Flypress( new Local( '/tmp/flypress' ) );
		$url = 'http://example.org/wp-content/uploads/2017/02/me.jpg';

		add_filter( 'flypress_upload_url', function () {
			return 'http://localhost/';
		} );

		$out = $fly->get_attachment_url( $url );

		$this->assertSame( 'http://localhost/uploads/2017/02/me.jpg', $out );
	}
}
