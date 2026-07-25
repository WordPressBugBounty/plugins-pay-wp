<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\HttpClient;

use WPPayVendor\BlueMedia\Common\Dto\AbstractDto;
use WPPayVendor\GuzzleHttp\Client;
use WPPayVendor\GuzzleHttp\RequestOptions;
use WPPayVendor\Psr\Http\Message\ResponseInterface;
class HttpClient implements HttpClientInterface
{
    private $client;
    public function __construct()
    {
        $this->client = new Client([RequestOptions::ALLOW_REDIRECTS => \false, RequestOptions::HTTP_ERRORS => \false, RequestOptions::VERIFY => \true]);
    }
    /**
     * Perform POST request.
     *
     * @param AbstractDto $requestDto
     *
     * @return ResponseInterface
     */
    public function post(AbstractDto $requestDto): ResponseInterface
    {
        $options = [RequestOptions::HEADERS => $requestDto->getRequest()->getRequestHeaders(), RequestOptions::FORM_PARAMS => $requestDto->getRequestData()->capitalizedArray()];
        return $this->client->post($requestDto->getRequest()->getRequestUrl(), $options);
    }
}
