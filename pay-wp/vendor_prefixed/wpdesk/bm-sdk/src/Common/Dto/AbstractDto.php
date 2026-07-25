<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Common\Dto;

use WPPayVendor\BlueMedia\HttpClient\ValueObject\Request;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\JMS\Serializer\Annotation\Type;
abstract class AbstractDto
{
    /**
     * @var string
     * @Type("string");
     */
    protected string $gatewayUrl;
    /**
     * @var Request
     * @Type("WPPayVendor\BlueMedia\HttpClient\ValueObject\Request");
     */
    protected $request;
    /**
     * @return string
     */
    public function getGatewayUrl(): string
    {
        return $this->gatewayUrl;
    }
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }
    public function getRequest(): ?Request
    {
        return $this->request;
    }
    abstract public function getRequestData(): SerializableInterface;
}
