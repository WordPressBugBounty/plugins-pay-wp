<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Construction;

use WPPayVendor\Doctrine\Instantiator\Instantiator;
use WPPayVendor\JMS\Serializer\DeserializationContext;
use WPPayVendor\JMS\Serializer\Metadata\ClassMetadata;
use WPPayVendor\JMS\Serializer\Visitor\DeserializationVisitorInterface;
final class UnserializeObjectConstructor implements ObjectConstructorInterface
{
    /** @var Instantiator */
    private $instantiator;
    /**
     * {@inheritdoc}
     */
    public function construct(DeserializationVisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context): ?object
    {
        return $this->getInstantiator()->instantiate($metadata->name);
    }
    private function getInstantiator(): Instantiator
    {
        if (null === $this->instantiator) {
            $this->instantiator = new Instantiator();
        }
        return $this->instantiator;
    }
}
