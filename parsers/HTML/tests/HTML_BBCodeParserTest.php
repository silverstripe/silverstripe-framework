<?php
require_once 'PHPUnit.php';
require_once 'HTML/BBCodeParser.php';

class HTML_BBCodeParserTest extends PHPUnit_Framework_TestCase
{
    function testFilters()
    {
		$bbc = new HTML_BBCodeParser(array('filters' => ''));
		$bbc->addFilter('Basic');
		$this->basicBBCode($bbc, 'qparse');
		$bbc->removeFilter('Basic');
		$this->assertEquals('[b]txt[/b]', $bbc->qparse('[b]txt[/b]'), 'Basic filters have been removed.');
		$bbc->addFilters('Basic,Email');
		$this->basicBBCode($bbc, 'qparse');
		$this->emailBBCode($bbc, 'qparse');
	}

	function testQparse()
    {
		$bbc = new HTML_BBCodeParser(array('filters' => 'Basic,Email,Extended,Images,Links,Lists'));
        $this->basicBBCode($bbc, 'qparse');
		$this->listBBCode($bbc, 'qparse');
		$this->linkBBCode($bbc, 'qparse');
		$this->extBBCode($bbc, 'qparse');
		$this->imgBBCode($bbc, 'qparse');
		$this->emailBBCode($bbc, 'qparse');
	}

	function emailBBCode($bbc, $funcNam)
    {
		$this->assertEquals('<a href="mailto:guest@anonymous.org">guest@anonymous.org</a>', $bbc->$funcNam('guest@anonymous.org'));
		$this->assertEquals('<a href="mailto:guest@anonymous.org">mail me</a>', $bbc->$funcNam('[email=guest@anonymous.org]mail me[/email]'));
		$this->assertEquals('<a href="mailto:guest@anonymous.org">guest@anonymous.org</a>', $bbc->$funcNam('[email]guest@anonymous.org[/email]'));
	}

	function imgBBCode($bbc, $funcNam)
    {
        $this->assertEquals('<img src="/images/Enthalpy Wheel.png" width="100" height="99" alt="Enthalpy Wheel" />', $bbc->$funcNam('[img w=100 h=99 alt=Enthalpy Wheel]/images/Enthalpy Wheel.png[/img]'));
		$this->assertEquals('<img src="img.jpg" />', $bbc->$funcNam('[img]img.jpg[/img]'));
		$this->assertEquals('<img src="http://www.server.org/image.jpg" width="100" height="200" />', $bbc->$funcNam('[img w=100 h=200]http://www.server.org/image.jpg[/img]'));
	}

	function basicBBCode($bbc, $funcNam)
    {
		$this->assertEquals('<strong>txt</strong>', $bbc->$funcNam('[b]txt[/b]'));
		$this->assertEquals('<strong>txt</strong>', $bbc->$funcNam('[b]txt'));
		$this->assertEquals('<em>txt</em>', $bbc->$funcNam('[i]txt[/i]'));
		$this->assertEquals('<em>txt</em>', $bbc->$funcNam('[i]txt[/I]'));
		$this->assertEquals('<em>txt</em>', $bbc->$funcNam('[I]txt[/i]'));
		$this->assertEquals('<em>txt</em>', $bbc->$funcNam('[I]txt[/I]'));
		$this->assertEquals('<del>txt</del>', $bbc->$funcNam('[s]txt[/s]'));
		$this->assertEquals('<span style="text-decoration:underline;">txt</span>', $bbc->$funcNam('[u]txt[/u]'));
		$this->assertEquals('<sub>txt</sub>', $bbc->$funcNam('[sub]txt[/sub]'));
		$this->assertEquals('<sup>txt</sup>', $bbc->$funcNam('[sup]txt[/sup]'));
		$this->assertEquals('<sup><sub>txt</sub></sup>', $bbc->$funcNam('[sup][sub]txt[/sup][/sub]'));
		$this->assertEquals('<em><strong>txt</strong></em>', $bbc->$funcNam('[i][b]txt[/i][/b]'));
	}

	function listBBCode($bbc, $funcNam)
    {
		$this->assertEquals('<ul><li>txt</li></ul>', $bbc->$funcNam('[*]txt'));
		$this->assertEquals("<ul><li>txt\n</li></ul>", $bbc->$funcNam("[ulist][*]txt\n[/ulist]"));
		$this->assertEquals('<ul><li>txt</li></ul>', $bbc->$funcNam('[ulist]txt[/ulist]'));
		$this->assertEquals('<ul><li><ul><li><ul><li>txt</li></ul></li></ul></li></ul>', $bbc->$funcNam('[ulist][ulist][ulist]txt'));
		$this->assertEquals('<ul><li>[xxx]txt[/xxx]</li></ul>', $bbc->$funcNam('[ulist][xxx]txt[/xxx][/ulist]'));
		$this->assertEquals('<ul><li>txt</li></ul>', $bbc->$funcNam('[ulist][li]txt[/li][/ulist]'));
		$this->assertEquals('<ul><li>txt</li><li>txt</li></ul>', $bbc->$funcNam('[ulist][li]txt[li]txt[/ulist]'));
		$this->assertEquals('<ul><li>txt</li></ul>', $bbc->$funcNam('[ulist][*]txt[/ulist]'));
		$this->assertEquals('<ul><li><ol><li>txt</li></ol></li></ul>', $bbc->$funcNam('[ulist][*][list][*]txt[/ulist]'));
		$this->assertEquals('<ol><li>txt</li></ol>', $bbc->$funcNam('[list][li]txt[/li][/list]'));
		$this->assertEquals('<ul><li><ol><li>txt</li></ol></li></ul>', $bbc->$funcNam('[li][list][li]txt[/li][/list]'));
		$this->assertEquals('<ul><li>txt<ul><li>txt</li></ul></li></ul>', $bbc->$funcNam('[*]txt[ulist]txt[/ulist]'));
		$this->assertEquals('<ul><li><ul><li><ul><li><ul><li>txt</li></ul></li></ul></li></ul></li></ul>', $bbc->$funcNam('[li][ulist][ulist][ulist]txt'));
		$this->assertEquals(
			'<ol style="list-style-type:upper-alpha;"><li>ordered item 1, nested list:<ol style="list-style-type:upper-roman;"><li>nested item 1</li><li>nested item 2</li></ol></li><li>ordered item 2</li></ol>',
			$bbc->$funcNam('[list=A s=3][li]ordered item 1, nested list:[list=I][li]nested item 1[/li][li]nested item 2[/li][/list][/li][li]ordered item 2[/li][/list]'));
		$this->assertEquals(
			'<ol style="list-style-type:upper-alpha;"><li>ordered item 1 type A</li><li>ordered item 12 type A</li></ol>',
			$bbc->$funcNam('[list=A][li]ordered item 1 type A[/li][li=12]ordered item 12 type A[/li][/list]'));
		$this->assertEquals(
			'<ol style="list-style-type:lower-alpha;"><li>ordered item 5 type a</li><li>ordered item 6 type a</li></ol>',
			$bbc->$funcNam('[list=a s=5][li]ordered item 5 type a[/li][*]ordered item 6 type a[/list]'));
		$this->assertEquals(
			'<ol style="list-style-type:upper-roman;"><li>ordered item 1 type I</li></ol>',
			$bbc->$funcNam('[list=I][*]ordered item 1 type I[/list]'));
		$this->assertEquals(
			'<ol style="list-style-type:lower-roman;"><li>ordered item 1 type i</li><li>ordered item 4 type i</li></ol>',
			$bbc->$funcNam('[list=i][*]ordered item 1 type i[li=4]ordered item 4 type i[/li][/list]'));
		$this->assertEquals(
			'<ol style="list-style-type:decimal;"><li>ordered item 1</li><li>ordered item 2</li></ol>',
			$bbc->$funcNam('[list=1][*]ordered item 1[*]ordered item 2[/list]'));
        //Bug #512: [list] in a [list] breaks the first [list]
        $this->assertEquals(
            '<ol><li> Subject 1<ol><li> First</li><li> Second</li></ol></li><li> Subject 2</li></ol>',
            $bbc->$funcNam('[list][*] Subject 1[list][*] First[*] Second[/list][*] Subject 2[/list]')
        );
        //Bug #1201: [list] output adding extra <li></li>
        $this->assertEquals(
            '<ol><li>txt</li></ol>',
            $bbc->$funcNam('[list][*]txt[/list]')
        );
        //Bug#6335 Empty item displayed
        $this->assertEquals(
            '<ol style="list-style-type:decimal;"><li> Item one</li><li> Item two</li><li> Item three</li></ol>', 
            $bbc->$funcNam('[list=1][*] Item one[*] Item two[*] Item three[/list]'));
	}

	function linkBBCode($bbc, $funcNam)
    {
		$this->assertEquals(
			'<a href="http://www.test.com/">http://www.test.com/</a>',
			$bbc->$funcNam('http://www.test.com/'));
		$this->assertEquals(
			'<a href="http://www.test.com/">www.test.com</a>',
			$bbc->$funcNam('[url]www.test.com[/url]'));
		$this->assertEquals(
			'<a href="http://www.test.com/testurl">http://www.test.com/testurl</a>',
			$bbc->$funcNam('[url]http://www.test.com/testurl[/url]'));
		$this->assertEquals(
			'<a href="http://www.test.com/">testurl</a>',
			$bbc->$funcNam('[url=www.test.com/]testurl[/url]'));
		$this->assertEquals(
			'<a href="http://www.server.org">server</a>',
			$bbc->$funcNam('[url=http://www.server.org t=new]server[/url]'));
		$this->assertEquals(
			'txt <a href="http://www.test.com/">www.test.com</a> txt',
			$bbc->$funcNam('txt www.test.com txt'));
		$this->assertEquals(
			'txt (<a href="http://www.test.com/">www.test.com</a>) txt',
			$bbc->$funcNam('txt (www.test.com) txt'));
		$this->assertEquals(
			'txt <a href="http://www.test.com/test.php?a=1,2">www.test.com/test.php?a=1,2</a>, txt',
			$bbc->$funcNam('txt www.test.com/test.php?a=1,2, txt'));
		$this->assertEquals(
			'txt <a href="http://www.test.com/">www.test.com</a>, txt',
			$bbc->$funcNam('txt www.test.com, txt'));
		$this->assertEquals(
			'txt <a href="http://www.test.com/">http://www.test.com</a>: txt',
			$bbc->$funcNam('txt http://www.test.com: txt'));
		$this->assertEquals(
			'txt <a href="http://www.test.com/">www.test.com</a>; txt',
			$bbc->$funcNam('txt www.test.com; txt'));
        //Bug #1755: tags around an url -> mess
		$this->assertEquals(
			'txt <em><a href="http://www.test.com/">www.test.com</a></em> txt',
			$bbc->$funcNam('txt [i]www.test.com[/i] txt'));
        //Bug #1512: URL Tags Allow Javascript injection
		$this->assertEquals(
			'Click here',
			$bbc->$funcNam('[url=javascript:location.replace("bad_link");]Click here[/url]'));
		$this->assertEquals(
			'<a href="http://domain.com/index.php?i=1&amp;j=2">linked text</a>',
			$bbc->$funcNam('[url=http://domain.com/index.php?i=1&j=2]linked text[/URL]'));
        $this->assertEquals(
            '<a href="http://domain.com/index.php?i=1&amp;j=2">linked text</a>',
            $bbc->$funcNam('[url=http://domain.com/index.php?i=1&amp;j=2]linked text[/URL]'));
        //Bug #5609: BBCodeParser allows XSS
		$this->assertEquals(
            '<a href="javascript&amp;#058;//%0ASh=alert(%22CouCou%22);window.close();">Alert box with "CouCou"</a>',
            $bbc->$funcNam('[url=javascript://%0ASh=alert(%22CouCou%22);window.close();]Alert box with "CouCou"[/url]')
        );
        /*
        //Request #4936: Nested URLs in quotes not handled
        $this->assertEquals(
            '<q>Quoted text</q>', //?!?!?
            $bbc->$funcNam('[quote="[url=http://somewhere.com]URL-Title[/url]"]Quoted text[/quote]')
        );
        */
	}

	function extBBCode($bbc, $funcNam)
    {
		$this->assertEquals('<h2>txt</h2>', $bbc->$funcNam('[h2]txt[/h2]'));
		$this->assertEquals('<span style="color:blue">blue text</span>', $bbc->$funcNam('[color=blue]blue text[/color]'));
		$this->assertEquals('<span style="font-size:18pt">the size of this text is 18pt</span>', $bbc->$funcNam('[size=18]the size of this text is 18pt[/size]'));
		$this->assertEquals('<span style="font-family:arial">different font type</span>', $bbc->$funcNam('[font=arial]different font type[/font]'));
		$this->assertEquals('<div style="text-align:right">yes, you\'re right, this isn\'t on the left</div>', $bbc->$funcNam('[align=right]yes, you\'re right, this isn\'t on the left[/align]'));
		$this->assertEquals('he said: <q cite="http://www.server.org/quote.html">i\'m tony montana</q>', $bbc->$funcNam('he said: [quote=http://www.server.org/quote.html]i\'m tony montana[/quote]'));
		$this->assertEquals('<code>x + y = 6;</code>', $bbc->$funcNam('[code]x + y = 6;[/code]'));
		//Bug #1258: Extra tags rendered with faulty BBCode
		$this->assertEquals(
            '<span style="font-family:Verdana"><span style="color:red">my name NeverMind!</span></span>',
            $bbc->$funcNam('[font=Verdana][color=red]my name NeverMind![/font][/color]')
        );
        //Bug #1979: Whitespaces in attribute are breaking it
        $this->assertEquals(
            '<span style="font-family:Comic Sans MS">txt</span>',
            $bbc->$funcNam('[font=Comic Sans MS]txt[/font]')
        );
        //Bug #4844: Arbitrary HTML injection
        $this->assertEquals(
            '<div style="text-align:foo&quot;&gt;&lt;script&gt;alert(\'JavaScript_Enabled\');&lt;/script&gt;"></div>',
            $bbc->$funcNam('[align=foo"><script>alert(\'JavaScript_Enabled\');</script>][/align]')
        );
	}



    /**
    *   An empty <li> had been included for the first space
    */
    function testBug11400()
    {
        $bbc = new HTML_BBCodeParser(array('filters' => ''));
        $bbc->addFilter('Lists');

        //this works
        $this->assertEquals('<ul><li>one</li><li>two</li></ul>',
              $bbc->qparse("[ulist][*]one[*]two[/ulist]")
        );
        //this not
        $this->assertEquals('<ul><li>one</li><li>two</li></ul>',
              $bbc->qparse("[ulist] [*]one[*]two[/ulist]")
        );
        //this not
        $this->assertEquals('<ol><li>one</li><li>two</li></ol>',
              $bbc->qparse("[list] [*]one[*]two[/list]")
        );
    }



    /**
    *   img tags didn't like = in url
    */
    function testBug11370()
    {
        $bbc = new HTML_BBCodeParser(array('filters' => ''));
        $bbc->addFilter('Images');

        $this->assertEquals('<img src="admin.php?fs=image" />',
              $bbc->qparse("[img]admin.php?fs=image[/img]")
        );
    }
}

//Run tests if run from the command line
if (realpath($_SERVER['PHP_SELF']) == __FILE__){
	$suite = new PHPUnit_TestSuite('BBCodeParser_TestCase');
	$result = PHPUnit::run($suite);
	echo $result->toString();
}
?>