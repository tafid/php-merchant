<?php

namespace hiqdev\php\merchant\tests\unit\merchants\paypal;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use hiqdev\php\merchant\Invoice;
use hiqdev\php\merchant\merchants\paypal\PayPalExpressMerchant;
use hiqdev\php\merchant\response\RedirectPurchaseResponse;
use hiqdev\php\merchant\tests\unit\merchants\AbstractMerchantTest;
use Money\Currency;
use Money\Money;

class PayPalExpressMerchantTest extends AbstractMerchantTest
{
    /** @var PayPalExpressMerchant */
    protected $merchant;

    protected function buildMerchant()
    {
        return new PayPalExpressMerchant(
            $this->getCredentials(),
            $this->getGatewayFactory(),
            $this->getMoneyFormatter(),
            $this->getMoneyParser()
        );
    }

    public function testCredentialsWereMappedCorrectly()
    {
        $gatewayPropertyReflection = (new \ReflectionObject($this->merchant))->getProperty('gateway');
        $gatewayPropertyReflection->setAccessible(true);
        $gateway = $gatewayPropertyReflection->getValue($this->merchant);

        $this->assertSame($this->getCredentials()->getPurse(), $gateway->getPurse());
        $this->assertSame($this->getCredentials()->getKey1(), $gateway->getSecret());
    }

    public function testRequestPurchase()
    {
        $invoice = $this->buildInvoice();

        $purchaseResponse = $this->merchant->requestPurchase($invoice);
        $this->assertInstanceOf(RedirectPurchaseResponse::class, $purchaseResponse);
        $this->assertSame('https://www.paypal.com/cgi-bin/webscr', $purchaseResponse->getRedirectUrl());
        $this->assertArraySubset([
            "cmd" => "_xclick",
            "bn" => "PP-BuyNowBF:btn_paynowCC_LG.gif:NonHostedGuest",
            "item_name" => $invoice->getDescription(),
            "amount" => "10.99",
            "currency_code" => "USD",
            "business" => $this->merchant->getCredentials()->getPurse(),
            "notify_url" => $invoice->getNotifyUrl(),
            "return" => $invoice->getReturnUrl(),
            "cancel_return" => $invoice->getCancelUrl(),
            "item_number" => $invoice->getId()
        ], $purchaseResponse->getRedirectData());
    }

    /**
     * Used only for testCompletePurchase
     */
    protected function buildHttpClient()
    {
        return new class extends Client
        {
            public function send($requests)
            {
                return new Response(200, [], 'VERIFIED');
            }
        };
    }

    public function testCompletePurchase()
    {
        $_POST = [
            'item_number' => '123',
            'txn_id' => 'tax_num_id',
            'payment_status' => 'Completed',
            'payment_gross' => '10.99',
            'payment_fee' => '0.31',
            'mc_currency' => 'USD',
            'payment_date' => '2017-10-10T00:10:42'
        ];

        $this->merchant = $this->buildMerchant();

        $completePurchaseResponse = $this->merchant->completePurchase([]);

        $this->assertInstanceOf(\hiqdev\php\merchant\response\CompletePurchaseResponse::class, $completePurchaseResponse);
        $this->assertTrue($completePurchaseResponse->getIsSuccessful());
        $this->assertSame('123', $completePurchaseResponse->getTransactionId());
        $this->assertSame('tax_num_id', $completePurchaseResponse->getTransactionReference());
        $this->assertTrue((new Money(1099, new Currency('USD')))->equals($completePurchaseResponse->getAmount()));
        $this->assertTrue((new Money(31, new Currency('USD')))->equals($completePurchaseResponse->getFee()));
        $this->assertSame('USD', $completePurchaseResponse->getCurrency()->getCode());
        $this->assertEquals(new \DateTime('2017-10-10T00:10:42'), $completePurchaseResponse->getTime());
    }
}
