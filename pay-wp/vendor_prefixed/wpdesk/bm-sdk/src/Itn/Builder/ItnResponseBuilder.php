<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\Builder;

use WPPayVendor\BlueMedia\Configuration;
use WPPayVendor\BlueMedia\Common\Enum\ClientEnum;
use WPPayVendor\BlueMedia\Hash\HashGenerator;
use WPPayVendor\BlueMedia\Itn\ValueObject\Itn;
use WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse\ItnResponse;
use WPPayVendor\BlueMedia\Serializer\Serializer;
final class ItnResponseBuilder
{
    public static function build(Itn $itn, bool $transactionConfirmed, Configuration $configuration): ItnResponse
    {
        $confirmation = $transactionConfirmed ? ClientEnum::STATUS_CONFIRMED : ClientEnum::STATUS_NOT_CONFIRMED;
        $hashData = ['serviceID' => $configuration->getServiceId(), 'orderID' => $itn->getOrderId(), 'confirmation' => $confirmation];
        $itnResponseData = ['serviceID' => $configuration->getServiceId(), 'transactionsConfirmations' => ['transactionConfirmed' => ['orderID' => $itn->getOrderId(), 'confirmation' => $confirmation]], 'hash' => HashGenerator::generateHash($hashData, $configuration)];
        $serializer = new Serializer();
        return $serializer->fromArray($itnResponseData, ItnResponse::class);
    }
}
