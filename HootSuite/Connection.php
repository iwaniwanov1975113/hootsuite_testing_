<?php
/**
 * This file holds the main HootSuite Api connection class.
 *
 * @author Aaron Saray
 */

namespace HootSuite;
use HootSuite\Options\OptionsAbstract;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class Connection
 * @package HootSuite
 */
class Connection
{
    /**
     * @var boolean use this to indicate that you'd like debug mode on
     */
    const ENABLE_DEBUG = true;

    /**
     * @var string the mad mimi api
     */
    const API_URL = 'https://platform.hootsuite.com';

    /**
     * @var string the api authentication has failed
     */
    const API_AUTHENTICATION_FAILED = "Authentication failed";

    /**
     * @var string the username (your email) used for the connection
     */
    protected $username;

    /**
     * @var string the api key for the connection
     */
    protected $apiKey;

    /**
     * @var CurlRequest
     */
    protected $curlRequest;

    /**
     * @var bool whether debugging logging should be on or not
     */
    protected $debugMode = false;

    /**
     * @var Logger
     */
    private $log;

    /**
     * Connection constructor - sets up the potential for hte connection
     * @param $username string The email that is used to connect
     * @param $apiKey string the API key that is used
     * @param $curlRequest CurlRequest a curl request
     * @param $debugMode bool whether to turn on debugging
     */
    public function __construct($token, CurlRequest $curlRequest, $debugMode = false)
    {
        $this->apiKey = $token;
        $this->curlRequest = $curlRequest;
        $this->debugMode = $debugMode;
    }

    /**
     * Sends the request
     *
     * @param OptionsAbstract $options options for this send
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @return string the unique ID that was sent back
     */
    public function request(OptionsAbstract $options)
    {
        $endPoint = $options->getEndPoint();
        $requestType = $options->getRequestType();
        $this->debug("About to send to {$endPoint} via {$requestType} with options of " . get_class($options));
        

        // $query = http_build_query($options->getPopulated());
        $query = json_encode($options->getPopulated());
        $this->debug("Query: {$query}");

        $url = self::API_URL . $endPoint;

        if ($requestType == OptionsAbstract::REQUEST_TYPE_GET && !! $query) {
            $url .= "?{$query}";
        }
        $this->debug("Url: {$url}");

        $header = ["Authorization: Bearer {$this->apiKey}"];

        // $this->debug("header: {$header}");

        $this->curlRequest->setOption(CURLOPT_URL, $url);
        $this->curlRequest->setOption(CURLOPT_RETURNTRANSFER, true);
        // $this->curlRequest->setOption(CURLOPT_HEADER, 1);
        $this->curlRequest->setOption(CURLOPT_HTTPHEADER, $header);

        if ($requestType != OptionsAbstract::REQUEST_TYPE_GET) {
            $this->curlRequest->setOption(CURLOPT_POSTFIELDS, $query);
            if ($requestType == OptionsAbstract::REQUEST_TYPE_POST) {
                $this->curlRequest->setOption(CURLOPT_POST, true);
            }
            else {
                $this->curlRequest->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($requestType));
            }
        }

        $result = $this->curlRequest->execute();
        $this->debug("Curl info after call: " . print_r($this->curlRequest->getInfo(), true));
        $this->debug("Body content: " . print_r($result, true));

        $this->handleSendError($result);

        if (($httpCode = $this->curlRequest->getInfo(CURLINFO_HTTP_CODE)) !== 200) {
            throw new \Exception("HTTP Error Code of {$httpCode} was generated and not caught: " . $result); // really shouldn't ever happen if I do my job right
        }

        $this->debug('Successful call with result: ' . $result);

        return $result;
    }

    protected function handleSendError($result)
    {
        /**
         * Curl error
         */
        if ($result === false) {
            throw new \Exception($this->curlRequest->getError(), $this->curlRequest->getErrorNumber());
        }

        /**
         * Authentication failure
         */
        if ($result == self::API_AUTHENTICATION_FAILED) {
            throw new \Exception("Authentication failed: " . $result);
        }

        /**
         * HTTP Error Codes
         */

        $this->debug($this->curlRequest->getInfo(CURLINFO_HTTP_CODE));
        switch ($this->curlRequest->getInfo(CURLINFO_HTTP_CODE)) {
            case 200:
                if (stripos($result, '{') === 0) {
                    $json = json_decode($result);
                    if (isset($json->error)) {
                        throw new \Exception($json->error, $json->error_description);
                    }
                }
                break;

            case 404:
                if(stripos($result, '{') === 0) {
                    $json = json_decode($result);
                    foreach ($json->errors as $error) {
                        throw new \Exception($error->message, $error->code);
                    }
                } else
                    throw new \Exception("Requested resource does not exist: {$result}", 404);

                break;

            case 403:
                if (stripos($result, 'Access denied') === 0) {
                    throw new \Exception($result, 403);
                }
                break;

            case 500:
                // @todo figure out if this actually works
                if ($this->curlRequest->getInfo(CURLINFO_CONTENT_TYPE) == 'text/html; charset=utf-8') {
                    throw new \Exception("An error 500 was generated and an HTML page was returned.", 500);
                }
                else {
                    throw new \Exception("Unexpected error occurred on the server.", 500);
                }
                break;

            case 429:
                throw new \Exception("Rate Limit Exceeded. Please contact dev.support@hootsuite.com for assistance: {$result}", 409);
                break;

            case 400:
                $json = json_decode($result);
                foreach ($json->errors as $error) {
                    throw new \Exception($error->message, $error->code);
                }

                break;

            case 401:
                throw new \Exception("Missing or invalid authentication: {$result}", 422);
                break;
        }
    }

    public function uploadMedia($uploadUrl, $fileName, $mimeType, $sizeBytes)
    {
        $header = [
            "Content-Type: {$mimeType}", 
            "Content-Length: {$sizeBytes}",
            // "Authorization: Bearer {$this->apiKey}"
        ];
        $this->curlRequest->setOption(CURLOPT_HTTPHEADER, $header);
        $this->curlRequest->setOption(CURLOPT_URL, $uploadUrl);
        $this->curlRequest->setOption(CURLOPT_RETURNTRANSFER, true);
        
        $requestType = "PUT";

        $this->curlRequest->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($requestType));

        $this->debug("sizeBytes: ".$sizeBytes);
        if (function_exists('curl_file_create')) { // php 5.5+
            $cFile = curl_file_create($fileName);
        } else { // 
            $cFile = '@' . realpath($fileName);
        }
        $post = array("file" => $cFile);

        $this->curlRequest->setOption(CURLOPT_POST, false);
        $this->curlRequest->setOption(CURLOPT_POSTFIELDS, $post);
        
        // $result = "";
        $result = $this->curlRequest->execute();

        $this->debug("Curl info after call: " . print_r($this->curlRequest->getInfo(), true));
        $this->debug("Body content: " . print_r($result, true));


        if (($httpCode = $this->curlRequest->getInfo(CURLINFO_HTTP_CODE)) !== 200) {
            throw new \Exception("HTTP Error Code of {$httpCode} was generated and not caught: " . $result); 
        }

        $this->debug('Successful call with result: ' . $result);
        return $result;
    }

    /**
     * This is a shortcut to debugging with the log - tries to limit the calculations done if debug mode is false
     *
     * @param $string string the debug string
     */
    protected function debug($string)
    {
        if ($this->debugMode) echo $string."<br />";
    }
}