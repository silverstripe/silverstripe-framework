URL=`./cli-script.php SapphireInfo/baseurl`

test: windmill

windmill:
	functest ../cms/tests/test_windmill url=${URL}admin browser=firefox
