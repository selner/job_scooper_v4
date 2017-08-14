#!/usr/bin/env bash
PROPEL=`pwd`"/vendor/bin/propel"
ROOTDIR=`pwd`
CONFIGDIR=`pwd`"/config"
NOW=$(date "+%F-%H-%M-%S")

cd $CONFIGDIR
mv "jobscooper_db.sq3" "jobscooper_db.sq3-"$NOW

$PROPEL config:convert -vvv
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv
