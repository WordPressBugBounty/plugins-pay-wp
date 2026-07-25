<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Accessor;

use WPPayVendor\JMS\Serializer\DeserializationContext;
use WPPayVendor\JMS\Serializer\Metadata\PropertyMetadata;
use WPPayVendor\JMS\Serializer\SerializationContext;
/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface AccessorStrategyInterface
{
    /**
     * @return mixed
     */
    public function getValue(object $object, PropertyMetadata $metadata, SerializationContext $context);
    /**
     * @param mixed $value
     */
    public function setValue(object $object, $value, PropertyMetadata $metadata, DeserializationContext $context): void;
}
