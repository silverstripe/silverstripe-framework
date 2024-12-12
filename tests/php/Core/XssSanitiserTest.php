<?php

namespace SilverStripe\Core\Tests;

use DOMElement;
use SilverStripe\Core\XssSanitiser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\HTMLValue;

class XssSanitiserTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideSanitise(): array
    {
        // Most of these scenarios are inspired by Symfony's HtmlSanitizerAllTest scenarios
        return [
            // Text
            [
                'input' => '',
                'expected' => '',
            ],
            [
                'input' => 'hello world',
                'expected' => 'hello world',
            ],
            [
                'input' => '&lt;hello world&gt;',
                'expected' => '&lt;hello world&gt;',
            ],
            [
                'input' => '< Hello',
                'expected' => ' Hello',
            ],
            [
                'input' => 'Lorem & Ipsum',
                'expected' => 'Lorem &amp; Ipsum',
            ],
            // Unknown tag
            [
                'input' => '<unknown>Lorem ipsum</unknown>',
                'expected' => '<unknown>Lorem ipsum</unknown>',
            ],
            // Scripts
            [
                'input' => '<script>alert(\'ok\');</script>',
                'expected' => 'alert(\'ok\');',
            ],
            [
                'input' => 'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>',
                'expected' => 'javascript:/*--&gt;',
            ],
            [
                // Not exploitable XSS
                'input' => '<scr<script>ipt>alert(1)</script>',
                'expected' => '<scr>ipt&gt;alert(1)</scr>',
            ],
            [
                // Not exploitable XSS
                'input' => '<scr<a>ipt>alert(1)</script>',
                'expected' => '<scr><a>ipt&gt;alert(1)</a></scr>',
            ],
            [
                // Not exploitable XSS
                'input' => '<noscript>Lorem ipsum</noscript>',
                'expected' => '<noscript>Lorem ipsum</noscript>',
            ],
            [
                'input' => '<div>Lorem ipsum dolor sit amet, consectetur adipisicing elit.<script>alert(\'ok\');</script></div>',
                'expected' => '<div>Lorem ipsum dolor sit amet, consectetur adipisicing elit.alert(\'ok\');</div>',
            ],
            [
                'input' => '<a href="javascript:alert(\'ok\')">Lorem ipsum dolor sit amet, consectetur adipisicing elit.</a>',
                'expected' => '<a>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</a>',
            ],
            [
                // Not exploitable XSS
                'input' => '<<a href="javascript:evil"/>a href="javascript:evil"/>',
                'expected' => '<a>a href="javascript:evil"/&gt;</a>',
            ],
            [
                'input' => '<a href="javascript:alert(\'ok\')">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            [
                'input' => '<a href="javascript://%0Aalert(document.cookie)">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            [
                'input' => '<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;">Lorem ipsum</a>',
                'expected' => '<a>Lorem ipsum</a>',
            ],
            [
                // Note this includes U+200A, U+202F, U+205F, U+2000, U+2001, U+2002, U+2003, U+2004, U+2005, U+2006, U+2007, U+2008, U+2009, U+3000
                'input' => "<a href=\"ja\tva\v\r\n sc     r i   　    pt:alert('foo')\">Lorem ipsum</a>",
                'expected' => '<a>Lorem ipsum</a>',
            ],
            [
                // Not exploitable XSS
                'input' => '<a href= onmouseover="alert(\\\'XSS\\\');">Lorem ipsum</a>',
                'expected' => '<a href="onmouseover=&quot;alert(\&#039;XSS\&#039;);&quot;">Lorem ipsum</a>',
            ],
            [
                'input' => '<a href="http://example.com" onclick="alert(\'ok\')">Test</a>',
                'expected' => '<a href="http://example.com">Test</a>',
            ],
            [
                'input' => '<a href="javascript:" title="Link title">Lorem ipsum</a>',
                'expected' => '<a title="Link title">Lorem ipsum</a>',
            ],
            [
                // Not exploitable XSS
                'input' => '<figure><img src="https://example.com/img/example.jpg" onclick="alert(\'ok\')" /></figure>',
                'expected' => '<figure><img src="https://example.com/img/example.jpg"></figure>',
            ],
            [
                // Not exploitable XSS
                'input' => '<img src= onmouseover="alert(\'XSS\');">',
                'expected' => '<img src="onmouseover=&quot;alert(&#039;XSS&#039;);&quot;">',
            ],
            [
                // Not exploitable XSS
                'input' => '<<img src="javascript:evil"/>iframe src="javascript:evil"/>',
                'expected' => '<img>iframe src="javascript:evil"/&gt;',
            ],
            [
                // Not exploitable XSS
                'input' => '<<img src="javascript:evil"/>img src="javascript:evil"/>',
                'expected' => '<img>img src="javascript:evil"/&gt;',
            ],
            [
                'input' => '<IMG SRC="javascript:alert(\'XSS\');">',
                'expected' => '<img>',
            ],
            [
                'input' => '<IMG SRC=javascript:alert(\'XSS\')>',
                'expected' => '<img>',
            ],
            [
                'input' => '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>',
                'expected' => '<img>',
            ],
            [
                'input' => '<IMG SRC=javascript:alert(&quot;XSS&quot;)>',
                'expected' => '<img>',
            ],
            [
                // Not exploitable XSS
                'input' => '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>',
                'expected' => '<img src="`javascript:alert(&quot;RSnake">',
            ],
            [
                'input' => '<IMG """><SCRIPT>alert("XSS")</SCRIPT>"\>',
                'expected' => '<img>alert("XSS")"\&gt;',
            ],
            [
                'input' => '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>',
                'expected' => '<img>',
            ],
            [
                'input' => '<IMG SRC=# onmouseover="alert(\'xxs\')">',
                'expected' => '<img src="#">',
            ],
            [
                'input' => '<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">',
                'expected' => '<img src="x">',
            ],
            [
                // decodes to `javascript:alert('XSS')`
                'input' => '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>',
                'expected' => '<img>',
            ],
            [
                // Not exploitable XSS
                'input' => '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>',
                'expected' => '<img src="&amp;#0000106&amp;#0000097&amp;#0000118&amp;#0000097&amp;#0000115&amp;#0000099&amp;#0000114&amp;#0000105&amp;#0000112&amp;#0000116&amp;#0000058&amp;#0000097&amp;#0000108&amp;#0000101&amp;#0000114&amp;#0000116&amp;#0000040&amp;#0000039&amp;#0000088&amp;#0000083&amp;#0000083&amp;#0000039&amp;#0000041">',
            ],
            [
                // Not exploitable XSS
                'input' => '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>',
                'expected' => '<img src="&amp;#x6A&amp;#x61&amp;#x76&amp;#x61&amp;#x73&amp;#x63&amp;#x72&amp;#x69&amp;#x70&amp;#x74&amp;#x3A&amp;#x61&amp;#x6C&amp;#x65&amp;#x72&amp;#x74&amp;#x28&amp;#x27&amp;#x58&amp;#x53&amp;#x53&amp;#x27&amp;#x29">',
            ],
            [
                // Decodes to a SVG with `<script type="text/ecmascript">alert("XSS");</script>` inside it
                // But that's not actually exploitable XSS
                'input' => '<IMG SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==">',
                'expected' => '<img src="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==">',
            ],
            [
                'input' => '<IMG LOWSRC="javascript:alert(\'XSS\')">',
                'expected' => '<img>',
            ],
            [
                'input' => '<IMG SRC=\'vbscript:msgbox(\"XSS")\'>',
                'expected' => '<img>',
            ],
            [
                'input' => '<img src="javascript:" alt="Image alternative text" title="Image title">',
                'expected' => '<img alt="Image alternative text" title="Image title">',
            ],
            [
                'input' => '<svg/onload=alert(\'XSS\')>',
                'expected' => '',
            ],
            [
                'input' => '<BGSOUND SRC="javascript:alert(\'XSS\');">',
                'expected' => '<bgsound></bgsound>',
            ],
            [
                // Not exploitable XSS
                'input' => '<BR SIZE="&{alert(\'XSS\')}">',
                'expected' => '<br size="&amp;{alert(&#039;XSS&#039;)}">',
            ],
            [
                'input' => '<BR></br>',
                'expected' => '<br><br>',
            ],

            [
                'input' => '<OBJECT TYPE="text/x-scriptlet" DATA="http://xss.rocks/scriptlet.html"></OBJECT>',
                'expected' => '',
            ],
            [
                // Decodes to a SVG with `<script type="text/ecmascript">alert("XSS");</script>` inside it
                'input' => '<EMBED SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"></EMBED>',
                'expected' => '',
            ],
            [
                // Not exploitable XSS
                'input' => '!<textarea>&lt;/textarea&gt;&lt;svg/onload=prompt`xs`&gt;</textarea>!',
                'expected' => '!<textarea>&lt;/textarea&gt;&lt;svg/onload=prompt`xs`&gt;</textarea>!',
            ],
            [
                'input' => '!<textarea></textarea><svg/onload=prompt`xs`></textarea>!',
                'expected' => '!<textarea></textarea>!',
            ],
            [
                'input' => '"><svg/onload=confirm(1)>"@x.y',
                'expected' => '"&gt;"@x.y',
            ],
            [
                'input' => '<div class="some-class"><img src="https://example.com/image.jpg"><span id="something025">one</span><script>2</script>two<span id="something026">three</span></div>',
                'expected' => '<div class="some-class"><img src="https://example.com/image.jpg"><span id="something025">one</span>2two<span id="something026">three</span></div>',
            ],
            // Styles
            [
                'input' => '<style>body { background: red; }</style>',
                'expected' => 'body { background: red; }',
            ],
            [
                'input' => '<div>Lorem ipsum dolor sit amet, consectetur.<style>body { background: red; }</style></div>',
                'expected' => '<div>Lorem ipsum dolor sit amet, consectetur.body { background: red; }</div>',
            ],
            [
                'input' => '<img src="https://example.com/img/example.jpg" style="position:absolute;top:0;left:0;width:9000px;height:9000px;">',
                'expected' => '<img src="https://example.com/img/example.jpg" style="position:absolute;top:0;left:0;width:9000px;height:9000px;">',
            ],
            [
                'input' => '<a style="font-size: 40px; color: red;">Lorem ipsum dolor sit amet, consectetur.</a>',
                'expected' => '<a style="font-size: 40px; color: red;">Lorem ipsum dolor sit amet, consectetur.</a>',
            ],
            // Comments
            [
                // Not exploitable XSS
                'input' => 'Lorem ipsum dolor sit amet, consectetur<!--if[true]> <script>alert(1337)</script> -->',
                'expected' => 'Lorem ipsum dolor sit amet, consectetur<!--if[true]> <script>alert(1337)</script> -->',
            ],
            [
                'input' => 'Lorem ipsum<![CDATA[ <!-- ]]> <script>alert(1337)</script> <!-- -->',
                'expected' => 'Lorem ipsum <!--  alert(1337) <!-- -->',
            ],
            // Normal tags (just checking they don't get mangled)
            [
                'input' => '<a>Lorem ipsum</a>',
                'expected' => '<a>Lorem ipsum</a>',
            ],
            [
                'input' => '<a href="/img/example.jpg" title="Link title">Lorem ipsum</a>',
                'expected' => '<a href="/img/example.jpg" title="Link title">Lorem ipsum</a>',
            ],
            [
                'input' => '<a href="http://example.com/index.html#this:stuff">Lorem ipsum</a>',
                'expected' => '<a href="http://example.com/index.html#this:stuff">Lorem ipsum</a>',
            ],
            [
                'input' => '<a href="mailto:test@example.com" title="Link title">Lorem ipsum</a>',
                'expected' => '<a href="mailto:test@example.com" title="Link title">Lorem ipsum</a>',
            ],
            [
                'input' => '<img src="/img/example.jpg" onanything="" alt="Image alternative text" title="Image title" height="150" width="300">',
                'expected' => '<img src="/img/example.jpg" alt="Image alternative text" title="Image title" height="150" width="300">',
            ],
            [
                'input' => '<img src="http://example.com/img/examp:le.jpg" alt="Image alternative text" title="Image title">',
                'expected' => '<img src="http://example.com/img/examp:le.jpg" alt="Image alternative text" title="Image title">',
            ],
            [
                'input' => '<img>',
                'expected' => '<img>',
            ],
            [
                'input' => '<div class="some-class"><img src="https://example.com/image.jpg"><span id="something025">one</span>two<span id="something026">three</span></div>',
                'expected' => '<div class="some-class"><img src="https://example.com/image.jpg"><span id="something025">one</span>two<span id="something026">three</span></div>',
            ],
        ];
    }

    /**
     * @dataProvider provideSanitise
     */
    public function testSanitiseString(string $input, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        $this->assertSame($expected, $sanitiser->sanitiseString($input));
    }

    /**
     * @dataProvider provideSanitise
     */
    public function testSanitiseHtmlValue(string $input, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        $htmlValue = new HTMLValue($input);
        $sanitiser->sanitiseHtmlValue($htmlValue);
        $this->assertSame($expected, $htmlValue->getContent());
    }

    /**
     * @dataProvider provideSanitise
     */
    public function testSanitiseElement(string $input, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        $htmlValue = new HTMLValue($input);
        foreach ($htmlValue->query('//*') as $element) {
            if (!is_a($element, DOMElement::class)) {
                continue;
            }
            $element = $sanitiser->sanitiseElement($element);
        }
        $this->assertSame($expected, $htmlValue->getContent());
    }

    public function provideSanitiseElementsAllowed(): array
    {
        return [
            'disallow these by default' => [
                'input' => '<script>alert("one");</script><svg><circle cx="50" cy="50" r="40" /></svg><embed src="image.jpg"></embed><object data="image.jpg"></object>',
                'removeElements' => null,
                'expected' => 'alert("one");<circle cx="50" cy="50" r="40"></circle>',
            ],
            'allow all' => [
                'input' => '<script>alert("one");</script><svg><circle cx="50" cy="50" r="40" /></svg><embed src="image.jpg"></embed><object data="image.jpg"></object>',
                'removeElements' => [],
                'expected' => '<script>alert("one");</script><svg><circle cx="50" cy="50" r="40"></circle></svg><embed src="image.jpg"></embed><object data="image.jpg"></object>',
            ],
            'disallow circle' => [
                'input' => '<script>alert("one");</script><svg><circle cx="50" cy="50" r="40" /></svg><embed src="image.jpg"></embed><object data="image.jpg"></object>',
                'removeElements' => ['circle'],
                'expected' => '<script>alert("one");</script><svg></svg><embed src="image.jpg"></embed><object data="image.jpg"></object>',
            ],
        ];
    }

    /**
     * @dataProvider provideSanitiseElementsAllowed
     */
    public function testSanitiseElementsAllowed(string $input, ?array $removeElements, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        if ($removeElements !== null) {
            $sanitiser->setElementsToRemove($removeElements);
        }
        $this->assertSame($expected, $sanitiser->sanitiseString($input));
    }

    public function provideSanitiseAttributesAllowed(): array
    {
        return [
            'disallow these by default' => [
                'input' => '<span class="my-class" onanything onerror onclick onetc="anything" accesskey="A">abcd</span>',
                'removeAttributes' => null,
                'expected' => '<span class="my-class">abcd</span>',
            ],
            'allow all' => [
                'input' => '<span class="my-class" onanything onerror onclick onetc="anything" accesskey="A">abcd</span>',
                'removeAttributes' => [],
                'expected' => '<span class="my-class" onanything="" onerror="" onclick="" onetc="anything" accesskey="A">abcd</span>',
            ],
            'disallow class' => [
                'input' => '<span class="my-class" onanything onerror onclick onetc="anything" accesskey="A">abcd</span>',
                'removeAttributes' => ['class'],
                'expected' => '<span onanything="" onerror="" onclick="" onetc="anything" accesskey="A">abcd</span>',
            ],
            'wildcard attributes' => [
                'input' => '<span class="my-class" title="my title" cattle="something" car="a thing" clap="nope" clop="yep" disabled="true">abcd</span>',
                'removeAttributes' => [
                    'cla*',
                    '*tle',
                    // this one specifically won't do anything
                    'di*ed',
                ],
                'expected' => '<span car="a thing" clop="yep" disabled>abcd</span>',
            ],
            // Not sure why you'd do this, but this functionality is a natural consequence of how `*something` and `something*` are implemented.
            'remove all attributes' => [
                'input' => '<span class="my-class" title="my title" cattle="something" car="a thing" clap="nope" clop="yep" disabled="true">abcd</span>',
                'removeAttributes' => [
                    '*',
                ],
                'expected' => '<span>abcd</span>',
            ],
        ];
    }

    /**
     * @dataProvider provideSanitiseAttributesAllowed
     */
    public function testSanitiseAttributesAllowed(string $input, ?array $removeAttributes, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        if ($removeAttributes !== null) {
            $sanitiser->setAttributesToRemove($removeAttributes);
        }
        $this->assertSame($expected, $sanitiser->sanitiseString($input));
    }

    public function provideSanitiseNoKeepInnerHtml(): array
    {
        return [
            'keeps inner html' => [
                'input' => '<section>something first<div>Keep this<span>and this</span></div><span>something last</span></section>',
                'keepInnerHtml' => true,
                'expected' => '<section>something firstKeep this<span>and this</span><span>something last</span></section>',
            ],
            'discards inner html' => [
                'input' => '<section>something first<div>Keep this<span>and this</span></div><span>something last</span></section>',
                'keepInnerHtml' => false,
                'expected' => '<section>something first<span>something last</span></section>',
            ],
            'multiple and nested disallowed elements (keep inner html)' => [
                'input' => '<section>something<div></div><div><div><div>nested </div><div>nested2</div></div></div><span>last</span></section>',
                'keepInnerHtml' => true,
                'expected' => '<section>somethingnested nested2<span>last</span></section>',
            ],
            'multiple and nested disallowed elements (discard inner html)' => [
                'input' => '<section>something<div></div><div><div><div>nested </div><div>nested2</div></div></div><span>last</span></section>',
                'keepInnerHtml' => false,
                'expected' => '<section>something<span>last</span></section>',
            ],
        ];
    }

    /**
     * @dataProvider provideSanitiseNoKeepInnerHtml
     */
    public function testSanitiseNoKeepInnerHtml(string $input, bool $keepInnerHtml, string $expected): void
    {
        $sanitiser = new XssSanitiser();
        $sanitiser->setElementsToRemove(['div'])->setKeepInnerHtmlOnRemoveElement($keepInnerHtml);
        $this->assertSame($expected, $sanitiser->sanitiseString($input));
    }
}
