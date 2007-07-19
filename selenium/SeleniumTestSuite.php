<?php

class SeleniumTestSuite extends Controller {
	function index() {
		echo <<<HTML
<html>
<head>
<meta content="text/html; charset=ISO-8859-1"
http-equiv="content-type">
<title>Test Suite</title>
</head>

<body>


    <table id="suiteTable"    cellpadding="1"
           cellspacing="1"
           border="1">
        <tbody>

            <tr><td><b>Test Suite</b></td></tr>
		<tr><td><a href="../cms/tests/LoginTest.html">LoginTest</a></td></tr>
		<tr><td><a href="../cms/tests/SaveAndLoadTest.html">SaveAndLoadTest</a></td></tr>

        </tbody>
    </table>

</body>
</html>
HTML;
	}
	
}

?>