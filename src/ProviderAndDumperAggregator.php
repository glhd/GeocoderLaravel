<?php namespace Geocoder\Laravel;

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

use Doctrine\Common\Cache\Cache;
use Geocoder\Provider\Provider;
use Geocoder\ProviderAggregator;
use Geocoder\Geocoder;
use Geocoder\Dumper\Gpx;
use Geocoder\Dumper\Kml;
use Geocoder\Dumper\Wkb;
use Geocoder\Dumper\Wkt;
use Geocoder\Dumper\GeoJson;
use Geocoder\Laravel\Exceptions\InvalidDumperException;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;

/**
 * @author Mike Bronner <hello@genealabs.com>
 */
class ProviderAndDumperAggregator extends ProviderAggregator implements Geocoder
{
    const CACHE_NAMESPACE = 'geocoder.';

    /**
     * @var AddressCollection
     */
    protected $results;

    /**
     * @var CacheManager
     */
    protected $cache;

    /**
     * @param int $limit
     */
    public function __construct($limit = Provider::MAX_RESULTS, CacheManager $cache = null)
    {
        $this->cache = $cache;
        parent::__construct($limit);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->results->all();
    }

    /**
     * @param string
     * @return Collection
     */
    public function dump($dumper)
    {
        $dumperClasses = collect([
            'geojson' => GeoJson::class,
            'gpx' => Gpx::class,
            'kml' => Kml::class,
            'wkb' => Wkb::class,
            'wkt' => Wkt::class,
        ]);

        if (!$dumperClasses->has($dumper)) {
            $errorMessage = implode('', [
                "The dumper specified ('{$dumper}') is invalid. Valid dumpers ",
                "are: geojson, gpx, kml, wkb, wkt.",
            ]);
            throw new InvalidDumperException($errorMessage);
        }

        $dumperClass = $dumperClasses->get($dumper);
        $dumper = new $dumperClass;
        $results = collect($this->results->all());

        return $results->map(function ($result) use ($dumper) {
            return $dumper->dump($result);
        });
    }

    /**
     * @param string
     * @return ProviderAndDumperAggregator
     */
    public function geocode($value)
    {
        if ($cachedResults = $this->fetchFromCache($value)) {
            $this->results = $cachedResults;
        } else {
            $this->results = parent::geocode($value);
            $this->storeInCache($value, $this->results);
        }

        return $this;
    }

    /**
     * @return ProviderAndDumperAggregator
     */
    public function get()
    {
        return $this->results;
    }

    /**
     * @param float
     * @param float
     * @return ProviderAndDumperAggregator
     */
    public function reverse($latitude, $longitude)
    {
        if ($cachedResults = $this->fetchFromCache("$latitude,$longitude")) {
            return $cachedResults;
        }

        $this->results = parent::reverse($latitude, $longitude);
        $this->storeInCache("$latitude,$longitude", $this->results);

        return $this;
    }

    /**
     * @param string $cacheKey
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function fetchFromCache($value)
    {
        if ($this->cacheEnabled()) {
            return $this->cache->get($this->getCacheKey($value));
        }
    }

    /**
     * @param string $value
     * @param mixed $results
     */
    protected function storeInCache($value, $results)
    {
        if ($this->cacheEnabled()) {
            $this->cache->put($this->getCacheKey($value), $results, $this->getCacheTimeout());
        }
    }

    public function clearCache()
    {
        $this->cache->forget(static::CACHE_NAMESPACE . '*');
    }

    /**
     * @param $value
     * @return string
     */
    protected function getCacheKey($value)
    {
        return static::CACHE_NAMESPACE . sha1($value);
    }

    /**
     * @return CacheManager
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param CacheManager $cache
     * @return $this
     */
    public function setCache(CacheManager $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return bool
     */
    protected function cacheEnabled()
    {
        return config('geocoder.cache.enabled') == true;
    }

    /**
     * @return int
     */
    protected function getCacheTimeout()
    {
        return (int)config('geocoder.cache.timeout');
    }
}
