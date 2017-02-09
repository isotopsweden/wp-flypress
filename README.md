# Flypress [![Build Status](https://travis-ci.org/isotopsweden/wp-flypress.svg?branch=master)](https://travis-ci.org/isotopsweden/wp-flypress)

> WIP - Requires PHP 7.0 and WordPress 4.6

Use [Flysystem](https://flysystem.thephpleague.com/) with WordPress. Flypress will rename each file uploaded to WordPress with a new name based on uuid v4, the attachment title will not be changed to uuid.

## Installation

```
composer require isotopsweden/wp-flypress
```

## Usage

Example configuration for using the built in adapter for AWS S3 with [Minio](https://minio.io/) locally. Region and endpoint constant is optional.

```php
/* Flypress */
define( 'FLYPRESS_ADAPTER', 'aws-s3' );

/* AWS S3 */
define( 'AWS_S3_ENDPOINT', 'http://localhost:9999/' );
define( 'AWS_S3_BUCKET_URL', 'http://localhost:9999/bwh' );
define( 'AWS_S3_BUCKET', 'bwh' );
define( 'AWS_S3_KEY', 'key' );
define( 'AWS_S3_SECRET', 'secret' );
define( 'AWS_S3_REGION', 'us-east-1' );
```

## Custom adapter

Add a custom adapter to flypress using `flypress_adapter` filter.

```php
add_filter( 'flypress_adapter', function () {
  return new \League\Flysystem\Adapter\Local( '/path/to/folder' );
} );
```

With `flypress_upload_url` can you modify how the base url of a upload url looks like.

```php
add_filter( 'flypress_upload_url', function () {
  return 'http://localhost:9000/';
} );
```

Look at the built in `aws-s3` adapter to check how we created a working flypress adapter and if you look at the source code for flypress you will find some more filters that you can hook into.

## Todo

- [ ] Fix so sizes works

## License

MIT © Isotop
