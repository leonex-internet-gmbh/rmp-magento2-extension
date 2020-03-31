<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Model\Logger;

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
     * @var Data
     */
    protected $helper;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var Logger
     */
    protected $rmpLogger;

    public function __construct(Data $helper, ResponseFactory $responseFactory, Logger $rmpLogger)
    {
        $this->helper = $helper;
        $this->responseFactory = $responseFactory;
        $this->rmpLogger = $rmpLogger;
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

        $url = rtrim($this->helper->getApiUrl(), '?/') . $queryString . 'FoOoO';
        $dataString = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Magento 2 RMP Connector',
            'Content-Type: application/json; charset=utf-8',
            'X-AUTH-KEY: ' . $this->helper->getApiKey(),
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $result = curl_exec($ch);

        $error = false === $result ? curl_error($ch) : null;
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        if (!$result || $error || $responseCode >= 300) {
            $this->rmpLogger->error('Error on RMP API call.', [
                'url' => $url,
                'curl_error' => $error,
                'status_code' => $responseCode,
                'request' => $dataString,
                'response' => $result,
            ]);

            if (!$result || $error || $responseCode >= 500 || $responseCode === 404) {
                throw new \Exception('Call to the RMP API failed.');
            }
        } else if ($this->helper->isDebugLoggingEnabled()) {
            $this->rmpLogger->debug('Successful RMP API call.', [
                'status_code' => $responseCode,
                'request' => $dataString,
                'response' => $result,
            ]);
        }

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
