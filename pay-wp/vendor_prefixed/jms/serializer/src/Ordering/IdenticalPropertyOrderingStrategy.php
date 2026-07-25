<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Ordering;

final class IdenticalPropertyOrderingStrategy implements PropertyOrderingInterface
{
    /**
     * {@inheritdoc}
     */
    public function order(array $properties): array
    {
        return $properties;
    }
}
