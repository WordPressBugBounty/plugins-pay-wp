<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Confirmation\Builder;

use WPPayVendor\BlueMedia\Serializer\Serializer;
use WPPayVendor\BlueMedia\Confirmation\ValueObject\Confirmation;
final class ConfirmationVOBuilder
{
    public static function build(array $data): Confirmation
    {
        return (new Serializer())->fromArray($data, Confirmation::class);
    }
}
