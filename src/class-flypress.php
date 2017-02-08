<?php

namespace Isotop\Flypress;

use Exception;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use Twistor\FlysystemStreamWrapper;

class Flypress {

	/**
	 * Flysystem adapter instance.
	 *
	 * @var \League\Flysystem\AdapterInterface
	 */
	protected $adapter;

	/**
	 * Filsystem instance.
	 *
	 * @var \League\Flysystem\Filesystem
	 */
	protected $filesystem;

	/**
	 * Orginal upload directory.
	 *
	 * @var array
	 */
	protected $orginal_dir;

	/**
	 * Flypress construct.
	 */
	public function __construct( AdapterInterface $adapter = null ) {
		// Set default adapter.
		if ( ! is_null( $adapter ) ) {
			$this->adapter = $adapter;
		}

		// Load built in adapter if exists.
		if ( defined( 'FLYPRESS_ADAPTER' ) && file_exists( __DIR__ . '/adapters/' . strtolower( FLYPRESS_ADAPTER ) . '.php' ) ) {
			require_once __DIR__ . '/adapters/' . strtolower( FLYPRESS_ADAPTER ) . '.php';
		}

		/**
		 * Modify default adapter.
		 *
		 * @param \League\Flysystem\AdapterInterface $adapter
		 */
		$this->adapter = apply_filters( 'flypress_adapter', $this->adapter );

		// Bail if no adapter or not a instance of adapter interface. Flypress requires a adapter to work.
		if ( ! $this->adapter || $this->adapter instanceof AdapterInterface === false ) {
			return;
		}

		// Create a new filesystem instance.
		$this->filesystem = new Filesystem( $this->adapter );

		// Register flysystem stream wrapper.
		FlysystemStreamWrapper::register( 'fly', $this->filesystem );

		// Set original upload directory.
		$this->orginal_dir = wp_upload_dir();

		// Setup filter for filtering upload directory.
		add_filter( 'upload_dir', [$this, 'filter_upload_dir'] );

		// Setup filter for filtering delete file path.
		add_filter( 'wp_delete_file', [$this, 'filter_delete_file'] );

		// Setup filter for filtering attachment url.
		add_filter( 'wp_get_attachment_url', [$this, 'get_attachment_url'] );
	}

	/**
	 * Modify upload directory paths and urls to fly paths and urls.
	 *
	 * @param  array $dir
	 *
	 * @return array
	 */
	public function filter_upload_dir( array $dir ) {
		/**
		 * Get Flypress upload url.
		 *
		 * @param string $url
		 */
		$url = apply_filters( 'flypress_upload_url', $this->orginal_dir['baseurl'] );
		$url = is_string( $url ) ? $url : $this->orginal_dir['baseurl'];
		$url = rtrim( $url, '/' );

		/**
		 * Get Flypress base path.
		 *
		 * @param  string $path
		 */
		$base_path = apply_filters( 'flypress_base_path', 'fly://' );
		$base_path = is_string( $base_path ) ? $base_path : 'fly://';
		$base_path = $base_path[strlen( $base_path )-1] === '/' ? str_replace( '://', ':/', $base_path ) : $base_path;

		// Replace upload directory paths with fly path.
		$dir['path']    = str_replace( WP_CONTENT_DIR, $base_path, $dir['path'] );
		$dir['basedir'] = str_replace( WP_CONTENT_DIR, $base_path, $dir['basedir'] );

		// Replace upload directory urls with fly path.
		$dir['url']     = str_replace( $base_path, $url, $dir['path'] );
		$dir['baseurl'] = str_replace( $base_path, $url, $dir['basedir'] );

		// Sometimes you get 'uploads/uploads' and that's bad.
		$uploads = defined( 'UPLOADS' ) ? UPLOADS : '/uploads';
		$dir['url'] = str_replace( $uploads.$uploads, $uploads, $dir['url'] );
		$dir['baseurl'] = str_replace( $uploads.$uploads, $uploads, $dir['baseurl'] );

		return $dir;
	}

	/**
	 * Filter delete file to work with fly protocol.
	 *
	 * @param  string $path
	 *
	 * @return string
	 */
	public function filter_delete_file( string $path ) {
		$dir = wp_upload_dir();

		return str_replace( trailingslashit( $dir['basedir'] ), '', $path );
	}

	/**
	 * Get adapter.
	 *
	 * @return \League\Flysystem\AdapterInterface
	 */
	public function get_adapter() {
		return $this->adapter;
	}

	/**
	 * Get flypress attachment url.
	 *
	 * @param  string url
	 *
	 * @return string
	 */
	public function get_attachment_url( string $url ) {
		$dir = wp_upload_dir();
		$url = str_replace( $this->orginal_dir['baseurl'], $dir['baseurl'], $url );

		/**
		 * Modify Flypress attachment url.
		 *
		 * @param  string $url
		 * @param  \League\Flysystem\AdapterInterface $adapter
		 */
		return apply_filters( 'flypress_attachment_url', $url, $this->adapter );
	}
}
