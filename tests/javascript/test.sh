#!/bin/bash
while getopts  "j:t:" flag
do
  if [ $flag == "j" ]; then
    JSTD=$OPTARG
  elif [ $flag == "t" ]; then
    TESTS=$OPTARG
  fi
done

TESTPATH=`dirname $0`

if [ -z "$JSTD" ]; then
	# Download test driver dependency if required (too large to include in core)
	JSTD="$TESTPATH/JsTestDriver-1.3.1.jar"
	if [ ! -f $JSTD ]; then
		wget -O $JSTD http://js-test-driver.googlecode.com/files/JsTestDriver-1.3.1.jar
	fi
fi

if [ -z "$TESTS" ]; then
  TESTS="all"
  echo "Running all tests"
else
  echo "Running '$TESTS'"
fi

java -jar $JSTD --reset --config "$TESTPATH/jstestdriver.conf" --tests "$TESTS"
