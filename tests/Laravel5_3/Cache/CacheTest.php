<?php

namespace Geocoder\Laravel\Tests;

use Geocoder\Laravel\Tests\Laravel5_3\TestCase;
use Geocoder\Provider\GoogleMaps;
use Ivory\HttpAdapter\CurlHttpAdapter;

/**
 * Class CacheTest
 *
 * @package Geocoder\Laravel\Tests
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class CacheTest extends TestCase
{
    public function testCacheForGoogleMapsProvider()
    {
        $adapter = new CurlHttpAdapter();
        $geocoder = new GoogleMaps($adapter);
//        $this->assertEquals()
    }
}
