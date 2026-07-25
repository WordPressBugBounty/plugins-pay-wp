<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Transaction\Parser;

use WPPayVendor\BlueMedia\Common\Enum\ClientEnum;
use WPPayVendor\BlueMedia\Common\Exception\HashException;
use WPPayVendor\BlueMedia\Common\Parser\ResponseParser;
use WPPayVendor\BlueMedia\Common\Util\XMLParser;
use WPPayVendor\BlueMedia\Hash\HashChecker;
use WPPayVendor\BlueMedia\HttpClient\ValueObject\Response;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\Serializer\Serializer;
use WPPayVendor\BlueMedia\Transaction\ValueObject\TransactionBackground;
use WPPayVendor\BlueMedia\Transaction\ValueObject\TransactionContinue;
use WPPayVendor\BlueMedia\Transaction\ValueObject\TransactionInit;
final class TransactionResponseParser extends ResponseParser
{
    public function parse(bool $transactionInit = \false): Response
    {
        $this->isErrorResponse();
        $paywayForm = $this->getPaywayFormResponse();
        if (!empty($paywayForm)) {
            return new Response(htmlspecialchars_decode($paywayForm['1']['0']));
        }
        if ($transactionInit === \true) {
            return new Response($this->parseTransactionInitResponse());
        }
        return new Response($this->parseTransactionBackgroundResponse());
    }
    private function getPaywayFormResponse(): array
    {
        $matchesCount = preg_match_all(ClientEnum::PATTERN_PAYWAY, $this->response, $data);
        return $matchesCount === 0 ? [] : $data;
    }
    private function parseTransactionBackgroundResponse(): SerializableInterface
    {
        /** @var TransactionBackground $transaction */
        $transaction = (new Serializer())->deserializeXml($this->response, TransactionBackground::class);
        if (HashChecker::checkHash($transaction, $this->configuration) === \false) {
            throw HashException::wrongHashError();
        }
        return $transaction;
    }
    private function parseTransactionInitResponse(): SerializableInterface
    {
        $xmlTransaction = XMLParser::parse($this->response);
        if (isset($xmlTransaction->redirecturl)) {
            /** @var TransactionContinue $transaction */
            $transaction = (new Serializer())->deserializeXml($this->response, TransactionContinue::class);
        } else {
            /** @var TransactionInit $transaction */
            $transaction = (new Serializer())->deserializeXml($this->response, TransactionInit::class);
        }
        if (HashChecker::checkHash($transaction, $this->configuration) === \false) {
            throw HashException::wrongHashError();
        }
        return $transaction;
    }
}
