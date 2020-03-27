<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

/**
 * Class Api
 *
 * @package LxRmp\Components
 * @author  fseeger
 */
class Api
{
    /**
     * @var string
     */
    const METHOD_GET = 'GET';

    /**
     * @var string
     */
    const METHOD_PUT = 'PUT';

    /**
     * @var string
     */
    const METHOD_POST = 'POST';

    /**
     * @var string
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * @var array|mixed
     */
    protected $validMethods = [
        self::METHOD_POST
    ];

    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var resource
     */
    protected $cURL;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Set cURL configuration
     *
     * @param array $array
     */
    public function setConfiguration(array $array)
    {
        $this->apiUrl = rtrim($array['api_url'], '/');

        $this->cURL = curl_init();
        curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->cURL, CURLOPT_HTTPHEADER, array(
            'User-Agent: Magento 2 RMP Connector',
            'X-AUTH-KEY: ' . $array['api_key'], 'Content-Type: application/json; charset=utf-8',
        ));
    }

    /**
     * Call the api with given data and parameters.
     *
     * @param string $method
     * @param array  $data
     * @param array  $params
     *
     * @return Response
     * @throws \Exception
     */
    protected function call($method = self::METHOD_GET, $data = [], $params = [])
    {
        if (!in_array($method, $this->validMethods)) {
            throw new \Exception('Invalid HTTP-Methode: ' . $method);
        }
        $queryString = '';
        if (!empty($params)) {
            $queryString = '?' . http_build_query($params);
        }
        $url = rtrim($this->apiUrl, '?');
        $url = $url . $queryString;
        $dataString = json_encode($data);
        curl_setopt($this->cURL, CURLOPT_URL, $url);
        curl_setopt($this->cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $dataString);
        $result = curl_exec($this->cURL);

        return $this->prepareResponse($result);
    }

    /**
     * Init a new call via GET-Method.
     *
     * @param array $params
     *
     * @return Response
     * @throws \Exception
     */
    public function get($params = [])
    {
        return $this->call(self::METHOD_GET, [], $params);
    }

    /**
     * Init a new call via POST-Method.
     *
     * @param array $data
     * @param array $params
     *
     * @return Response
     * @throws \Exception
     */
    public function post($data = [], $params = [])
    {
        return $this->call(self::METHOD_POST, $data, $params);
    }

    /**
     * Init a new call via PUT-Method.
     *
     * @param array $data
     * @param array $params
     *
     * @return Response
     * @throws \Exception
     */
    public function put($data = [], $params = [])
    {
        return $this->call(self::METHOD_PUT, $data, $params);
    }

    /**
     * Init a new call via DELETE-Method.
     *
     * @param array $params
     *
     * @return Response
     * @throws \Exception
     */
    public function delete($params = [])
    {
        return $this->call(self::METHOD_DELETE, [], $params);
    }

    /**
     * Return the response from the api-call and implement an event to filter this response.
     *
     * @param $result
     *
     * @return Response
     */
    protected function prepareResponse($result)
    {
        /** @var Response $response */
        $response = $this->newResponse($result);
        return $response;
    }

    /**
     * @param $result
     *
     * @return Response
     */
    protected function newResponse($result)
    {
        $response = $this->responseFactory->create(['jsonString' => $result]);
        return $response;
    }

}
