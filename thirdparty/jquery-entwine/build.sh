#!/bin/sh

# Specify the output file's name
FILE="dist/jquery.entwine-dist.js"

mkdir -p dist
rm dist/*.js

echo "/* jQuery.Entwine - Copyright 2009-2011 Hamish Friedlander and SilverStripe. Version $VER. */" > $FILE

for x in \
	vendor/jquery.selector/jquery.class.js \
	vendor/jquery.selector/jquery.selector.js \
	vendor/jquery.selector/jquery.selector.specifity.js \
	vendor/jquery.selector/jquery.selector.matches.js \
	src/jquery.selector.affectedby.js \
	src/jquery.focusinout.js \
	src/jquery.entwine.js \
	src/domevents/jquery.entwine.domevents.addrem.js \
	src/domevents/jquery.entwine.domevents.maybechanged.js \
	src/jquery.entwine.events.js \
	src/jquery.entwine.eventcapture.js \
	src/jquery.entwine.ctors.js \
	src/jquery.entwine.addrem.js \
	src/jquery.entwine.properties.js \
	src/jquery.entwine.legacy.js
do \
  echo >> $FILE
  echo "/* $x */" >> $FILE
  echo >> $FILE
  cat $x >> $FILE
  echo ';' >> $FILE
  echo >> $FILE
done

cp $FILE "dist/jquery.concrete-dist.js"

# cp LICENSE /tmp/
# cp $FILE /tmp/

# git checkout dist
# mv /tmp/$FILE .
# mv /tmp/LICENSE .

# git add $FILE
# git add LICENSE
# git commit -m "Update dist to master version $VER"

# git checkout master
