<?php

namespace UniversalAnalytics;

class UniversalBeacon
{
    private $endpoint = 'https://www.google-analytics.com/collect';
    private $data = null;
    private $user_agent = null;

    /**
     * Implements CURL POST to Google Analytics
     * @param array $data
     * @param null $user_agent
     */
    public function __construct($data, $user_agent = null)
    {
        $this->data = $data;
        $this->user_agent = $user_agent;
    }

    /**
     * @param array $data_update
     * @param bool $debug
     * @return false|resource
     */
    public function handle($data_update = null, $debug = false)
    {
        $data = array_merge($this->data, (array)$data_update);
        return self::curl($this->endpoint, $data, $this->user_agent, $debug);
    }

    /**
     * Issue an HTTP request via CURL
     * @param string $url
     * @param array $data
     * @param null $ua
     * @param bool $debug
     * @return false|resource
     */
    public static function & curl($url, $data, $ua = null, $debug = true)
    {
        $h = curl_init($url);
        $payload = self::combine($data);
        curl_setopt($h, CURLOPT_AUTOREFERER, true);
        curl_setopt($h, CURLOPT_NOPROGRESS, true);
        if ($debug) {
            print_r($data);
            print_r($payload);
            print "\n"; # readability
        }
        if (is_string($ua)) {
            curl_setopt($h, CURLOPT_USERAGENT, $ua);
        }
        curl_setopt($h, CURLOPT_VERBOSE, $debug);
        curl_setopt($h, CURLOPT_POST, count($data));
        curl_setopt($h, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_HEADER, 0);
        return $h;
    }

    /**
     * A simpler parameter joining method
     * @param array $params
     * @param string $pair
     * @param string $sep
     * @return string
     */
    public static function combine($params, $pair = '=', $sep = '&')
    {
        $c = count($params);
        return $c ? implode($sep, array_map(
            'sprintf', // NOTE: even built-in functions require names given as strings when mapping
            array_fill(0, $c, '%s%s%s'), // format string
            array_keys($params), // keys
            array_fill(0, $c, $pair),  // pairing (=)
            array_map('urlencode', array_values($params)) // values
        )) : '';
    }

}