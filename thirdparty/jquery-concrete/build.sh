#!/bin/sh

# Specify the output file's name
FILE="dist/jquery.concrete-dist.js"

mkdir -p dist
rm dist/*.js

echo "/* jQuery.Concrete - Copyright 2009 Hamish Friedlander and SilverStripe. Version $VER. */" > $FILE

for x in \
	vendor/jquery.selector/jquery.class.js \
	vendor/jquery.selector/jquery.selector.js \
	vendor/jquery.selector/jquery.selector.specifity.js \
	vendor/jquery.selector/jquery.selector.matches.js \
	src/jquery.focusinout.js \
	src/jquery.concrete.js \
	src/jquery.concrete.dommaybechanged.js \
	src/jquery.concrete.events.js \
	src/jquery.concrete.ctors.js \
	src/jquery.concrete.properties.js
do \
  echo >> $FILE
  echo "/* $x */" >> $FILE
  echo >> $FILE
  cat $x >> $FILE
  echo ';' >> $FILE
  echo >> $FILE
done

# cp LICENSE /tmp/
# cp $FILE /tmp/

# git checkout dist
# mv /tmp/$FILE .
# mv /tmp/LICENSE .

# git add $FILE
# git add LICENSE
# git commit -m "Update dist to master version $VER"

# git checkout master