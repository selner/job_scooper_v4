#!/usr/bin/env bash
SCRIPTSDIR=`pwd`
cd ..
PROJDIR=`pwd`
CODEDIR="$PROJDIR/src"
PROPEL="$PROJDIR/vendor/bin/propel"
CONFIGDIR="$PROJDIR/Config"
OUTDIR=`echo ${JOBSCOOPER_OUTPUT}`
NOW=$(date "+%F-%H-%M-%S")
echo "current dir is `pwd`"

echo "Moving current db to backup ($OUTDIR/job_scooper_db.sq3)..."
mv "$OUTDIR/job_scooper_db.sq3" "$OUTDIR/job_scooper_db.sq3.backup-"$NOW

rm -Rf $CODEDIR/src/DataAccess/Base
rm -Rf $CODEDIR/src/DataAccess/Map
cp -R $CODEDIR/src/DataAccess "$CODEDIR/generated-classes.backup-"$NOW

cd $PROJDIR
echo "Generating Propel runtime configuration from $CONFIGDIR"
$PROPEL config:convert -vvv

echo "Building Propel files starting from $PROJDIR"
CMD="$PROPEL build -vvv"
echo $CMD; $CMD

CMD="$PROPEL sql:build --overwrite -vvv"
echo $CMD; $CMD

CMD="$PROPEL sql:insert -vvv"
echo $CMD; $CMD

cp -f "$OUTDIR/job_scooper_db.sq3" "$CURDIR/examples/job_scooper_db.sq3"

cd $PROJDIR
echo "current dir is " `pwd`

composer dump

cd $SCRIPTSDIR


