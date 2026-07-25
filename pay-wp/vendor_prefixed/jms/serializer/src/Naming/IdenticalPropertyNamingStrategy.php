<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Naming;

use WPPayVendor\JMS\Serializer\Metadata\PropertyMetadata;
final class IdenticalPropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadata $property): string
    {
        return $property->name;
    }
}
