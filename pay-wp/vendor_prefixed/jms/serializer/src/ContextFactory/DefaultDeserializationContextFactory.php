<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\ContextFactory;

use WPPayVendor\JMS\Serializer\DeserializationContext;
/**
 * Default Deserialization Context Factory.
 */
final class DefaultDeserializationContextFactory implements DeserializationContextFactoryInterface
{
    public function createDeserializationContext(): DeserializationContext
    {
        return new DeserializationContext();
    }
}
