#!/usr/bin/env bash
PROPEL=`pwd`"/vendor/bin/propel"
ROOTDIR=`pwd`
CONFIGDIR=`pwd`"/config"
NOW=$(date "+%F-%H-%M-%S")

cd $CONFIGDIR
mv "jobscooper_db.sq3" "jobscooper_db.sq3.backup-"$NOW
rm -Rf ./generated-classes/Base
rm -Rf ./generated-classes/Map
cp -R ./generated-classes "./generated-classes.backup-"$NOW

$PROPEL config:convert -vvv
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv
