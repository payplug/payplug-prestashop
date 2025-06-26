<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 *
 * @runTestsInSeparateProcesses
 */
class getClientDataTest extends BaseMerchant
{
    public $session;
    public $company_id;

    public function setUp()
    {
        parent::setUp();
        $this->session = 'session_token';
        $this->company_id = 'company_id';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * * @param mixed $session
     */
    public function testWhenGivenSessionIsntValidString($session)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong session given',
            ],
            $this->class->getClientData($session, $this->company_id)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * * @param mixed $company_id
     */
    public function testWhenGivenCompanyIdIsntValidString($company_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong company_id given',
            ],
            $this->class->getClientData($this->session, $company_id)
        );
    }

    public function testWhenNoClientDataCantBeGot()
    {
        $this->api_service->shouldReceive([
            'getClientData' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [
                'result' => true,
                'data' => [
                    'test' => [],
                    'live' => [],
                ],
            ],
            $this->class->getClientData($this->session, $this->company_id)
        );
    }

    public function testWhenClientDataAreGot()
    {
        $oauth_client_data = [
            'client_id' => 'client_id',
            'client_secret' => 'client_secret',
        ];
        $this->api_service->shouldReceive([
            'getClientData' => [
                'result' => true,
                'data' => $oauth_client_data,
            ],
        ]);
        $this->assertSame(
            [
                'result' => true,
                'data' => [
                    'test' => $oauth_client_data,
                    'live' => $oauth_client_data,
                ],
            ],
            $this->class->getClientData($this->session, $this->company_id)
        );
    }

    /**
     * @description Get client data for live and test mode
     *
     * @param string $session
     * @param string $company_id
     *
     * @return array
     */
    public function getClientData($session = '', $company_id = '')
    {
        if (!is_string($session) || empty($session)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::getClientData - Invalid argument, $session must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Wrong session given',
            ];
        }

        if (!is_string($company_id) || empty($company_id)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Merchant::getClientData - Invalid argument, $company_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Wrong company_id given',
            ];
        }

        $data = [];
        $client_name = 'Prestashop';

        // Get the client id and secret for test mode
        $oauth_client_data_test = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getClientData($company_id, $client_name, 'test', $session);
        $data['test'] = $oauth_client_data_test['result'] ? [
            'client_id' => $oauth_client_data_test['data']['client_id'],
            'client_secret' => $oauth_client_data_test['data']['client_secret'],
        ] : [];

        // Get the client id and secret for live mode
        $oauth_client_data_live = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getClientData($company_id, $client_name, 'live', $session);
        $data['live'] = $oauth_client_data_live['result'] ? [
            'client_id' => $oauth_client_data_live['data']['client_id'],
            'client_secret' => $oauth_client_data_live['data']['client_secret'],
        ] : [];

        return [
            'result' => true,
            'data' => $data,
        ];
    }
}
