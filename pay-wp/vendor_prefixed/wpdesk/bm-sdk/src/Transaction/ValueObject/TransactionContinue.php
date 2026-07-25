<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Transaction\ValueObject;

use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\JMS\Serializer\Annotation\Type;
use WPPayVendor\JMS\Serializer\Annotation\AccessorOrder;
/**
 * @AccessorOrder("custom",
 *     custom = {
 *      "status",
 *      "redirecturl",
 *      "orderID",
 *      "remoteID",
 *      "hash"
 * })
 */
final class TransactionContinue extends Transaction
{
    /**
     * @var string
     * @Type("string")
     */
    private string $status;
    /**
     * @var string
     * @Type("string")
     */
    private string $redirecturl;
    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirecturl;
    }
    public function toArray(): array
    {
        return ['status' => $this->getStatus(), 'redirecturl' => $this->getRedirectUrl(), 'orderID' => $this->getOrderID(), 'remoteID' => $this->getRemoteID(), 'hash' => $this->getHash()];
    }
}
