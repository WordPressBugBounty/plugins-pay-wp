<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse;

use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\JMS\Serializer\Annotation\AccessorOrder;
use WPPayVendor\JMS\Serializer\Annotation\Type;
/**
 * @AccessorOrder("custom",
 *     custom = {
 *      "orderID",
 *      "confirmation"
 * })
 */
final class TransactionConfirmed implements SerializableInterface
{
    /**
     * @var string
     * @Type("string")
     */
    private string $orderID;
    /**
     * @var string
     * @Type("string")
     */
    private string $confirmation;
    /**
     * @return string
     */
    public function getOrderID(): string
    {
        return $this->orderID;
    }
    /**
     * @return string
     */
    public function getConfirmation(): string
    {
        return $this->confirmation;
    }
}
