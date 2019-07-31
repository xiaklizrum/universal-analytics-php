<?php

namespace UniversalAnalytics;

defined('ANALYTICS_HASH_IDS') || define('ANALYTICS_HASH_IDS', false);

class Tracker
{
    const VERSION = 1;
    const USER_AGENT = 'Analytics Pros - Universal Analytics (PHP)';
    private $account = null;
    private $state = null;
    private $user_agent = null;
    private $pool = null;
    public $debug = false;

    public static $name_map = array(
        'clientId' => 'cid',
        'userId' => 'uid',
        'eventCategory' => 'ec',
        'eventAction' => 'ea',
        'eventLabel' => 'el',
        'eventValue' => 'ev',
        'nonInteraction' => 'ni',
        'nonInteractive' => 'ni',
        'documentPath' => 'dp',
        'documentTitle' => 'dt',
        'title' => 'dt',
        'path' => 'dp',
        'page' => 'dp',
        'location' => 'dl',
        'documentLocation' => 'dl',
        'hostname' => 'dh',
        'documentHostname' => 'dh',
        'sessionControl' => 'sc',
        'referrer' => 'dr',
        'documentReferrer' => 'dr',
        'queueTime' => 'qt',
        'campaignName' => 'cn',
        'campaignSource' => 'cs',
        'campaignMedium' => 'cm',
        'campaignKeyword' => 'ck',
        'campaignContent' => 'cc',
        'campaignId' => 'ci',
        'screenResolution' => 'sr',
        'viewportSize' => 'vp',
        'documentEncoding' => 'de',
        'screenColors' => 'sd',
        'userLanguage' => 'ul',
        'appName' => 'an',
        'contentDescription' => 'cd',
        'appVersion' => 'av',
        'transactionAffiliation' => 'ta',
        'transactionId' => 'ti',
        'transactionRevenue' => 'tr',
        'transactionShipping' => 'ts',
        'transactionTax' => 'tt',
        'transactionCurrency' => 'cu',
        'itemName' => 'in',
        'itemPrice' => 'ip',
        'itemQuantity' => 'iq',
        'itemCode' => 'ic',
        'itemVariation' => 'iv',
        'itemCategory' => 'iv',
        'socialAction' => 'sa',
        'socialNetwork' => 'sn',
        'socialTarget' => 'st',
        'exceptionDescription' => 'exd',
        'exceptionFatal' => 'exf',
        'timingCategory' => 'utc',
        'timingVariable' => 'utv',
        'timingTime' => 'utt',
        'timingLabel' => 'utl',
        'timingDNS' => 'dns',
        'timingPageLoad' => 'pdt',
        'timingRedirect' => 'rrt',
        'timingTCPConnect' => 'tcp',
        'timingServerResponse' => 'srt'
    );

    public static $name_map_re = array(
        '@^dimension([0-9]+)$@' => 'cd$1',
        '@^metric([0-9]+)$@' => 'cm$1'
    );

    /**
     * Analytics Pros - Universal Analytics (PHP)
     * @param $account
     * @param null $client_id
     * @param null $user_id
     * @param bool $debug
     */
    public function __construct($account, $client_id = null, $user_id = null, $debug = false)
    {
        $this->account = $account;
        $this->debug = (bool)$debug;
        $this->pool = new UniversalBeaconPool(self::USER_AGENT, $this->debug);

        if (!is_null($client_id) && constant('ANALYTICS_HASH_IDS'))
            $client_id = self::hash_uuid($client_id);
        elseif (is_null($client_id))
            $client_id = self::generateUUID4();

        $this->state = array(
            'v' => self::VERSION,
            'tid' => $account,
            'cid' => $client_id
        );

        if (!is_null($user_id)) {
            if (constant('ANALYTICS_HASH_IDS'))
                $user_id = self::hash_uuid($user_id);
            $this->state['uid'] = $user_id;
        }
    }

    /* Return an MD5 checksum spaced in UUD4-format */
    public static function hash_uuid($value)
    {
        $checksum = md5($value);
        return sprintf('%8s-%4s-%4s-%4s-%12s',
            substr($checksum, 0, 8),
            substr($checksum, 8, 4),
            substr($checksum, 12, 4),
            substr($checksum, 16, 4),
            substr($checksum, 20, 12)
        );
    }

    /**
     * @param string $ua
     */
    public function setUserAgent($ua)
    {
        if (is_string($ua)) {
            $this->user_agent = $ua;
        }
    }

    public function send($hit_type, $attribs = null, $ua = null)
    {
        $agent = (is_string($ua)
            ? $ua
            : (is_string($this->user_agent)
                ? $this->user_agent
                : self::USER_AGENT
            )
        );
        $this->pool->addRequest($this->hitdata($hit_type, $attribs), $agent);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->state[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->state)) {
            return $this->state[$name];
        } else {
            return null;
        }
    }

    /**
     * @param $type
     * @param null $attribs
     * @return array
     */
    public function hitdata($type, $attribs = null)
    {
        return self::params($type, array_merge($this->state, (array)$attribs));
    }

    /**
     * @param $type
     * @param $data
     * @return array
     */
    public static function & params($type, $data)
    {
        $result_data = array();
        $result_keys_in = array_keys($data);
        $result_keys = str_replace(array_keys(self::$name_map), array_values(self::$name_map), $result_keys_in);
        $result_keys = preg_replace(array_keys(self::$name_map_re), array_values(self::$name_map_re), $result_keys);
        for ($i = 0; $i < count($result_keys_in); $i++) {
            $result_data[$result_keys[$i]] = $data[$result_keys_in[$i]];
        }
        $result_data['t'] = $type;
        return $result_data;
    }

    /**
     * @return string
     */
    public static function generateUUID4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
