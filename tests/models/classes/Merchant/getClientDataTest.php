<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 */
class getClientDataTest extends BaseMerchant
{
    public $session;
    public $company_id;

    public function setUp(): void
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
}
