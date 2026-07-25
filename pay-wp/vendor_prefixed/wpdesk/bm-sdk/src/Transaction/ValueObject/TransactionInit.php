<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Transaction\ValueObject;

use WPPayVendor\JMS\Serializer\Annotation\Type;
use WPPayVendor\JMS\Serializer\Annotation\AccessorOrder;
/**
 * @AccessorOrder("custom",
 *     custom = {
 *      "orderID",
 *      "remoteID",
 *      "confirmation",
 *      "reason",
 *      "blikAMKey",
 *      "blikAMLabel",
 *      "paymentStatus",
 *      "hash"
 * })
 */
final class TransactionInit extends Transaction
{
    /**
     * @var string
     * @Type("string")
     */
    private string $confirmation;
    /**
     * @var string
     * @Type("string")
     */
    private string $reason;
    /**
     * @var string
     * @Type("string")
     */
    private string $paymentStatus;
    /**
     * @return string
     */
    public function getConfirmation(): string
    {
        return $this->confirmation;
    }
    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
    /**
     * @return string
     */
    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }
}
