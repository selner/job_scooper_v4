#!/usr/bin/env bash
PROPEL=`pwd`"/vendor/bin/propel"
ROOTDIR=`pwd`
CONFIGDIR=`pwd`"/config"
OUTDIR=`echo $JOBSCOOPER_OUTPUT`
NOW=$(date "+%F-%H-%M-%S")

echo "Moving current db to backup ($OUTDIR/job_scooper_db.sq3)..."
mv "$OUTDIR/job_scooper_db.sq3" "$OUTDIR/job_scooper_db.sq3.backup-"$NOW
cd $CONFIGDIR
rm -Rf ./generated-classes/Base
rm -Rf ./generated-classes/Map
cp -R ./generated-classes "./generated-classes.backup-"$NOW

$PROPEL config:convert -vvv
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv

cd ..
composer dump