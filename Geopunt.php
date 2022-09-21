<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Geopunt;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

/**
 * @author Jonathan Beliën <jbe@geo6.be>
 */
final class Geopunt extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'https://loc.geopunt.be/v4/Location?q=%s&c=%d';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'https://loc.geopunt.be/v4/Location?latlon=%F,%F&c=%d';

    /**
     * @param ClientInterface $client an HTTP adapter
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        // This API does not support IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Geopunt provider does not support IP addresses, only street addresses.');
        }

        // Save a request if no valid address entered
        if (empty($address)) {
            throw new InvalidArgument('Address cannot be empty.');
        }

        $url = sprintf(self::GEOCODE_ENDPOINT_URL, urlencode($address), $query->getLimit());
        $json = $this->executeQuery($url);

        // no result
        if (empty($json->LocationResult)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->LocationResult as $location) {
            $streetName = !empty($location->Thoroughfarename) ? $location->Thoroughfarename : null;
            $housenumber = !empty($location->Housenumber) ? $location->Housenumber : null;
            $municipality = !empty($location->Municipality) ? $location->Municipality : null;
            $zipcode = !empty($location->Zipcode) ? $location->Zipcode : null;

            $builder = new AddressBuilder($this->getName());
            $builder->setCoordinates($location->Location->Lat_WGS84, $location->Location->Lon_WGS84)
                ->setStreetNumber($housenumber)
                ->setStreetName($streetName)
                ->setLocality($municipality)
                ->setPostalCode($zipcode)
                ->setCountry('België')
                ->setCountryCode('BE')
                ->setBounds(
                    $location->BoundingBox->LowerLeft->Lat_WGS84,
                    $location->BoundingBox->LowerLeft->Lon_WGS84,
                    $location->BoundingBox->UpperRight->Lat_WGS84,
                    $location->BoundingBox->UpperRight->Lon_WGS84
                );

            $results[] = $builder->build();
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $url = sprintf(self::REVERSE_ENDPOINT_URL, $coordinates->getLatitude(), $coordinates->getLongitude(), $query->getLimit());
        $json = $this->executeQuery($url);

        // no result
        if (empty($json->LocationResult)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->LocationResult as $location) {
            $streetName = !empty($location->Thoroughfarename) ? $location->Thoroughfarename : null;
            $housenumber = !empty($location->Housenumber) ? $location->Housenumber : null;
            $municipality = !empty($location->Municipality) ? $location->Municipality : null;
            $zipcode = !empty($location->Zipcode) ? $location->Zipcode : null;

            $builder = new AddressBuilder($this->getName());
            $builder->setCoordinates($location->Location->Lat_WGS84, $location->Location->Lon_WGS84)
                ->setStreetNumber($housenumber)
                ->setStreetName($streetName)
                ->setLocality($municipality)
                ->setPostalCode($zipcode)
                ->setCountry('België')
                ->setCountryCode('BE')
                ->setBounds(
                    $location->BoundingBox->LowerLeft->Lat_WGS84,
                    $location->BoundingBox->LowerLeft->Lon_WGS84,
                    $location->BoundingBox->UpperRight->Lat_WGS84,
                    $location->BoundingBox->UpperRight->Lon_WGS84
                );

            $results[] = $builder->build();
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'geopunt';
    }

    /**
     * @param string $url
     *
     * @return \stdClass
     */
    private function executeQuery(string $url): \stdClass
    {
        $content = $this->getUrlContents($url);
        $json = json_decode($content);
        // API error
        if (!isset($json)) {
            throw InvalidServerResponse::create($url);
        }

        return $json;
    }
}
