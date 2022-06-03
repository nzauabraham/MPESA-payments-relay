<?php

namespace App\Services\Emalify\src\API\Abstracts;

abstract class AbstractAPI
{

    /**
     *
     *
     *
     * @author Albert Leitato <albert.leitato@roamtech.com>
     *
     */
    const BASE_URL = 'https://api.emalify.com';

    /**
     * The current API version
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * Cache file location
     *
     * Stores the token to prevent multiple calls to token endpoint
     */
    private $cacheFile = '.token.json';

    /**
     *  Customer ID
     *
     * Emalify Customer id
     * @var string
     */
    private $clientId;

    /**
     * Customer Secret
     * @var string
     */
    private $clientSecret;

    /**
     * ProjectId
     * Project id from emalify dev portal
     */
    private $projectId;

    /**
     * Initiate class with credentials and projectId
     */
    public function __construct($clientId, $clientSecret, $projectId)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->projectId = $projectId;
    }

    /**
     * Get the API URL endpoint
     *
     * @param string $uri URI to append
     * @return string
     */
    protected function buildEndpoint($uri)
    {
        return sprintf('/%s/projects/%s/%s', $this->apiVersion, $this->projectId, $uri);
    }


    private function authenticate()
    {
        if (file_exists(__DIR__."/{$this->cacheFile}")) {
            $contents = file_get_contents(__DIR__."/{$this->cacheFile}");
            $token = json_decode($contents, true);
            if (isset($token['ttl']) && $token['ttl'] > time()) {
                return $token['token'];
            }
        }
        $token = $this->getAuthToken();

        return $token['access_token'];
    }

    private function getAuthToken()
    {
        $credentials =  [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials'
        ];
        $token = $this->makeRequest('POST', "/{$this->apiVersion}/oauth/token", $credentials, false);
        $this->saveCredentials($token);

        return $token;
    }

    private function saveCredentials($token)
    {
        $content = [
            'token' => $token['access_token'],
            'ttl' => time() + ($token['expires_in'] - 120)
        ];
        return file_put_contents(__DIR__."/{$this->cacheFile}", json_encode($content));
    }

    public function makeRequest($method, $path, $body = [], $authorize = true)
    {
        $url = static::BASE_URL.$path;
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        if ($authorize) {
            $headers[] = "Authorization: Bearer ".$this->authenticate();
        }
        $curl = curl_init();
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
            $requestBody = json_encode($body);
            $curlOptions[CURLOPT_POSTFIELDS] = $requestBody;
        }
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($httpCode !== 200) {
            throw new \Exception(
                "Error while connecting to the API endpoind $url
                Body ==> $requestBody
                Response ==> $response
                Headers ==>". json_encode($headers)
            );
        }

        return json_decode($response, true);
    }
}
