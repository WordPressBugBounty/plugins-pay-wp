<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse;

use WPPayVendor\JMS\Serializer\Annotation\Type;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
final class TransactionsConfirmations implements SerializableInterface
{
    /**
     * @var TransactionConfirmed
     * @Type("WPPayVendor\BlueMedia\Itn\ValueObject\ItnResponse\TransactionConfirmed")
     */
    private TransactionConfirmed $transactionConfirmed;
    /**
     * @return TransactionConfirmed
     */
    public function getTransactionConfirmed(): TransactionConfirmed
    {
        return $this->transactionConfirmed;
    }
}
