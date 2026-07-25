<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Transaction\Dto;

use WPPayVendor\BlueMedia\Common\Dto\AbstractDto;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\Transaction\ValueObject\Transaction;
use WPPayVendor\JMS\Serializer\Annotation\Type;
final class TransactionDto extends AbstractDto implements TransactionDtoInterface
{
    /**
     * @var Transaction
     * @Type("WPPayVendor\BlueMedia\Transaction\ValueObject\Transaction")
     */
    private Transaction $transaction;
    /**
     * Language used in html form with redirect to BlueMedia paywall.
     *
     * @var string
     */
    private $htmlFormLanguage = 'pl';
    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
    /**
     * @return string
     */
    public function getHtmlFormLanguage(): string
    {
        return $this->htmlFormLanguage;
    }
    /**
     * @param string $htmlFormLanguage
     * @return TransactionDto
     */
    public function setHtmlFormLanguage(string $htmlFormLanguage): self
    {
        $this->htmlFormLanguage = $htmlFormLanguage;
        return $this;
    }
    public function getRequestData(): SerializableInterface
    {
        return $this->getTransaction();
    }
}
