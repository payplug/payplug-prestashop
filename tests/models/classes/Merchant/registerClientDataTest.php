<?php

namespace PayPlug\tests\models\classes\Address;

use PayPlug\tests\models\classes\Merchant\BaseMerchant;

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
            'client_id' => 'client-id-key',
            'client_secret' => 'clientSecretKey',
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

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * * @param mixed $client_id
     */
    public function testWhenGivenClientIDIsntValidString($client_id)
    {
        $client_data = $this->client_data;
        $client_data['client_id'] = $client_id;
        $this->assertFalse($this->class->registerClientData($client_data));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * * @param mixed $client_secret
     */
    public function testWhenGivenClientSecretIsntValidString($client_secret)
    {
        $client_data = $this->client_data;
        $client_data['client_secret'] = $client_secret;
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);
        $this->assertFalse($this->class->registerClientData($client_data));
    }

    public function testWhenClientIDCantBeRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => false,
        ]);
        $this->assertFalse($this->class->registerClientData($this->client_data));
    }

    public function testWhenClientSecretCantBeRegistered()
    {
        $this->configuration_class
            ->shouldReceive('set')
            ->once()
            ->andReturn(true);
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
