<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Dev\SapphireTest;

class HTTPStreamResponseTest extends SapphireTest
{
    /**
     * Test replaying of stream from memory
     */
    public function testReplayStream()
    {
        $path = __DIR__ . '/HTTPStreamResponseTest/testfile.txt';
        $stream = fopen($path, 'r');
        $response = new HTTPStreamResponse($stream, filesize($path));

        // Test body (should parse stream directly into memory)
        $this->assertEquals("Test output\n", $response->getBody());

        // Test stream output
        ob_start();
        $response->output();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(12, $response->getHeader('Content-Length'));
        $this->assertEquals("Test output\n", $result);
    }

    /**
     * Test stream directly without loading into memory
     */
    public function testDirectStream()
    {
        $path = __DIR__ . '/HTTPStreamResponseTest/testfile.txt';
        $stream = fopen($path, 'r');
        $metadata = stream_get_meta_data($stream);
        $this->assertTrue($metadata['seekable']);
        $response = new HTTPStreamResponse($stream, filesize($path));

        // Test stream output
        ob_start();
        $response->output();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(12, $response->getHeader('Content-Length'));
        $this->assertEquals("Test output\n", $result);
        $this->assertEmpty($response->getSavedBody(), 'Body of seekable stream is un-cached');

        // Seekable stream can be repeated
        ob_start();
        $response->output();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(12, $response->getHeader('Content-Length'));
        $this->assertEquals("Test output\n", $result);
        $this->assertEmpty($response->getSavedBody(), 'Body of seekable stream is un-cached');
    }
}
