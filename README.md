# Flypress [![Build Status](https://travis-ci.org/isotopsweden/wp-flypress.svg?branch=master)](https://travis-ci.org/isotopsweden/wp-flypress)

> WIP - Requires PHP 7.0 and WordPress 4.6

Use [Flysystem](https://flysystem.thephpleague.com/) with WordPress.

## Installation

```
composer require isotopsweden/wp-flypress
```

## Usage

Example configuration for using the built in adapter for AWS S3 with [Minio](https://minio.io/) locally. Endpoint constant is not needed when using real AWS S3.

```php
/* Flypress */
define( 'FLYPRESS_ADAPTER', 'aws-s3' );

/* AWS S3 */
define( 'AWS_S3_ENDPOINT', 'http://localhost:9999/' );
define( 'AWS_S3_BUCKET_URL', 'http://localhost:9999/bwh' );
define( 'AWS_S3_BUCKET', 'bwh' );
define( 'AWS_S3_KEY', 'key' );
define( 'AWS_S3_SECRET', 'secret' );
```

## Todo

- [ ] Fix so sizes works

## License

MIT © Isotop
