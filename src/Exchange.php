<?php

namespace Fadion\Fixerio;

use Fadion\Fixerio\Exceptions\ResponseException;
use Fadion\Fixerio\Exceptions\ConnectionException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;

class Exchange {

    /**
     * Guzzle client
     * @var GuzzleHttp\Client
     */
    private $guzzle;

    /**
     * URL of fixer.io
     * @var string
     */
    private $url = "api.fixer.io";

    /**
     * Date when an historical query is made
     * @var string
     */
    private $date;

    /**
     * Http or Https
     * @var string
     */
    private $protocol = 'http';

    /**
     * Base currency
     * @var string
     */
    private $base = 'EUR';

    /**
     * List of currencies to return
     * @var array
     */
    private $symbols = [];

    /**
     * @param $guzze Guzzle client
     */
    public function __construct($guzzle = null)
    {
        if (isset($guzzle)) {
            $this->guzzle = $guzzle;
        }
        else {
            $this->guzzle = new GuzzleClient();
        }
    }

    /**
     * Sets the protocol to https
     */
    public function secure()
    {
        $this->protocol = 'https';
    }

    /**
     * Sets the base currency
     * 
     * @param  string $currency
     * @return Fadion\Fixerio\Exchange
     */
    public function base($currency)
    {
        $this->base = $currency;

        return $this;
    }

    /**
     * Sets the currencies to return in either a
     * list of arguments or as an array
     * 
     * @param  array $currencies
     * @return Fadion\Fixerio\Exchange
     */
    public function symbols($currencies = null)
    {
        if (func_num_args() and !is_array(func_get_args()[0])) {
            $currencies = func_get_args();
        }

        $this->symbols = $currencies;

        return $this;
    }

    /**
     * Defines that the api call should be
     * historical, meaning it will return rates
     * for any day since the selected date
     * 
     * @param  string $date
     * @return Fadion\Fixerio\Exchange
     */
    public function historical($date)
    {
        $this->date = date('Y-m-d', strtotime($date));

        return $this;
    }

    /**
     * Makes the request and returns the response
     * with the rates.
     * 
     * @throws ConnectionException if the request is incorrect or times out
     * @throws ResponseException if the response is malformed
     * @return array
     */
    public function get()
    {
        $url = $this->buildUrl($this->url);

        try {
            $response = $this->makeRequest($url);

            return $this->prepareResponse($response);
        }
        // The client needs to know only one exception, no
        // matter what exceptions is thrown by Guzzle
        catch (ConnectException $e) {
            throw new ConnectionException($e->getMessage());
        }
        catch (ClientException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * Forms the correct url from the different parts
     * 
     * @param  string $url
     * @return string
     */
    private function buildUrl($url)
    {
        $url = $this->protocol.'://'.$url.'/';

        if ($this->date) {
            $url .= $this->date;
        }
        else {
            $url .= 'latest';
        }

        $url .= '?base='.$this->base;

        if ($symbols = $this->symbols) {
            $url .= '&symbols='.implode(',', $symbols);
        }

        return $url;
    }

    /**
     * Makes the http request
     * 
     * @param  string $url
     * @return string
     */
    private function makeRequest($url)
    {
        $response = $this->guzzle->request('GET', $url);

        return $response->getBody();
    }

    /**
     * @param  string $body
     * @throws ResponseException if the response is malformed
     * @return array
     */
    private function prepareResponse($body)
    {
        $response = json_decode($body, true);

        if (isset($response['rates']) and is_array($response['rates'])) {
            return $response['rates'];
        }
        else {
            throw new ResponseException('Response body is malformed.');
        }
    }

}