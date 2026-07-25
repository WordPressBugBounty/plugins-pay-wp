<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\ContextFactory;

use WPPayVendor\JMS\Serializer\SerializationContext;
/**
 * Default Serialization Context Factory.
 */
final class DefaultSerializationContextFactory implements SerializationContextFactoryInterface
{
    public function createSerializationContext(): SerializationContext
    {
        return new SerializationContext();
    }
}
