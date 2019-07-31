<?php

namespace UniversalAnalytics;

class UniversalBeaconPool
{
    const MAXIMUM_REQUEST_QUEUE = 10;
    private $user_agent = 'user_agent';
    private $debug = false;
    private $handler = null;
    private $request_queue = array();

    /**
     * UniversalBeaconPool constructor.
     * @param null $user_agent
     * @param bool $debug
     */
    public function __construct($user_agent = null, $debug = false)
    {
        $this->handler = curl_multi_init();

        if (is_string($user_agent)) {
            $this->user_agent = $user_agent;
        }
        if ($debug) {
            $this->debug = true;
        }
    }

    /**
     * @param array $data
     * @param string $user_agent
     */
    public function addRequest($data, $user_agent = null)
    {
        $user_agent = (is_string($user_agent) ? $user_agent : $this->user_agent);
        $request = new UniversalBeacon($data, $user_agent);
        $handle = $request->handle(null, $this->debug);
        array_push($this->request_queue, $handle);
        curl_multi_add_handle($this->handler, $handle);
        if (count($this->request_queue) >= self::MAXIMUM_REQUEST_QUEUE) {
            self::process($this->handler, $this->request_queue);
        }
    }

    /**
     * @param $handler
     * @param $request_queue
     */
    public static function process(& $handler, & $request_queue)
    {
        do {
            curl_multi_exec($handler, $running);
        } while ($running > 0);
        while ($handle = array_pop($request_queue)) {
            curl_multi_remove_handle($handler, $handle);
        }
    }

    public function __destruct()
    {
        self::process($this->handler, $this->request_queue);
        curl_multi_close($this->handler);
    }
}