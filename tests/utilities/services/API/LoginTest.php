<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class LoginTest extends BaseApi
{
    private $email;
    private $password;

    public function setUp()
    {
        parent::setUp();
        $this->email = 'unit_test@email.com';
        $this->password = 'password';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $email
     */
    public function testWhenGivenEmailIsntValidString($email)
    {
        $this->assertFalse($this->service->login($email, $this->password));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $password
     */
    public function testWhenGivenPasswordIsntValidString($password)
    {
        $this->assertFalse($this->service->login($this->email, $password));
    }

    public function testWhenBadRequestExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('getKeysByLogin')
            ->andThrow(new \Payplug\Exception\BadRequestException('An error occured during the process', '', 400));

        $this->assertFalse($this->service->login($this->email, $this->password));
    }

    public function testWhenPayplugServerExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('getKeysByLogin')
            ->andThrow(new \Payplug\Exception\PayplugServerException('An error occured during the process', '', 500));

        $this->assertFalse($this->service->login($this->email, $this->password));
    }

    public function testWhenExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('getKeysByLogin')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertFalse($this->service->login($this->email, $this->password));
    }

    public function testWhenApiKeyCantBeSetted()
    {
        $this->authentication->shouldReceive([
            'getKeysByLogin' => [
                'httpResponse' => 'user.keys',
            ],
        ]);

        $this->service->shouldReceive([
            'setUserAgent' => true,
            'setApiKeysbyJsonResponse' => false,
        ]);

        $this->assertFalse($this->service->login($this->email, $this->password));
    }

    public function testWhenUserIsLogged()
    {
        $this->authentication->shouldReceive([
            'getKeysByLogin' => [
                'httpResponse' => 'user.keys',
            ],
        ]);

        $this->service->shouldReceive([
            'setUserAgent' => true,
            'setApiKeysbyJsonResponse' => $this->api,
        ]);

        $this->assertTrue($this->service->login($this->email, $this->password));
    }
}
