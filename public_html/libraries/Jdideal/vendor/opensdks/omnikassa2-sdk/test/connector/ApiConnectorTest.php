<?php namespace nl\rabobank\gict\payments_savings\omnikassa_sdk\connector;

use DateTime;
use DateTimeZone;
use Exception;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\connector\http\RESTTemplate;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\AccessToken;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\signing\SigningKey;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\connector\ApiConnectorWrapper;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\request\MerchantOrderRequestBuilder;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\response\AnnouncementResponseBuilder;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\response\MerchantOrderResponseBuilder;
use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\response\MerchantOrderStatusResponseBuilder;
use Phake;
use PHPUnit_Framework_TestCase;

class ApiConnectorTest extends PHPUnit_Framework_TestCase
{
    /** @var AccessToken */
    private $accessToken;
    /** @var AccessToken */
    private $expiredAccessToken;
    /** @var AccessToken */
    private $secondAccessToken;
    /** @var string */
    private $refreshToken;
    /** @var ApiConnector */
    private $connector;
    /** @var RESTTemplate */
    private $restTemplate;
    /** @var TokenProvider */
    private $tokenProvider;

    private $signing_key;

    public function setUp()
    {
        $this->signing_key = new SigningKey("secret");

        $this->restTemplate = Phake::mock('nl\rabobank\gict\payments_savings\omnikassa_sdk\connector\http\RESTTemplate');
        $this->tokenProvider = Phake::mock('nl\rabobank\gict\payments_savings\omnikassa_sdk\connector\TokenProvider');
        $this->connector = new ApiConnectorWrapper($this->restTemplate, $this->tokenProvider);

        $utc = new DateTimeZone("UTC");
        $this->accessToken = new AccessToken('accessToken1', new DateTime('+1 day', $utc), 1000);
        $this->expiredAccessToken = new AccessToken('expiredAccessToken', new DateTime('-1 day', $utc), 1000);
        $this->secondAccessToken = new AccessToken('accessToken2', new DateTime('+30 day', $utc), 1000);
        $this->refreshToken = 'refreshToken';
    }

    public function testAnnounceOrder()
    {
        $order = MerchantOrderRequestBuilder::makeCompleteRequest();
        $expectedResponse = MerchantOrderResponseBuilder::newInstanceAsJson();

        $this->prepareTokenProviderWithAccessToken($this->accessToken);
        Phake::when($this->restTemplate)->post('order/server/api/order', $order)->thenReturn($expectedResponse);

        $actualResponse = $this->connector->announceMerchantOrder($order);

        Phake::verify($this->restTemplate)->setToken($this->accessToken->getToken());
        Phake::verify($this->restTemplate)->post('order/server/api/order', $order);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testGetAnnouncementData()
    {
        $announcement = AnnouncementResponseBuilder::newInstance();
        $expectedResponse = $this->makeAnnouncementResponse($announcement->getEventName());

        $this->prepareTokenProviderWithAccessToken($this->accessToken);
        Phake::when($this->restTemplate)->get('order/server/api/events/results/' . $announcement->getEventName())->thenReturn($expectedResponse);

        $actualResponse = $this->connector->getAnnouncementData($announcement);

        Phake::verify($this->restTemplate)->setToken('MyJwt');
        Phake::verify($this->restTemplate)->get('order/server/api/events/results/' . $announcement->getEventName());

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testExpiredTokenResultsInARetryAttemptWithADifferentToken()
    {
        $order = MerchantOrderRequestBuilder::makeCompleteRequest();

        $this->prepareTokenProviderWithAccessToken($this->expiredAccessToken);
        Phake::when($this->restTemplate)->get('gatekeeper/refresh')->thenReturn(json_encode($this->secondAccessToken));

        $this->connector->announceMerchantOrder($order);

        //Verify that a new access token is retrieved
        Phake::verify($this->restTemplate)->get('gatekeeper/refresh');

        //Verify that the correct token is used to call the API
        Phake::verify($this->restTemplate, Phake::never())->setToken($this->expiredAccessToken->getToken());
        Phake::verify($this->restTemplate)->setToken($this->secondAccessToken->getToken());

        //Verify that the new access token is stored in the token provider
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN, $this->secondAccessToken->getToken());
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN_VALID_UNTIL, $this->secondAccessToken->getValidUntil()->format(DateTime::ISO8601));
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN_DURATION, $this->secondAccessToken->getDurationInMillis());
    }

    public function testNoAccessTokenProvided()
    {
        $order = MerchantOrderRequestBuilder::makeCompleteRequest();

        $this->prepareTokenProviderWithoutAccessToken();
        Phake::when($this->restTemplate)->get('gatekeeper/refresh')->thenReturn(json_encode($this->secondAccessToken));

        $this->connector->announceMerchantOrder($order);

        //Verify that a new access token is retrieved
        Phake::verify($this->restTemplate)->get('gatekeeper/refresh');

        //Verify that the correct token is used to call the API
        Phake::verify($this->restTemplate)->setToken($this->secondAccessToken->getToken());

        //Verify that the new access token is stored in the token provider
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN, $this->secondAccessToken->getToken());
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN_VALID_UNTIL, $this->secondAccessToken->getValidUntil()->format(DateTime::ISO8601));
        Phake::verify($this->tokenProvider)->setValue(TokenProvider::ACCESS_TOKEN_DURATION, $this->secondAccessToken->getDurationInMillis());
    }

    private function prepareTokenProviderWithoutAccessToken()
    {
        $this->prepareTokenProviderWithAccessToken(null);
    }

    /**
     * @param AccessToken $accessToken
     */
    private function prepareTokenProviderWithAccessToken($accessToken)
    {
        $token = null;
        $validUntil = null;
        $durationInMillis = null;

        if (!empty($accessToken)) {
            $token = $accessToken->getToken();
            $validUntil = $accessToken->getValidUntil()->format(DateTime::ISO8601);
            $durationInMillis = $accessToken->getDurationInMillis();
        }

        Phake::when($this->tokenProvider)->getValue(TokenProvider::ACCESS_TOKEN)->thenReturn($token);
        Phake::when($this->tokenProvider)->getValue(TokenProvider::ACCESS_TOKEN_VALID_UNTIL)->thenReturn($validUntil);
        Phake::when($this->tokenProvider)->getValue(TokenProvider::ACCESS_TOKEN_DURATION)->thenReturn($durationInMillis);
    }

    /**
     * @param string $eventName
     * @return string
     * @throws Exception
     */
    private function makeAnnouncementResponse($eventName)
    {
        if ($eventName == 'merchant.order.status.changed') {
            return MerchantOrderStatusResponseBuilder::newInstanceAsJson();
        }
        throw new Exception('Unknown announcement type');
    }
}