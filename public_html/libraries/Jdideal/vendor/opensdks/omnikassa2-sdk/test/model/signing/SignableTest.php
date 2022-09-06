<?php namespace nl\rabobank\gict\payments_savings\omnikassa_sdk\test\model\signing;

use nl\rabobank\gict\payments_savings\omnikassa_sdk\model\signing\SigningKey;
use PHPUnit_Framework_TestCase;

class SignableTest extends PHPUnit_Framework_TestCase
{
    public function testCalculateSignatureNullIsEmptyString()
    {
        $signingKey = new SigningKey('testKey');

        $left = new SignableSpy(array(null, 'foo', null, 'bar', null));
        $right = new SignableSpy(array('', 'foo', '', 'bar', ''));

        $this->assertEquals($left->getCalculatedSignature($signingKey), $right->getCalculatedSignature($signingKey));
    }

    public function testCalculateBase64EncodedKey()
    {
        $signingKey = new SigningKey(base64_decode('AHwD9V0BWrG8I39BnWmcQQ=='));

        $left = new SignableSpy(array('', 'foo', '', 'bar', ''));

        $this->assertEquals($left->getCalculatedSignature($signingKey), "de414646b65ac54f045716e5e79e3b5ab2d78db1e5e5bd24e9c2c8e87ad21c2795814d7a0a551b7fc8c3cb75cbcc62ca556078a10f4591ba48025fe9096786ee");
    }

    public function testCalculateBase64EncodedVSTextKey()
    {
        $leftKey = new SigningKey(base64_decode('c2VjcmV0'));
        $left = new SignableSpy(array('', 'foo', '', 'bar', ''));

        $rightKey = new SigningKey('secret');
        $right = new SignableSpy(array('', 'foo', '', 'bar', ''));

        $this->assertEquals($left->getCalculatedSignature($leftKey), $right->getCalculatedSignature($rightKey));
    }

    public function testCalculateSignatureDifferentData()
    {
        $signingKey = new SigningKey('testKey');

        $left = new SignableSpy(array('Foo', 'Bar'));
        $right = new SignableSpy(array('foo', 'bar'));

        $this->assertNotEquals($left->getCalculatedSignature($signingKey), $right->getCalculatedSignature($signingKey));

        $left = new SignableSpy(array('foo ', 'bar '));
        $right = new SignableSpy(array('foo', 'bar'));

        $this->assertNotEquals($left->getCalculatedSignature($signingKey), $right->getCalculatedSignature($signingKey));

        $left = new SignableSpy(array(' foo', ' bar'));
        $right = new SignableSpy(array('foo', 'bar'));

        $this->assertNotEquals($left->getCalculatedSignature($signingKey), $right->getCalculatedSignature($signingKey));

        $left = new SignableSpy(array('bar', 'foo'));
        $right = new SignableSpy(array('foo', 'bar'));

        $this->assertNotEquals($left->getCalculatedSignature($signingKey), $right->getCalculatedSignature($signingKey));
    }

    public function testCalculateSignatureDifferentKey()
    {
        $left = new SignableSpy(array('foo', 'bar'));
        $leftKey = new SigningKey('testKey1');

        $right = new SignableSpy(array('foo', 'bar'));
        $rightKey = new SigningKey('testKey2');

        $this->assertNotEquals($left->getCalculatedSignature($leftKey), $right->getCalculatedSignature($rightKey));
    }
}