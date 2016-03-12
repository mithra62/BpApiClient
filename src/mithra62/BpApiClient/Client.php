<?php
/**
 * Backup Pro - REST Client
 *
 * @copyright	Copyright (c) 2016, mithra62, Eric Lamb.
 * @link		http://backup-pro.com/
 * @version		1.0
 * @filesource 	./mithra62/BpApiClient/Client.php
 */
 
namespace mithra62\BpApiClient;

/**
 * Rest Client Object
 *
 * Simple object to interact with a Backup Pro installation
 *
 * @package BackupPro\View
 * @author Eric Lamb <eric@mithra62.com>
 */
class Client
{
    protected $config = array();
    protected $api_key = null;
    protected $api_secret = null;
    protected $debug_info = null;
    protected $curl = null;
    
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    
    /**
     * Sets it up
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
    }
    
    /**
     * POST to an authenciated API endpoint w/ payload
     *
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    public function post($endpoint, array $payload = array())
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_POST);
    }
    /**
     * GET an authenticated API endpoind w/ payload
     *
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    public function get($endpoint, array $payload = array())
    {
        return $this->fetch($endpoint, $payload);
    }
    /**
     * PUT to an authenciated API endpoint w/ payload
     *
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    public function put($endpoint, array $payload = array())
    {
        return $this->fetch($endpoint, $payload, self::HTTP_METHOD_PUT);
    }
    
    /**
     * Get debug info from the CURL request
     *
     * @return array
     */
    public function getDebugInfo()
    {
        return $this->_debug_info;
    }
    /**
     * Make a CURL request
     *
     * @param string $url
     * @param array $payload
     * @param string $method
     * @param array $headers
     * @param array $curl_options
     * @throws \RuntimeException
     * @return array
     */
    protected function _makeRequest($url, array $payload = array(), $method = 'GET', array $headers = array(), array $curl_options = array())
    {
        $ch = $this->_getCurlHandle();
        $options = array(
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        );
        if (!empty($payload)) {
            if ($options[CURLOPT_CUSTOMREQUEST] == self::HTTP_METHOD_POST || $options[CURLOPT_CUSTOMREQUEST] == self::HTTP_METHOD_PUT) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = json_encode($payload);
                $headers[] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
                $headers[] = 'Content-Type: application/json';
                $options[CURLOPT_HTTPHEADER] = $headers;
            } else {
                $options[CURLOPT_URL] .= '&' . http_build_query($payload, '&');
            }
        }
        if (!empty($curl_options)) {
            $options = array_replace($options, $curl_options);
        }
        if (isset($this->_config['curl_options']) && !empty($this->_config['curl_options'])) {
            $options = array_replace($options, $this->_config['curl_options']);
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $this->_debug_info = curl_getinfo($ch);
        if ($response === false) {
            throw new \RuntimeException('Request Error: ' . curl_error($ch));
        }
        $response = json_decode($response, true);
        if (isset($response['status']) && ($response['status'] < 200 || $response['status'] > 300)) {
            throw new \RuntimeException('Request Error: ' . $response['message'] . '. Raw Response: ' . print_r($response, true));
        }
        return $response;
    }
    protected function _getCurlHandle()
    {
        if (!$this->_curl_handle) {
            $this->_curl_handle = curl_init();
        }
        return $this->_curl_handle;
    }
    public function __destruct()
    {
        if ($this->_curl_handle) {
            curl_close($this->_curl_handle);
        }
    }
}