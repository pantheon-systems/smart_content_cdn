# Smart Content CDN

[![Unsupported](https://img.shields.io/badge/pantheon-unsupported-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#unsupported) ![Build Status](https://github.com/pantheon-systems/smart_content_cdn/actions/workflows/main.yml/badge.svg) ![Package Version](https://img.shields.io/packagist/v/pantheon-systems/smart_content_cdn)

Drupal module that extends [`smart_content`](https://www.drupal.org/project/smart_content) to support Pantheon Edge Integrations and personalization features.

## Installation

We recommend using Composer to install this module. In your project root, run:

```
composer require pantheon-systems/smart_content_cdn
```

This will install the Smart Content module, Smart Content CDN and [`pantheon-systems/pantheon-edge-integrations`](https://github.com/pantheon-systems/pantheon-edge-integrations) -- a PHP library that is _required_ by Smart Content CDN. Smart Content CDN will not function properly without the `pantheon-edge-integrations` library.

For detailed instructions on how to install and set up Smart Content CDN, see the [Edge Integration Guide](https://pantheon.io/docs/guides/edge-integrations).

## API

It is possible to retrieve header information using Smart Content CDN within your own custom module. This can be used in any class context or procedural context in any hook.

1. Include the library with the `use` statement.
    ``` php
    use Pantheon\EI\HeaderData;
    ```
1. Use the snippet below to obtain the header data object
    ``` php
    // Get header data.
    $smart_content_cdn = new HeaderData();
    $p_obj = $smart_content_cdn->returnPersonalizationObject();
    ```

### Drupal Event Subscriber Vary Header

It is possible to set a Vary header within a Drupal Event Subscriber, giving the possibility of customizing content on a per-user basis.

``` php
/**
 * This method is called when the kernel.response is dispatched.
 *
 * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
 *   The dispatched event.
 */
public function onRespond(FilterResponseEvent $event) {
  $config = \Drupal::configFactory()->get('smart_content_cdn.config');

  // Check if Vary Header should be set.
  if ($config->get('set_vary') ?? TRUE) {
    $response = $event->getResponse();

    // Header keys to add to Vary header.
    $vary_headers = ['Audience', 'Interest', 'Role'];

    // Retrieve and set vary header.
    $smart_content_cdn = new HeaderData();
    $response_vary_header = $smart_content_cdn->returnVaryHeader($vary_headers);
    $response->headers->add($response_vary_header);
  }
}
```

## Integrations

There are a few different ways to extend the capabilities of the Smart Content CDN module.

### Smart Content Preview

Use the [Smart Content Preview](https://www.drupal.org/project/smart_content_preview) to allow previewing different segments that you have set up.

### Smart Content SSR

The [Smart Content SSR](https://www.drupal.org/project/smart_content_ssr) module adds a server-side rendering Decision block, based on the Decision block that the Smart Content module provides. Use this if you're looking to improve speed on the site, along with consistency.

## Tests & Linting

This module runs [PHPUnit](https://phpunit.de/) tests and [PHP_CodeSniffer](https://phpcs.de/) linting via the [Drupal Coder](https://www.drupal.org/project/coder) package.

PHPUnit tests can be run with Composer with the `composer test:unit` command. Additional tests can be added with the same `test:` prefix and added to the `composer test` command.

PHPCS linting can be run with Composer with the `composer lint:php` command. The `phpcbf` command can be used to automatically fix linting errors by running `composer lint:phpcbf`. Additional linting (e.g. ESLint) can be added with the same `lint:` prefix and added to the `composer lint` command.
