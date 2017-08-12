#!/usr/bin/env bash
PROPEL=`pwd`"/vendor/bin/propel"
ROOTDIR=`pwd`
CONFIGDIR=`pwd`"/config"

cd $CONFIGDIR
$PROPEL config:convert -vvv
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv
