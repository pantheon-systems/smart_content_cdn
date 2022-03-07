# Smart Content CDN

[![Unsupported](https://img.shields.io/badge/pantheon-unsupported-yellow?logo=pantheon&color=FFDC28)](https://github.com/topics/unsupported?q=org%3Apantheon-systems "Unsupported, e.g. a tool we are actively using internally and are making available, but do not promise to support") ![Build Status](https://github.com/pantheon-systems/smart_content_cdn/actions/workflows/main.yml/badge.svg) ![Package Version](https://img.shields.io/packagist/v/pantheon-systems/smart_content_cdn)

Drupal module that extends [`smart_content`](https://www.drupal.org/project/smart_content) to support Pantheon Edge Integrations and personalization features.

## Tests & Linting

This module runs [PHPUnit](https://phpunit.de/) tests and [PHP_CodeSniffer](https://phpcs.de/) linting via the [Drupal Coder](https://www.drupal.org/project/coder) package.

PHPUnit tests can be run with Composer with the `composer test:unit` command. Additional tests can be added with the same `test:` prefix and added to the `composer test` command.

PHPCS linting can be run with Composer with the `composer lint:php` command. The `phpcbf` command can be used to automatically fix linting errors by running `composer lint:phpcbf`. Additional linting (e.g. ESLint) can be added with the same `lint:` prefix and added to the `composer lint` command.
