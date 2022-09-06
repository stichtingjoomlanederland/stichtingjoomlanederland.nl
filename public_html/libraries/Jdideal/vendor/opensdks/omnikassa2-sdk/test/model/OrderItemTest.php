<?php namespace nl\rabobank\gict\payments_savings\omnikassa_sdk\model;

use nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\OrderItemBuilder;
use PHPUnit_Framework_TestCase;

class OrderItemTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $orderItem = OrderItemBuilder::makeCompleteOrderItem();

        $this->assertEquals('15', $orderItem->getId());
        $this->assertEquals('Name', $orderItem->getName());
        $this->assertEquals('Description', $orderItem->getDescription());
        $this->assertEquals(1, $orderItem->getQuantity());
        $this->assertEquals(Money::fromCents('EUR', 100), $orderItem->getAmount());
        $this->assertEquals(Money::fromCents('EUR', 50), $orderItem->getTax());
        $this->assertEquals(ProductType::DIGITAL, $orderItem->getCategory());
        $this->assertEquals(VatCategory::LOW, $orderItem->getVatCategory());
    }

    public function testExceptionIsThrownForInvalidProperty() {
        $this->setExpectedException('InvalidArgumentException');

        OrderItem::createFrom(['ID' => 'test']);
    }

    public function testSignature()
    {
        $expectedSignature = [
            '15',
            'Name',
            'Description',
            1,
            array('EUR', 100),
            array('EUR', 50),
            'DIGITAL',
            '2'
        ];
        $orderItem = OrderItemBuilder::makeCompleteOrderItem();
        $actualSignatureData = $orderItem->getSignatureData();

        $this->assertEquals($expectedSignature, $actualSignatureData);
    }

    public function testSignature_withoutOptionalFields()
    {
        $expectedSignature = [
            'Name',
            'Description',
            1,
            array('EUR', 100),
            null,
            'DIGITAL'
        ];
        $orderItem = OrderItemBuilder::makeOrderItemWithoutOptionals();
        $actualSignatureData = $orderItem->getSignatureData();

        $this->assertEquals($expectedSignature, $actualSignatureData);
    }

    public function testJsonSerialize()
    {
        $expectedJson = [
            'id' => '15',
            'name' => 'Name',
            'description' => 'Description',
            'quantity' => 1,
            'amount' => Money::fromCents('EUR', 100),
            'tax' => Money::fromCents('EUR', 50),
            'category' => 'DIGITAL',
            'vatCategory' => '2'
        ];
        $orderItem = OrderItemBuilder::makeCompleteOrderItem();
        $actualJson = $orderItem->jsonSerialize();

        $this->assertEquals($expectedJson, $actualJson);
    }

    public function testJsonSerialize_withoutOptionalFields()
    {
        $expectedJson = [
            'name' => 'Name',
            'description' => 'Description',
            'quantity' => 1,
            'amount' => Money::fromCents('EUR', 100),
            'category' => 'DIGITAL'
        ];
        $orderItem = OrderItemBuilder::makeOrderItemWithoutOptionals();
        $actualJson = $orderItem->jsonSerialize();

        $this->assertEquals($expectedJson, $actualJson);
    }
}
