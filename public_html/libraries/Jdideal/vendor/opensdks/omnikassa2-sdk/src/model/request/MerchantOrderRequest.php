<?php namespace nl\rabobank\gict\payments_savings\omnikassa_sdk\model\request;

use DateTime;
use JsonSerializable;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\signing\SignedRequest;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\signing\SigningKey;

/**
 * Envelope for the MerchantOrder.
 */
class MerchantOrderRequest extends SignedRequest implements JsonSerializable
{
    /** @var MerchantOrder */
    private $merchantOrder;
    /** @var DateTime */
    private $timestamp;

    /**
     * @param MerchantOrder $merchantOrder
     * @param SigningKey $signingKey
     */
    public function __construct(MerchantOrder $merchantOrder, SigningKey $signingKey)
    {
        $this->merchantOrder = $merchantOrder;
        $this->timestamp = new DateTime('now');
        $this->calculateAndSetSignature($signingKey);
    }

    /**
     * @return array
     */
    function getSignatureData()
    {
        $signatureData[] = $this->getFormattedTimestamp();
        $signatureData[] = $this->merchantOrder->getSignatureData();

        return $signatureData;
    }

    /**
     * @return string
     */
    function jsonSerialize()
    {
        $json['timestamp'] = $this->getFormattedTimestamp();
        foreach ($this->merchantOrder->jsonSerialize() as $key => $value) {
            if ($value != null) {
                $json[$key] = $value;
            }
        }
        $json['signature'] = $this->getSignature();

        return $json;
    }

    /**
     * @return string
     */
    private function getFormattedTimestamp()
    {
        return $this->timestamp->format(DateTime::ATOM);
    }

    /**
     * This method should only be called from the tests
     * @param DateTime $timestamp
     */
    function setTimestamp(DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }
}