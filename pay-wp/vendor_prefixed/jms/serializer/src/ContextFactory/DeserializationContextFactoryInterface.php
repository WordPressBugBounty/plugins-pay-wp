<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\ContextFactory;

use WPPayVendor\JMS\Serializer\DeserializationContext;
/**
 * Deserialization Context Factory Interface.
 */
interface DeserializationContextFactoryInterface
{
    public function createDeserializationContext(): DeserializationContext;
}
