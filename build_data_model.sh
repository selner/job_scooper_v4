#!/usr/bin/env bash
PROPEL=`pwd`"/vendor/bin/propel"
ROOTDIR=`pwd`
CONFIGDIR=`pwd`"/config"

cd $CONFIGDIR
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv
$PROPEL config:convert -vvv

cd $ROOTDIR
#$PROPEL sql:build -vvv --overwrite

