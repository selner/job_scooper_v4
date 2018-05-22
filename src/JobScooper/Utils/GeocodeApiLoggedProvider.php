<?php
/**
 * Copyright 2014-18 Bryan Selner
 *
 * Licensed under the Apache License, Version 2.0 (the 'License'); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace JobScooper\Utils;

use function Docopt\get_class_name;
use Geocoder\Exception\NoResultException;
use Geocoder\HttpAdapter\HttpAdapterInterface;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\LocaleAwareProviderInterface;
use Monolog\Logger;

class GeocodeApiLoggedProvider extends AbstractProvider implements LocaleAwareProviderInterface
{
    /**
     * @var string
     */
    const ENDPOINT_URL = '%s/lookup?result_type=place&query=%s';

    /**
     * @var string
     */
    private $region = null;

    /**
     * @var string
     */
    private $apiKey = null;

    /**
     * @var string
     */
    private $server = null;

    private $callCounter = 0;

    private $logger = null;

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @param HttpAdapterInterface $adapter An HTTP adapter.
     * @param string               $locale  A locale (optional).
     * @param string               $region  Region biasing (optional).
     * @param string               $server Whether to use an SSL connection (optional)
     * @param string               $apiKey  Google Geocoding API key (optional)
     * @param \Monolog\Logger      $logger Monolog Logger (optional)
     */
    public function __construct(HttpAdapterInterface $adapter, $locale = null, $region = null, $apiKey = null, $server = null, $logger=null)
    {
        $this->setLogger($logger);
        parent::__construct($adapter, $locale);

        $this->setRegion($region);
        $this->setApiKey($apiKey);
        $this->setServer($server);
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        if (empty($this->region)) {
            return '';
        }
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        if (empty($this->apiKey)) {
            return '';
        }
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        if (empty($this->logger)) {
            $logger = new Logger(get_class_name($this));
            $this->setLogger($logger);
        }
        return $this->logger;
    }

    public function getGeocodedData($address)
    {
        $query = sprintf(self::ENDPOINT_URL, $this->server, rawurlencode($address));

        return $this->executeQuery($query);
    }

    public function getReversedData(array $coordinates)
    {
        // TODO: Implement getReversedData() method.
    }

    public function getName()
    {
        return 'geocodeapi';
    }


    /**
     * @param string $query
     *
     * @return array
     */
    protected function executeQuery($query)
    {
        $this->callCounter += 1;
        $context = array('channel' => 'geocoder', 'numberGeoApiCalls' => $this->callCounter, 'query' => $query, 'call_count_for_run' => $this->callCounter);
        $this->getLogger()->log(\Monolog\Logger::INFO, 'Geocoder called (' . $this->callCounter . ' times)', $context);

        $query = $this->buildQuery($query);

        $content = $this->getAdapter()->getContent($query);

        if (is_empty_value($content)) {
            throw new NoResultException(sprintf('No results returned for query %s', $query));
        }

        $results = array();

        $result = object_to_array($content);
        $results[] = array_merge($this->getDefaults(), $result);

        return $results;
    }

    /**
     * @param string $query
     *
     * @return string Query with extra params
     */
    protected function buildQuery($query)
    {
        if (!empty($this->getRegion())) {
            $query = sprintf('%s&countrycode=%s', $query,  rawurlencode($this->getRegion()));
        }

        if (!empty($this->getApiKey())) {
            $query = sprintf('%s&apikey=%s', $query,  rawurlencode($this->getApiKey()));
        }

        return $query;
    }
}
