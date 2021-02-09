<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\ContextMock;
use PayPlug\src\repositories\OneyRepository;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group test
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckOneyRequiredFieldsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $cache;
    protected $config;
    protected $logger;
    protected $myLogPhp;
    protected $tools;
    protected $oney;

    protected $repo;

    protected $arrayCache;
    protected $arrayLogger;

    public function setUp()
    {
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->logger = MockHelper::createMockFactory('PayPlug\src\repositories\LoggerRepository');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');
        $this->tools = MockHelper::createMockFactory('Payplug\src\specific\ToolsSpecific');
        $this->oney = MockHelper::createMockFactory('Payplug\OneySimulation');

        $this->repo = new OneyRepository('');

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);

        // $context = ContextMock::get();
    }

    public function testIfPaymentDataIsEmpty()
    {
        $paymentData = null;
        $this->assertSame(
            ['Please fill in the required fields'],
            $this->repo->checkOneyRequiredFields($paymentData)
        );
    }

    public function checkOneyRequiredFields($payment_data)
    {
        $tools = $this->toolsSpecific;
        $validate = $this->validateSpecific;
        $errors = [];

        if (!$payment_data) {
            return [$this->l('Please fill in the required fields')];
        }

        foreach ($payment_data as $key => $data) {
            $parsed = explode('-', $key);
            $type = $parsed[0];
            $field = '';
            if (isset($parsed[1])) {
                $field = $parsed[1];
            }
            switch ($field) {
                case 'email':
                    if ($tools->tool('strlen', $data, 'UTF-8') > 100
                        && $tools->tool('strpos', $data, '+') !== false) {
                        $text = $this->l('Your email address is too long and the + character is not valid, 
                        please change it to another address (max 100 characters).');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data, 'UTF-8') > 100) {
                        $text = $this->l('Your email address is too long, 
                        please change it to a shorter one (max 100 characters).');
                        $errors[] = $text;
                    } elseif (strpos($data, '+') !== false) {
                        $text = $this->l('The + character is not valid. 
                        Please change your email address (100 characters max).');
                        $errors[] = $text;
                    }
                    break;
                case 'mobile_phone_number':
                    $id_address = $type == 'shipping' ?
                        $this->contextSpecific->getContext()->cart->id_address_delivery :
                        $this->contextSpecific->getContext()->cart->id_address_invoice;
                    $address = $this->addressSpecific->getAddress($id_address);
                    $country = $this->countrySpecific->getCountry($address->id_country);
                    $valid = $this->payplug->isValidMobilePhoneNumber($data, $country->iso_code);
                    if (!$valid) {
                        $errors[] = $this->l('Please enter your mobile phone number.');
                    }
                    break;
                case 'first_name':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ?
                            $this->l('Please enter your shipping firstname.') :
                            $this->l('Please enter your billing firstname.');
                        $errors[] = $text;
                    }
                    break;
                case 'last_name':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ?
                            $this->l('Please enter your shipping lastname.') :
                            $this->l('Please enter your billing lastname.');
                        $errors[] = $text;
                    }
                    break;
                case 'address1':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ?
                            $this->l('Please enter your shipping address.') :
                            $this->l('Please enter your billing address.');
                        $errors[] = $text;
                    }
                    break;
                case 'postcode':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ?
                            $this->l('Please enter your shipping postcode.') :
                            $this->l('Please enter your billing postcode.');
                        $errors[] = $text;
                    }
                    break;
                case 'city':
                    if (!$validate->validate('isCityName', $data)) {
                        $text = $type == 'shipping' ?
                            $this->l('Please enter your shipping city.') :
                            $this->l('Please enter your billing city.');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data, 'UTF-8') > 32) {
                        $text = $this->l('Your city name is too long (max 32 characters). ')
                            . $this->l('Please change it to another one or select another payment method.');
                        $errors[] = $text;
                    }
                    break;
            }
        }

        return $errors;
    }
}
