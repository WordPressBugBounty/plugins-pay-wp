<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\EventDispatcher;

use WPPayVendor\JMS\Serializer\Context;
use WPPayVendor\JMS\Serializer\Type\Type;
/**
 * @phpstan-import-type TypeArray from Type
 */
class ObjectEvent extends Event
{
    /**
     * @var mixed
     */
    private $object;
    /**
     * @param mixed $object
     * @param TypeArray $type
     */
    public function __construct(Context $context, $object, array $type)
    {
        parent::__construct($context, $type);
        $this->object = $object;
    }
    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }
}
