#!/bin/bash

if [ -z ${1} ]; then
	BRANCH="master"
else
	BRANCH="${1}"
fi

JOBSNAME="jobs-"$BRANCH
IMAGETAG="selner/js4-"$BRANCH
echo "Branch is "$BRANCH"."
echo "Container name is "$JOBSNAME"."
echo "Image tag is "$IMAGETAG"."


docker rm -f $JOBSNAME
docker rmi $IMAGETAG

docker build --build-arg BRANCH=$BRANCH -t $IMAGETAG . 

#
# To use on macos or linux:
#     1.  change the PC's volume path to be "/Users/bryan/Dropbox/var-jobs_scooper:/var/local/jobs_scooper --volume /devcode/nltk_data:/root/nltk_data" style instead
#     2.  save as a .sh file
#
docker run --volume C:\Users\bryan\Dropbox\var-local-jobs_scooper:/var/local/jobs_scooper --volume c:\dev\nltk_data:/root/nltk_data --name $JOBSNAME -d $IMAGETAG

docker logs -f $JOBSNAME


