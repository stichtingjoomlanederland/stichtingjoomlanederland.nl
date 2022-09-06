<?php namespace nl\rabobank\gict\payments_savings\omnikassa_sdk\model\response;

/**
 * This response contains partial or all order results.
 * By using the $moreOrderResultsAvailable boolean you can check if more order results can be retrieved.
 */
class MerchantOrderStatusResponse extends Response
{
    /** @var bool */
    protected $moreOrderResultsAvailable;
    /** @var MerchantOrderResult[] */
    protected $orderResults;

    /**
     * @return boolean
     */
    public function isMoreOrderResultsAvailable()
    {
        return $this->moreOrderResultsAvailable;
    }

    /**
     * @param boolean $moreOrderResultsAvailable
     */
    public function setMoreOrderResultsAvailable($moreOrderResultsAvailable)
    {
        $this->moreOrderResultsAvailable = $moreOrderResultsAvailable;
    }

    /**
     * @return MerchantOrderResult[]
     */
    public function getOrderResults()
    {
        return $this->orderResults;
    }

    /**
     * @param MerchantOrderResult[] $orderResults
     */
    public function setOrderResults($orderResults)
    {
        $this->orderResults = $orderResults;
    }

    /**
     * @return array
     */
    public function getSignatureData()
    {
        return array(
            ($this->moreOrderResultsAvailable ? 'true' : 'false'),
            $this->getOrderResultsSignatureData()
        );
    }

    private function getOrderResultsSignatureData()
    {
        $signatureData = array();
        foreach ($this->orderResults as $orderResult) {
            $signatureData[] = $orderResult->getSignatureData();
        }
        return $signatureData;
    }
}