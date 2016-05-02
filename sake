#!/usr/bin/env bash

# Check for an argument
if [ ${1:-""} = "" ]; then
	echo "SilverStripe Sake

Usage: $0 (command-url) (params)
Executes a SilverStripe command"
	exit 1
fi

# find the silverstripe installation
sakedir=`dirname $0`
if [ -f "$sakedir/cli-script.php" ]; then
	framework="$sakedir"
	base=`dirname $sakedir`
elif [ -f ./cli-script.php ]; then
	framework=.
	base=..
else
	# look up the tree for the first parent folder that has a framework
	# installation
	slashes=${PWD//[^\/]/}
	directory="$PWD"
	base=.
	for (( n=${#slashes}; n>0; --n )) do
		if [ -d "$directory/framework" ]; then
			framework="$directory/framework"

			break
		elif [ -d "$directory/sapphire" ]; then
			framework="$directory/sapphire"

			break
		fi

		directory=`dirname $directory`
		base="$base."
	done

	if [ ! -f "$framework/cli-script.php" ]; then
		echo "Can't find cli-script.php in $framework"
		exit 1
	fi
fi

# Find the PHP binary
for candidatephp in php php5; do
	if [ `which $candidatephp 2>/dev/null` -a -f `which $candidatephp 2>/dev/null` ]; then
		php=`which $candidatephp 2>/dev/null`
		break
	fi
done
if [ "$php" = "" ]; then
	echo "Can't find any php binary"
	exit 2
fi

################################################################################################
## Installation to /usr/bin

if [ "$1" = "installsake" ]; then
	echo "Installing sake to /usr/local/bin..."
	rm -rf /usr/local/bin/sake
	cp $0 /usr/local/bin
	exit 0
fi

################################################################################################
## Process control

if [ "$1" = "-start" ]; then
	if [ "`which daemon`" = "" ]; then
		echo "You need to install the 'daemon' tool.  In debian, go 'sudo apt-get install daemon'"
		exit 1
	fi
	
	if [ ! -f $base/$2.pid ]; then
		echo "Starting service $2 $3"
		touch $base/$2.pid
		pidfile=`realpath $base/$2.pid`
		
		outlog=$base/$2.log
		errlog=$base/$2.err

		echo "Logging to $outlog"
		
		sake=`realpath $0`
		base=`realpath $base`
		
		# if third argument is not explicitly given, copy from second argument
		if [ "$3" = "" ]; then
			url=$2
		else
			url=$3
		fi

		# TODO: Give a globally unique processname by including the projectname as well		
		processname=$2

		daemon -n $processname -r -D $base --pidfile=$pidfile --stdout=$outlog --stderr=$errlog $sake $url
	else
		echo "Service $2 seems to already be running"
	fi
		exit 0
fi

if [ "$1" = "-stop" ]; then
	pidfile=$base/$2.pid
	if [ -f $pidfile ]; then
		echo "Stopping service $2"
		
		# TODO: This is a bad way of killing the process
		kill -KILL `cat $pidfile`
		unlink $pidfile
	else
		echo "Service $2 doesn't seem to be running."
	fi
	exit 0
fi

################################################################################################
## Basic execution

$php $framework/cli-script.php ${*}
