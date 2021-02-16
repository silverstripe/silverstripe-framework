<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;

/**
 * Mofifies HTML that is about to be sent to the browser
 */
class HTMLModificationMiddleware implements HTTPMiddleware
{
    use Configurable;

    /**
     * @var string
     */
    private static $img_loading_attr = 'lazy';

    /**
     * @var string
     */
    private static $iframe_loading_attr = 'lazy';

    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** HTTPResponse $response */
        $response = $delegate($request);

        // Only modify output if the response status code is 200
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $this->addLoadingAttribute($response);

        return $response;
    }

    /**
     * Add an HTML loading attribute to `<img>` and `<iframe>` elements where:
     * - there isn't already an existing loading attribute on the element
     * - src, width and height attributes are all present on the element
     *
     * Example output:
     * `<img loading="lazy" src="/my/image.jpg" width="150" height="100">`
     *
     * @param HTTPResponse $response
     */
    private function addLoadingAttribute(HTTPResponse $response): void
    {
        $body = $response->getBody();
        $requiredAttrs = ['src', 'width', 'height'];
        foreach (['img', 'iframe'] as $tag) {
            $loadingAttr = $this->config()->get($tag . '_loading_attr');
            if (array_key_exists($loadingAttr, ['lazy' => 1, 'eager' => 1, 'auto' => 1])) {
                preg_match_all("#<$tag([^>]+)>#", $body, $matches);
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $element = $matches[0][$i];
                    $attrs = $matches[1][$i];
                    // Skip if there is an existing loading attribute
                    if (strpos($attrs, ' loading=') !== false) {
                        continue;
                    }
                    // Ensure relevant attributes are present
                    foreach ($requiredAttrs as $attr) {
                        if (strpos($attrs, " $attr=") === false) {
                            continue 2;
                        }
                    }
                    // Add loading attr
                    $replacement = str_replace("<$tag ", "<$tag loading=\"$loadingAttr\" ", $element);
                    $body = str_replace($element, $replacement, $body);
                }
            }
        }
        $response->setBody($body);
    }
}
