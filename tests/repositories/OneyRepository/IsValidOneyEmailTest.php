<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\src\utilities\validators\accountValidator;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsValidOneyEmailTest extends BaseOneyRepository
{
    protected $email;

    public function setUp()
    {
        parent::setUp();
        $this->email = 'mock@payplug.com';
        $this->validator = new AccountValidator();
    }

    public function testWithValidEmail()
    {
        $response = $this->repo->isValidOneyEmail($this->email);
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $response
        );
    }

    public function testWithEmptyEmail()
    {
        $response = $this->repo->isValidOneyEmail('');
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithInValidEmail()
    {
        $response = $this->repo->isValidOneyEmail(null);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithInValidEmailFormat()
    {
        $response = $this->repo->isValidOneyEmail([$this->email]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is not a valid email',
            ],
            $response
        );
    }

    public function testWithForbiddenChar()
    {
        $error_email = 'test+' . $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The + character is not valid. Please change your email address (100 characters max).',
            ],
            $response
        );
    }

    public function testWithTooLongEmail()
    {
        $max_lenght = 100;
        $error_email = '';
        for ($i = 0; $i < $max_lenght; ++$i) {
            $error_email .= 'a';
        }
        $error_email .= $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is too long. Please change your email address (100 characters max).',
            ],
            $response
        );
    }

    public function testWithTooLongAndWrongEmail()
    {
        $max_lenght = 100;
        $error_email = '';
        for ($i = 0; $i < $max_lenght; ++$i) {
            $error_email .= 'a';
        }
        $error_email .= '+' . $this->email;
        $response = $this->repo->isValidOneyEmail($error_email);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Your email address is too long and the + character is not valid please change it to another address (max 100 characters).',
            ],
            $response
        );
    }
}
