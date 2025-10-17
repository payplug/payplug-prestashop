<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isApplepayAllowedDomainTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [false];

        yield [''];
    }

    public function invalidArrayFormatDataProvider()
    {
        yield [42];

        yield [[]];

        yield [false];

        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $domain
     */
    public function testWhenGivenDomainIsInvalidStringFormat($domain)
    {
        $allowed_domains = [
            'www.website.com',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $domain must be a non empty string',
        ], $this->validator->isApplepayAllowedDomain($domain, $allowed_domains));
    }

    public function testWhenGivenDomainIsInvalidDomainFormat()
    {
        $domain = 'website';
        $allowed_domains = [
            'www.website.com',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $domain must be a valid domain format',
        ], $this->validator->isApplepayAllowedDomain($domain, $allowed_domains));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $allowed_domains
     */
    public function testWhenGivenAllowedDomainsIsInvalidArrayFormat($allowed_domains)
    {
        $domain = 'www.website.com';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $allowed_domains must be a non empty array',
        ], $this->validator->isApplepayAllowedDomain($domain, $allowed_domains));
    }

    public function testWhenGivenDomainIsntInGivenAllowedDomainsArray()
    {
        $domain = 'www.website.com';
        $allowed_domains = [
            'www.website.fr',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Given domain does not match with the list',
        ], $this->validator->isApplepayAllowedDomain($domain, $allowed_domains));
    }

    public function testWhenGivenDomainIsInGivenAllowedDomainsArray()
    {
        $domain = 'www.website.com';
        $allowed_domains = [
            'www.website.com',
        ];
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isApplepayAllowedDomain($domain, $allowed_domains));
    }
}
