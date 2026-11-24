<?php

namespace PayPlug\Tests\utilities\helpers\LanguageHelper;

use PayPlug\src\utilities\helpers\LanguageHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group language_helper
 */
class getOneyWidgetLanguageTest extends TestCase
{
    protected $languageHelper;

    public function setUp(): void
    {
        $this->languageHelper = (new \ReflectionClass(LanguageHelper::class))->newInstanceWithoutConstructor();
    }

    /**
     * @description languages accepted by the Oney widget provider
     *
     * @return \Generator
     */
    public function acceptedLanguageProvider()
    {
        $accepted = ['FR-FR', 'PT-PT', 'ES-ES', 'IT-IT', 'RO-RO', 'FR-BE', 'NL-BE', 'EN-NL', 'NL-NL', 'EN-PT', 'EN-EU'];
        foreach ($accepted as $language) {
            yield strtolower($language) => [strtolower($language), $language];

            yield $language => [$language, $language];

            yield ' ' . $language . ' ' => [' ' . strtolower($language) . ' ', $language];
        }

        yield 'fr-FR' => ['fr-FR', 'FR-FR'];
    }

    /**
     * @dataProvider acceptedLanguageProvider
     *
     * @param string $language_code
     * @param string $expected
     */
    public function testAnAcceptedLanguageIsSentAsTheOneyCode($language_code, $expected)
    {
        $this->assertSame($expected, $this->languageHelper->getOneyWidgetLanguage($language_code));
    }

    /**
     * @description languages not accepted by the Oney widget provider
     *
     * @return \Generator
     */
    public function unacceptedLanguageProvider()
    {
        yield 'en-us' => ['en-us'];

        yield 'en-gb' => ['en-gb'];

        yield 'de-de' => ['de-de'];

        yield 'two letters' => ['fr'];

        yield 'pt-br' => ['pt-br'];

        yield 'es-mx' => ['es-mx'];

        yield 'empty' => [''];

        yield 'whitespace' => ['  '];

        yield 'null' => [null];

        yield 'underscore' => ['fr_fr'];

        yield 'extra subtag' => ['fr-fr-x'];

        yield 'hyphen only' => ['-'];

        yield 'missing region' => ['FR-'];

        yield 'garbage' => ['garbage'];
    }

    /**
     * @dataProvider unacceptedLanguageProvider
     *
     * @param string|null $language_code
     */
    public function testAnUnacceptedLanguageFallsBackToEnglish($language_code)
    {
        $this->assertSame('EN-EU', $this->languageHelper->getOneyWidgetLanguage($language_code));
    }
}
