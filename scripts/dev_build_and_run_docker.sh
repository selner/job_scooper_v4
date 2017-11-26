#!/bin/bash
BRANCH="propel-use-didom"
JOBSNAME="jobs-"$BRANCH
IMAGETAG="selner/js4-"$BRANCH
DOCKERHOSTNAME=$(hostname)"_docker"

echo "Branch is "$BRANCH"."
echo "Container name is "$JOBSNAME"."
echo "Image tag is "$IMAGETAG"."
docker rm -f $JOBSNAME
# docker rmi $IMAGETAG
cd ..

ECHO "***************************************************************"
ECHO ""
ECHO "Building Image"
ECHO ""
ECHO "***************************************************************"
docker build -t $IMAGETAG .

# To use on macos or linux:
#     1.  change the PC's volume path to be "/Users/bryan/Dropbox/var-jobs_scooper:/var/local/jobs_scooper --volume /devcode/nltk_data:/root/nltk_data" style instead
#     2.  save as a .sh file
#
ECHO "***************************************************************"
ECHO ""
ECHO "Starting Container $JOBSNAME"
ECHO ""
ECHO "***************************************************************"
VARLOCAL=/var/local
if [ "$(uname)" == "Darwin" ]; then
    # Do something under Mac OS X platform
    VARLOCAL=/private/var/local
# elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    # Do something under GNU/Linux platform
# elif [ "$(expr substr $(uname -s) 1 10)" == "MINGW32_NT" ]; then
    # Do something under 32 bits Windows NT platform
# elif [ "$(expr substr $(uname -s) 1 10)" == "MINGW64_NT" ]; then
    # Do something under 64 bits Windows NT platform
fi

CMD="docker run --volume $VARLOCAL/jobs_scooper/:/var/local/jobs_scooper/ --volume /var/run/docker.sock:/var/run/docker.sock --name $JOBSNAME -d $IMAGETAG"
echo $CMD
$CMD

docker logs -f $JOBSNAME
