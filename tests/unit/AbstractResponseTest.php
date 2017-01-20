<?php
/**
 * Generalization over Omnipay and Payum
 *
 * @link      https://github.com/hiqdev/php-merchant
 * @package   php-merchant
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2017, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\php\merchant\tests\unit\webmoney;

use hiqdev\php\merchant\OmnipayRequest;
use hiqdev\php\merchant\webmoney\OmnipayMerchant;
use hiqdev\php\merchant\webmoney\OmnipayResponse;

/**
 * AbstractResponse test suite.
 */
class AbstractResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OmnipayResponse
     */
    protected $object;

    protected $request;

    protected function setUp()
    {
        $this->object = new OmnipayResponse();
        $this->object->request = new OmnipayRequest();
        $this->object->request->merchant = new OmnipayMerchant();
        $this->object->request->merchant->gateway = 'Stripe';
    }

    protected function tearDown()
    {
    }

    public function testGetFee()
    {
        $this->markTestSkipped('disabled now');
        $this->assertSame(0, $this->object->getFee());
    }
}
