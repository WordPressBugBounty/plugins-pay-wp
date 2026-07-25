<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\Builder;

use WPPayVendor\BlueMedia\Itn\Dto\ItnDto;
use WPPayVendor\BlueMedia\Itn\ValueObject\Itn;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\Serializer\Serializer;
use WPPayVendor\BlueMedia\Common\Util\XMLParser;
final class ItnDtoBuilder
{
    public static function build(string $itnData): SerializableInterface
    {
        $serializer = new Serializer();
        $xmlData = XMLParser::parse($itnData);
        $xmlTransaction = $xmlData->transactions->transaction->asXML();
        $itn = $serializer->deserializeXml($xmlTransaction, Itn::class);
        $itn->setServiceID((string) $xmlData->serviceID);
        $itn->setHash((string) $xmlData->hash);
        $itnDto = new ItnDto();
        $itnDto->setItn($itn);
        return $itnDto;
    }
}
