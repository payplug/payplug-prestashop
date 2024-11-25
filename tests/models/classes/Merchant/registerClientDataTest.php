<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_classe
 *
 * @runTestsInSeparateProcesses
 */
class registerClientDataTest extends BaseMerchant
{
    protected $configuration_class;
    protected $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
        ]);
        $this->client_data = [
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
        $this->assertFalse($this->class->registerClientData($client_data));
    }

    public function testWhenClientDataCantBeRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => false,
        ]);
        $this->assertFalse($this->class->registerClientData($this->client_data));
    }

    public function testWhenClientDataIsRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);
        $this->assertTrue($this->class->registerClientData($this->client_data));
    }
}
