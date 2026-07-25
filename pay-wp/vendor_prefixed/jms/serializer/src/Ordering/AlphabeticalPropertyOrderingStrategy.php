<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Ordering;

use WPPayVendor\JMS\Serializer\Metadata\PropertyMetadata;
final class AlphabeticalPropertyOrderingStrategy implements PropertyOrderingInterface
{
    /**
     * {@inheritdoc}
     */
    public function order(array $properties): array
    {
        uasort($properties, static fn(PropertyMetadata $a, PropertyMetadata $b): int => strcmp($a->name, $b->name));
        return $properties;
    }
}
