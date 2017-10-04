#!/usr/bin/env bash

if [ -z ${1} ]; then
	BRANCH="use-propel-orm"
else
	BRANCH="${1}"
fi

[ -z "$CODEFRESH_API_KEY" ] && echo "Missing required environment variable CODEFRESH_API_KEY." && exit 1;


# "***************************************************************"
# ""
# "Setting variable parameters to use during this script"
# ""
# "***************************************************************"

CONTAINER_NAME=jobs-$BRANCH
DOCKER_IMAGE=selner/js4-$BRANCH
DOCKER_REPO_IMAGE=r.cfcr.io/bryanselner/$DOCKER_IMAGE
echo Branch is $BRANCH.
echo Container name is $CONTAINER_NAME.
echo Docker image source is $DOCKER_REPO_IMAGE.


ECHO "***************************************************************"
ECHO ""
ECHO "Logging into Codefresh private docker repository..."
ECHO ""
ECHO "***************************************************************"
docker login r.cfcr.io -u bryanselner -p $CODEFRESH_API_KEY

ECHO "***************************************************************"
ECHO ""
ECHO "Removing any previous Docker container $CONTAINER_NAME"
ECHO ""
ECHO "***************************************************************"
docker rm -f $CONTAINER_NAME

ECHO "***************************************************************"
ECHO ""
ECHO "Pulling most recent Docker image from $DOCKER_REPO_IMAGE"
ECHO ""
ECHO "***************************************************************"
# docker rmi $imagetag
docker pull $DOCKER_REPO_IMAGE

ECHO "***************************************************************"
ECHO ""
ECHO "Starting docker container $CONTAINER_NAME from image $DOCKER_IMAGE"
ECHO ""
ECHO "***************************************************************"
DOCKER_RUN="docker run --volume /private/var/local/jobs_scooper:/var/local/jobs_scooper -name $CONTAINER_NAME $DOCKER_IMAGE"
echo $DOCKER_RUN
$DOCKER_RUN

docker logs -f "$CONTAINER_NAME"


