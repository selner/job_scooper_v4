#!/bin/bash

echo off

DOCKER_RUN_CMD=""
if [ -z ${1} ]; then
	echo "no command set."
else
	DOCKER_RUN_CMD="env bash -C '${1}'"
	echo "Docker run command:  "$DOCKER_RUN_CMD
	export DOCKER_RUN_CMD=$DOCKER_RUN_CMD
fi

if [ -z $CODEFRESH_API_KEY ]; then
	echo "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
fi

cd ..

##
# Remove the old values from any .env file
##
sed '/JS_DEPLOY_DATE/ d' .env > envtmp1
sed '/GIT_COMMIT_HASH/ d' envtmp1 > envtmp2
sed '/BRANCH/ d'  envtmp2 > .env
rm envtmp*

# GIT_COMMIT_HASH_RAW=$(git rev-parse --short HEAD)
#BRANCH=`git symbolic-ref --short HEAD`

echo "JS_DEPLOY_DATE=$(date +%s)" >> .env
echo "GIT_COMMIT_HASH=$(echo $(git rev-parse --short HEAD) | tr '[A-Z]' '[a-z]')" >> .env
echo "BRANCH=$(echo $(git symbolic-ref --short HEAD))" >> .env
echo "BRANCH_LC=$(echo $(git symbolic-ref --short HEAD) | tr '[A-Z]' '[a-z]')" >> .env

echo "Docker .env variables are set to: "
cat .env


echo "***************************************************************"
echo ""
echo "Logging into Codefresh private docker repository..."
echo ""
echo "***************************************************************"
docker login r.cfcr.io -u bryanselner -p $CODEFRESH_API_KEY

echo "***************************************************************"
echo ""
echo "Removing any previous Docker containers"
echo ""
echo "***************************************************************"
docker-compose down --remove-orphans

docker-compose build

docker-compose pull

docker-compose config


docker-compose up -d

docker ps -a


