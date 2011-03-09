#!/bin/bash
while getopts  "j:p:" flag
do
  if [ $flag == "j" ]; then
    JSTD=$OPTARG
  elif [ $flag == "p" ]; then
    PORT=$OPTARG
  fi
done

TESTPATH=`dirname $0`

if [ -z "$PORT" ]; then
	PORT=9876
fi

if [ -z "$JSTD" ]; then
	# Download test driver dependency if required (too large to include in core)
	JSTD="$TESTPATH/JsTestDriver-1.3.1.jar"
	if [ ! -f $JSTD ]; then
		wget -O $JSTD http://js-test-driver.googlecode.com/files/JsTestDriver-1.3.1.jar
	fi
fi

echo "####################################################"
echo "Please capture a browser by visiting http://localhost:$PORT"
echo "Run tests by executing ./tests/javascript/test.sh"
echo "####################################################"

java -jar $JSTD --port $PORT --config "$TESTPATH/jstestdriver.conf"