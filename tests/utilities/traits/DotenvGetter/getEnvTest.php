<?php

namespace PayPlug\tests\utilities\traits\DotenvGetter;

use PayPlug\src\utilities\traits\DotenvGetter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class getEnvTest extends TestCase
{
    private $user_of_trait;

    public function setUp(): void
    {
        parent::setUp();
        $this->user_of_trait = new class() {
            use DotenvGetter;

            public function callGetEnv($key)
            {
                return $this->getEnv($key);
            }
        };
    }

    public function tearDown(): void
    {
        unset($_ENV['PAYPLUG_TEST_DOTENV_GETTER_KEY']);
        parent::tearDown();
    }

    public function testReturnsTheEnvValueWhenSet()
    {
        $_ENV['PAYPLUG_TEST_DOTENV_GETTER_KEY'] = 'a-value';

        $this->assertSame('a-value', $this->user_of_trait->callGetEnv('PAYPLUG_TEST_DOTENV_GETTER_KEY'));
    }

    public function testReturnsNullWhenNotSet()
    {
        unset($_ENV['PAYPLUG_TEST_DOTENV_GETTER_KEY']);

        $this->assertNull($this->user_of_trait->callGetEnv('PAYPLUG_TEST_DOTENV_GETTER_KEY'));
    }
}
