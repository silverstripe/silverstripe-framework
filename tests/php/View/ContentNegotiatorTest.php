<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Tests\SSViewerTest\TestFixture;

class ContentNegotiatorTest extends SapphireTest
{

    /**
     * Small helper to render templates from strings
     * Cloned from SSViewerTest
     */
    private function render($templateString, $data = null)
    {
        $t = SSViewer::fromString($templateString);
        if (!$data) {
            $data = new TestFixture();
        }
        return $t->process($data);
    }

    public function testXhtmltagReplacement()
    {
        $tmpl1 = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
                . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
				<head><% base_tag %></head>
				<body>
				<form action="#">
					<select>
							<option>aa</option>
							<option selected = "selected">bb</option>
							<option selected="selected">cc</option>
							<option class="foo" selected>dd</option>
							<option>ee</option>
							<option selected value="">ll</option>
					</select>
					<input type="checkbox">ff
					<input type="checkbox" checked = "checked">gg
					<input type="checkbox" checked="checked">hh
					<input class="bar" type="checkbox" checked>ii
					<input type="checkbox" checked class="foo">jj
					<input type="submit">
				</form>
				<body>
			</html>';

        // Check that the content negotiator converts to the equally legal formats
        $negotiator = new ContentNegotiator();

        $response = new HTTPResponse($this->render($tmpl1));
        $negotiator->xhtml($response);

        ////////////////////////
        // XHTML select options
        ////////////////////////
        $this->assertMatchesRegularExpression('/<option>aa<\/option>/', $response->getBody());
        $this->assertMatchesRegularExpression('/<option selected = "selected">bb<\/option>/', $response->getBody());
        $this->assertMatchesRegularExpression('/<option selected="selected">cc<\/option>/', $response->getBody());
        // Just transform this
        $this->assertMatchesRegularExpression('/<option class="foo" selected="selected">dd<\/option>/', $response->getBody());
        $this->assertMatchesRegularExpression('/<option selected="selected" value="">ll<\/option>/', $response->getBody());

        ////////////////////////////////////////////////
        // XHTML checkbox options + XHTML input closure
        ////////////////////////////////////////////////
        $this->assertMatchesRegularExpression('/<input type="checkbox"\/>ff/', $response->getBody());
        $this->assertMatchesRegularExpression('/<input type="checkbox" checked = "checked"\/>g/', $response->getBody());
        $this->assertMatchesRegularExpression('/<input type="checkbox" checked="checked"\/>hh/', $response->getBody());
        // Just transform this
        $this->assertMatchesRegularExpression('/<input class="bar" type="checkbox" checked="checked"\/>ii/', $response->getBody());
        $this->assertMatchesRegularExpression('/<input type="checkbox" checked="checked" class="foo"\/>jj/', $response->getBody());
    }
}
