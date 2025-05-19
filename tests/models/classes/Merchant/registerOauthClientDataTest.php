<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 *
 * @runTestsInSeparateProcesses
 */
class registerOauthClientDataTest extends BaseMerchant
{
    protected $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->oauth_client_data = [
            'test' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
            ],
            'live' => [
                'client_id' => 'live_client_id',
                'client_secret' => 'live_client_secret',
            ],
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * * @param mixed $client_data
     */
    public function testWhenGivenClientDataIsntValidArray($client_data)
    {
        $this->assertFalse($this->class->registerOauthClientData($client_data));
    }

    public function testWhenClientDataCantBeRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => false,
        ]);
        $this->assertFalse($this->class->registerOauthClientData($this->oauth_client_data));
    }

    public function testWhenClientDataIsRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);
        $this->assertTrue($this->class->registerOauthClientData($this->oauth_client_data));
    }
}
