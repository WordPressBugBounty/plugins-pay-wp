<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\Builder;

use WPPayVendor\BlueMedia\Common\Util\XMLParser;
use WPPayVendor\BlueMedia\Itn\ValueObject\Itn;
use WPPayVendor\BlueMedia\Serializer\Serializer;
final class ItnVOBuilder
{
    public static function build(string $itnData): Itn
    {
        $xmlData = XMLParser::parse($itnData);
        $xmlTransaction = $xmlData->transactions->transaction->asXML();
        return (new Serializer())->deserializeXml($xmlTransaction, Itn::class);
    }
}
