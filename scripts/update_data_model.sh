#!/usr/bin/env bash

ROOT=`pwd`
args=$#           # Number of args passed.
ORIGDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJDIR=`pwd`

lastarg=${!args}
if [ -z ${1} ]; then
    CONFIGDIR="$ROOT/Config"
else
    CONFIGDIR=$1
fi

cd ..
PROPEL="$ROOT/vendor/bin/propel"
CODEDIR="$ROOT/src"
OUTDIR=`echo ${JOBSCOOPER_OUTPUT}`
NOW=$(date "+%F-%H-%M-%S")

echo "Copying current db to backup ($OUTDIR/job_scooper_db.sq3.backup-$NOW)..."
cp "$OUTDIR/job_scooper_db.sq3" "$OUTDIR/job_scooper_db.sq3.backup-"$NOW
cd $CONFIGDIR
# rm -Rf ./generated-classes/Base
# rm -Rf ./generated-classes/Map
# cp -R ./generated-classes "./generated-classes.backup-"$NOW


echo "Removing past migration and SQL migration scripts"
rm -Rf "$ROOT/Config/Data/generated-migrations"
rm -Rf "$ROOT/Config/Data/generated-sql"


cd $CONFIGDIR
$PROPEL config:convert -vvv


cd $ROOT
$PROPEL build -vvv
$PROPEL sql:build --overwrite -vvv
$PROPEL migration:diff -vvv
$PROPEL migration:migrate -vvv

mv -f "$OUTDIR/job_scooper_db.sq3" "$OUTDIR/job_scooper_db-migrated.sq3"

$PROPEL sql:build --overwrite -vvv
$PROPEL sql:insert -vvv
cp -f "$OUTDIR/job_scooper_db.sq3" "$CODEDIR/examples/job_scooper_db.sq3"
mv -f "$OUTDIR/job_scooper_db-migrated.sq3" "$OUTDIR/job_scooper_db.sq3"

cd $ROOT
composer dump
cd scripts
