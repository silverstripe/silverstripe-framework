<?php

namespace SilverStripe\Control;

use BadMethodCallException;

/**
 * A response which contains a streamable data source.
 *
 * @package framework
 * @subpackage control
 */
class HTTPStreamResponse extends HTTPResponse
{

    /**
     * Stream source for this response
     *
     * @var resource
     */
    protected $stream = null;

    /**
     * Set to true if this stream has been consumed.
     * A consumed non-seekable stream will not be re-consumable
     *
     * @var bool
     */
    protected $consumed = false;

    /**
     * HTTPStreamResponse constructor.
     * @param resource $stream Data stream
     * @param int $contentLength size of the stream in bytes
     * @param int $statusCode The numeric status code - 200, 404, etc
     * @param string $statusDescription The text to be given alongside the status code.
     */
    public function __construct($stream, $contentLength, $statusCode = null, $statusDescription = null)
    {
        parent::__construct(null, $statusCode, $statusDescription);
        $this->setStream($stream);
        if ($contentLength) {
            $this->addHeader('Content-Length', $contentLength);
        }
    }

    /**
     * Determine if a stream is seekable
     *
     * @return bool
     */
    protected function isSeekable()
    {
        $stream = $this->getStream();
        if (!$stream) {
            return false;
        }
        $metadata = stream_get_meta_data($stream);
        return $metadata['seekable'];
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param resource $stream
     * @return $this
     */
    public function setStream($stream)
    {
        $this->setBody(null);
        $this->stream = $stream;
        return $this;
    }

    /**
     * Get body prior to stream traversal
     *
     * @return string
     */
    public function getSavedBody()
    {
        return parent::getBody();
    }

    public function getBody()
    {
        $body = $this->getSavedBody();
        if (isset($body)) {
            return $body;
        }

        // Consume stream into string
        $body = $this->consumeStream(function ($stream) {
            $body = stream_get_contents($stream);

            // If this stream isn't seekable, we'll need to save the body
            // in case of subsequent requests.
            if (!$this->isSeekable()) {
                $this->setBody($body);
            }
            return $body;
        });
        return $body;
    }

    /**
     * Safely consume the stream
     *
     * @param callable $callback Callback which will perform the consumable action on the stream
     * @return mixed Result of $callback($stream) or null if no stream available
     * @throws BadMethodCallException Throws exception if stream can't be re-consumed
     */
    protected function consumeStream($callback)
    {
        // Load from stream
        $stream = $this->getStream();
        if (!$stream) {
            return null;
        }

        // Check if stream must be rewound
        if ($this->consumed) {
            if (!$this->isSeekable()) {
                throw new BadMethodCallException(
                    "Unseekable stream has already been consumed"
                );
            }
            rewind($stream);
        }

        // Consume
        $this->consumed = true;
        return $callback($stream);
    }

    /**
     * Output body of this response to the browser
     */
    protected function outputBody()
    {
        // If the output has been overwritten, or the stream is irreversible and has
        // already been consumed, return the cached body.
        $body = $this->getSavedBody();
        if ($body) {
            echo $body;
            return;
        }

        // Stream to output
        if ($this->getStream()) {
            $this->consumeStream(function ($stream) {
                fpassthru($stream);
            });
            return;
        }

        // Fail over
        parent::outputBody();
    }
}
