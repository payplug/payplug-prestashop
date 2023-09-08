<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group old_repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class ConfirmeDeleteCardMessageTest extends BaseCardRepository
{
    public function testValidTranslationKey()
    {
        $this->assertSame(
            'card.CardRepository.confirmDeleteCardMessage',
            $this->repo->confirmDeleteCardMessage()
        );
    }
}
