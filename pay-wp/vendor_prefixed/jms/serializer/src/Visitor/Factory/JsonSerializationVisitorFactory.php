<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Visitor\Factory;

use WPPayVendor\JMS\Serializer\JsonSerializationVisitor;
use WPPayVendor\JMS\Serializer\Visitor\SerializationVisitorInterface;
/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
final class JsonSerializationVisitorFactory implements SerializationVisitorFactory
{
    /**
     * @var int
     */
    private $options = \JSON_PRESERVE_ZERO_FRACTION;
    public function getVisitor(): SerializationVisitorInterface
    {
        return new JsonSerializationVisitor($this->options);
    }
    public function setOptions(int $options): self
    {
        $this->options = $options;
        return $this;
    }
}
