<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\GraphNavigator\Factory;

use WPPayVendor\JMS\Serializer\Accessor\AccessorStrategyInterface;
use WPPayVendor\JMS\Serializer\Construction\ObjectConstructorInterface;
use WPPayVendor\JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use WPPayVendor\JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use WPPayVendor\JMS\Serializer\GraphNavigator\DeserializationGraphNavigator;
use WPPayVendor\JMS\Serializer\GraphNavigatorInterface;
use WPPayVendor\JMS\Serializer\Handler\HandlerRegistryInterface;
use WPPayVendor\Metadata\MetadataFactoryInterface;
final class DeserializationGraphNavigatorFactory implements GraphNavigatorFactoryInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;
    /**
     * @var HandlerRegistryInterface
     */
    private $handlerRegistry;
    /**
     * @var ObjectConstructorInterface
     */
    private $objectConstructor;
    /**
     * @var AccessorStrategyInterface
     */
    private $accessor;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var ExpressionEvaluatorInterface
     */
    private $expressionEvaluator;
    public function __construct(MetadataFactoryInterface $metadataFactory, HandlerRegistryInterface $handlerRegistry, ObjectConstructorInterface $objectConstructor, AccessorStrategyInterface $accessor, ?EventDispatcherInterface $dispatcher = null, ?ExpressionEvaluatorInterface $expressionEvaluator = null)
    {
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->objectConstructor = $objectConstructor;
        $this->accessor = $accessor;
        $this->dispatcher = $dispatcher;
        $this->expressionEvaluator = $expressionEvaluator;
    }
    public function getGraphNavigator(): GraphNavigatorInterface
    {
        return new DeserializationGraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->objectConstructor, $this->accessor, $this->dispatcher, $this->expressionEvaluator);
    }
}
