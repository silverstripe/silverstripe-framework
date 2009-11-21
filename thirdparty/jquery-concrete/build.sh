#!/bin/sh

VER=$1

# Get the version - a tag if possible, otherwise a short ref (not well tested code)
if [ "$VER " = " " ] ; then \
	VER=`git rev-parse --abbrev-ref=strict HEAD`
fi
if [ "$VER" = "master" ] ; then \
	VER=`git show --pretty=format:"%h" --quiet`
fi

# Specify the output file's name
FILE="dist/jquery.concrete-$VER.js"

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

ln -s `basename "$FILE"` dist/jquery.concrete-latest.js

# cp LICENSE /tmp/
# cp $FILE /tmp/

# git checkout dist
# mv /tmp/$FILE .
# mv /tmp/LICENSE .

# git add $FILE
# git add LICENSE
# git commit -m "Update dist to master version $VER"

# git checkout master