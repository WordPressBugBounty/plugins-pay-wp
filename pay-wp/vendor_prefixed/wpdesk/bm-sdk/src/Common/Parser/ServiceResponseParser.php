<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Common\Parser;

use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\Serializer\Serializer;
final class ServiceResponseParser extends ResponseParser
{
    public function parseListResponse(string $type): SerializableInterface
    {
        $this->isErrorResponse();
        return (new Serializer())->deserializeXml($this->response, $type);
    }
}
