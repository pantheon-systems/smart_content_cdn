<?php

namespace Drupal\Tests\smart_content_cdn\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\smart_content_cdn\EventSubscriber\HeaderEventSubscriber;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Config;

/**
 * Tests that Vary header is being set according to header values.
 *
 * @group smart_content_cdn
 */
class VaryTest extends UnitTestCase {

    /**
     * HeaderEventSuscriber object.
     */
    protected $header_event_subscriber;

    /**
     * Array of ResponseEvent objects keyed by test type.
     */
    protected $events;

    /**
     * Implements setUp().
     */
    protected function setUp(): void {
        parent::setUp();

        // Create container.
        \Drupal::unsetContainer();
        $container = new ContainerBuilder();

        $tags = [];

        // For testing no cache tags.
        $tags['none'] = [];

        // For testing audience tags.
        $tags['audience'] = [
            'smart_content_cdn.geo',
        ];

        // For testing interest tags.
        $tags['interest'] = [
            'smart_content_cdn.interest',
        ];

        // For testing when all tags are present.
        $tags['all'] = [
            'smart_content_cdn.geo',
            'smart_content_cdn.interest',
        ];

        // Create HeaderEventSubscriber object.
        $this->header_event_subscriber = new HeaderEventSubscriber();

        $this->events = [];
        foreach ($tags as $key => $tag_set) {
            // Create Cacheable Metadata object and set cache tags.
            $cacheable_metadata = new CacheableMetadata();
            $cacheable_metadata->setCacheTags($tag_set);

            // Create HtmlResponse object.
            $response = new HtmlResponse();
            $response->addCacheableDependency($cacheable_metadata);

            // Create ResponseEvent object.
            $this->events[$key] = $this->getMockBuilder(ResponseEvent::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getResponse'])
                ->getMock();

            // Override getResponse method.
            $this->events[$key]->method('getResponse')
                ->willReturn($response);
        }

        // Create mock Config object with set_vary config.
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->set('set_vary', TRUE);
        $config->save(TRUE);

        // Create mock Config Factory.
        $config_factory = $this->getMockBuilder(ConfigFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        // Override get method.
        $config_factory->method('get')
            ->willReturn($config);

        // Set Config Factory in container.
        $container->set('config.factory', $config_factory);

        // Set container.
        \Drupal::setContainer($container);
    }

    /**
     * Test that vary header is empty when there are no cache tags.
     */
    public function testVaryHeadersNoTags(): void {
        $key = 'none';

        // Get headers altered by onRespond method.
        $headers = $this->_getAlteredHeaders($key);

        // Assert that very header is empty.
        $this->assertTrue(empty($headers['vary']));
    }

    /**
     * Test that vary header is empty when there are interest cache tags.
     */
    public function testVaryHeadersInterestTag(): void {
        $key = 'interest';

        // Get headers altered by onRespond method.
        $headers = $this->_getAlteredHeaders($key);

        // Assert that vary header has Interest but not Audience.
        $this->assertTrue(!empty($headers['vary']) && !in_array('Audience', $headers['vary']));
        $this->assertTrue(!empty($headers['vary']) && in_array('Interest', $headers['vary']));
    }

    /**
     * Test that vary header is empty when there are audience cache tags.
     */
    public function testVaryHeadersAudienceTag(): void {
        $key = 'audience';

        // Get headers altered by onRespond method.
        $headers = $this->_getAlteredHeaders($key);

        // Assert that very header has Audience but not Interest.
        $this->assertTrue(!empty($headers['vary']) && in_array('Audience', $headers['vary']));
        $this->assertTrue(!empty($headers['vary']) && !in_array('Interest', $headers['vary']));
    }

    /**
     * Test that vary header is empty when there are all cache tags.
     */
    public function testVaryHeadersAllTags(): void {
        $key = 'all';

        // Get headers altered by onRespond method.
        $headers = $this->_getAlteredHeaders($key);

        // Assert that vary header has both Audience and Interest.
        $this->assertTrue(!empty($headers['vary']) && in_array('Audience', $headers['vary']));
        $this->assertTrue(!empty($headers['vary']) && in_array('Interest', $headers['vary']));
    }

    protected function _getAlteredHeaders($key) {
        // Call onResponse method to alter headers.
        $this->header_event_subscriber->onRespond($this->events[$key]);

        // Get headers from response.
        $response = $this->events[$key]->getResponse();
        return $response->headers->all();
    }

    /**
      * Implements tearDown().
      */
    protected function tearDown(): void {
        parent::tearDown();

        unset($this->header_event_subscriber);
        unset($this->events);
    }
}
