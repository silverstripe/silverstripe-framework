#!/bin/sh

# Check for built files, and yell at user if not

# Find source files that are marked as modified
sources=`git status -s | grep 'M.*javascript\/src\/.*'`

# Find built files that have changed (we don't use M because they could be deleted, etc)
built=`git status -s | grep '^M.*javascript\/dist'`

# If source files have changed, but not built files, block the commit
if [ -n "$sources" ] &&  [ -z "$built" ]; then
    echo "
ERROR: CAN'T COMMIT
===================
Build the files using 'npm run build' and add the dist files to the commit,
before committing your change.
"
    exit 1
fi
