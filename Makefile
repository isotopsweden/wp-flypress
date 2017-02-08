lint:
	make lint:php

lint\:php:
	vendor/bin/phpcs -s --extensions=php --standard=phpcs.xml src/

test:
	vendor/bin/phpunit
