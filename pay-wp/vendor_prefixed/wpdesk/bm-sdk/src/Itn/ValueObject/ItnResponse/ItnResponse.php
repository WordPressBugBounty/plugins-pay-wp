<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse;

use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\Serializer\Serializer;
use WPPayVendor\JMS\Serializer\Annotation\AccessorOrder;
use WPPayVendor\JMS\Serializer\Annotation\XmlRoot;
use WPPayVendor\JMS\Serializer\Annotation\Type;
/**
 * @XmlRoot("confirmationList")
 *
 * @AccessorOrder("custom",
 *     custom = {
 *      "serviceID",
 *      "transactionsConfirmations",
 *      "hash"
 * })
 */
class ItnResponse implements SerializableInterface
{
    /**
     * @var string
     * @Type("string")
     */
    private string $serviceID;
    /**
     * @var TransactionsConfirmations
     * @Type("WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse\TransactionsConfirmations")
     */
    private TransactionsConfirmations $transactionsConfirmations;
    /**
     * @var string
     * @Type("string")
     */
    private string $hash;
    /**
     * @return string
     */
    public function getServiceID(): string
    {
        return $this->serviceID;
    }
    /**
     * @return TransactionsConfirmations
     */
    public function getTransactionsConfirmations(): TransactionsConfirmations
    {
        return $this->transactionsConfirmations;
    }
    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }
    public function toXml(): string
    {
        return (new Serializer())->toXml($this);
    }
}
