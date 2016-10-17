<?php

namespace Geocoder\Laravel\Tests;

use Geocoder\Laravel\ProviderAndDumperAggregator;
use Geocoder\Laravel\Providers\GeocoderService;
use Geocoder\Laravel\Tests\Laravel5_3\TestCase;

/**
 * Class CacheTest
 *
 * @package Geocoder\Laravel\Tests
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class CacheTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        app()->register(GeocoderService::class);
    }

    public function testIfCacheIsWorking()
    {
        /** @var ProviderAndDumperAggregator $geocoder */
        $geocoder = app('geocoder')
            ->using('google_maps');

        $geocoder->setCache(app()->make('cache'));
        $address = '1600 Pennsylvania Ave., Washington, DC USA';

        // Disabling cache
        config()->set('geocoder.cache.enabled', false);
        $start = microtime(true);
        $result = $geocoder->geocode($address)->all();
        $end = microtime(true);
        $timeWithoutCache = $end - $start;

        // Enabling cache
        config()->set('geocoder.cache.enabled', true);
        $result = $geocoder->geocode($address)->all(); // Call first time to cache
        $start = microtime(true);
        $result = $geocoder->geocode($address)->all();
        $end = microtime(true);
        $timeWithCache = $end - $start;

        // Ensure that the time with cache is lower than 60% of the time without cache
        $this->assertTrue($timeWithCache < ($timeWithoutCache * 0.6));
    }
}
