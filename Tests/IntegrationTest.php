<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Geopunt\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\Provider\Geopunt\Geopunt;
use Psr\Http\Client\ClientInterface;

class IntegrationTest extends ProviderIntegrationTest
{
    protected $testAddress = true;

    protected $testReverse = true;

    protected $testIpv4 = false;

    protected $testIpv6 = false;

    protected $skippedTests = [
        'testGeocodeQuery'              => 'Geopunt provider supports Brussels and Flanders (Belgium) only.',
        'testReverseQuery'              => 'Geopunt provider supports Brussels and Flanders (Belgium) only.',
        'testReverseQueryWithNoResults' => 'Geopunt provider supports Brussels and Flanders (Belgium) only.',
    ];

    protected function createProvider(ClientInterface $httpClient)
    {
        return new Geopunt($httpClient);
    }

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    protected function getApiKey()
    {
    }
}
