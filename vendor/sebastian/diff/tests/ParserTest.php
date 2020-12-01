<?php
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Diff;

use PHPUnit\Framework\TestCase;

/**
 * @covers SebastianBergmann\Diff\Parser
 *
 * @uses SebastianBergmann\Diff\Chunk
 * @uses SebastianBergmann\Diff\Diff
 * @uses SebastianBergmann\Diff\Line
 */
class ParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new Parser;
    }

    public function testParse()
    {
        $content = \file_get_contents(__DIR__ . '/fixtures/patch.txt');

        $diffs = $this->parser->parse($content);

        $this->assertInternalType('array', $diffs);
        $this->assertContainsOnlyInstancesOf('SebastianBergmann\Diff\Diff', $diffs);
        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->getChunks();
        $this->assertInternalType('array', $chunks);
        $this->assertContainsOnlyInstancesOf('SebastianBergmann\Diff\Chunk', $chunks);

        $this->assertCount(1, $chunks);

        $this->assertEquals(20, $chunks[0]->getStart());

        $this->assertCount(4, $chunks[0]->getLines());
    }

    public function testParseWithMultipleChunks()
    {
        $content = \file_get_contents(__DIR__ . '/fixtures/patch2.txt');

        $diffs = $this->parser->parse($content);

        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->getChunks();
        $this->assertCount(3, $chunks);

        $this->assertEquals(20, $chunks[0]->getStart());
        $this->assertEquals(320, $chunks[1]->getStart());
        $this->assertEquals(600, $chunks[2]->getStart());

        $this->assertCount(5, $chunks[0]->getLines());
        $this->assertCount(5, $chunks[1]->getLines());
        $this->assertCount(4, $chunks[2]->getLines());
    }

    public function testParseWithRemovedLines()
    {
        $content = 'abcdefg';
        $diffs = $this->parser->parse($content);
        $this->assertInternalType('array', $diffs);
        $this->assertContainsOnlyInstancesOf('SebastianBergmann\Diff\Diff', $diffs);
        $this->assertCount(1, $diffs);

        $chunks = $diffs[0]->getChunks();

        $this->assertInternalType('array', $chunks);
        $this->assertContainsOnlyInstancesOf('SebastianBergmann\Diff\Chunk', $chunks);
        $this->assertCount(1, $chunks);

        $chunk = $chunks[0];
        $this->assertSame(49, $chunk->getStart());
        $this->assertSame(49, $chunk->getEnd());
        $this->assertSame(9, $chunk->getStartRange());
        $this->assertSame(8, $chunk->getEndRange());

        $lines = $chunk->getLines();
        $this->assertInternalType('array', $lines);
        $this->assertContainsOnlyInstancesOf('SebastianBergmann\Diff\Line', $lines);
        $this->assertCount(2, $lines);

        /** @var Line $line */
        $line = $lines[0];
        $this->assertSame('A', $line->getContent());
        $this->assertSame(Line::UNCHANGED, $line->getType());

        $line = $lines[1];
        $this->assertSame('B', $line->getContent());
        $this->assertSame(Line::REMOVED, $line->getType());
    }

    public function testParseDiffForMulitpleFiles()
    {
        $content = 'abcdefg';
        $diffs = $this->parser->parse($content);
        $this->assertCount(2, $diffs);

        /** @var Diff $diff */
        $diff = $diffs[0];
        $this->assertSame('a/Test.txt', $diff->getFrom());
        $this->assertSame('b/Test.txt', $diff->getTo());
        $this->assertCount(1, $diff->getChunks());

        $diff = $diffs[1];
        $this->assertSame('a/Test2.txt', $diff->getFrom());
        $this->assertSame('b/Test2.txt', $diff->getTo());
        $this->assertCount(1, $diff->getChunks());
    }
}
