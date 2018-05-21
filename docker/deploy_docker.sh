#!/bin/bash
echo off

if [ -z $CODEFRESH_API_KEY ]; then
	echo "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
fi


COMPOSE_OVERRIDE=$1

if [ -z ${1} ]; then
    COMPOSE_OVERRIDE="codefresh"
fi

DOCKCOMP_OVERRIDE_FILE="docker-compose.$COMPOSE_OVERRIDE.yml"
DCOMP_PARAMS="-f docker-compose.yml -f $DOCKCOMP_OVERRIDE_FILE"

if [ ! -f "$DOCKCOMP_OVERRIDE_FILE" ]; then
    echo "Unable to find override file $DOCKCOMP_OVERRIDE_FILE.  Aborting."
    exit
fi

echo "****************************************************************"
echo ""
echo "          DEPLOYING:  $COMPOSE_OVERRIDE"
echo ""
echo "****************************************************************"
echo ""


##
# Remove the old values from any .env file
##
if [ ! -f ".env" ]; then
    if [ -f "../.env" ]; then
        cp ../.env ./.env
    fi
fi


sed '/JS_DEPLOY_DATE/ d' .env > envtmp1
sed '/GIT_COMMIT_HASH/ d' envtmp1 > envtmp2
sed '/BRANCH/ d'  envtmp2 > .env
rm envtmp*

echo "JS_DEPLOY_DATE=$(date +%s)" >> .env
echo "GIT_COMMIT_HASH=$(echo $(git rev-parse --short HEAD) | tr '[A-Z]' '[a-z]')" >> .env
echo "BRANCH=$(echo $(git symbolic-ref --short HEAD))" >> .env
echo "BRANCH_LC=$(echo $(git symbolic-ref --short HEAD) | tr '[A-Z]' '[a-z]')" >> .env

echo "Docker .env variables are set to: "
cat .env


echo "***************************************************************"
echo ""
echo "Removing any previous Docker containers"
echo ""
echo "***************************************************************"

docker-compose $DCOMP_PARAMS config

docker-compose $DCOMP_PARAMS down --remove-orphans

if [ $COMPOSE_OVERRIDE == 'dev' ]; then
    echo "Building images..."
    docker-compose $DCOMP_PARAMS build
fi

echo "***************************************************************"
echo ""
echo "Logging into Codefresh private docker repository..."
echo ""
echo "***************************************************************"
echo "Logging into repo..."
docker login r.cfcr.io -u bryanselner -p $CODEFRESH_API_KEY

echo "Pulling repo images..."
docker-compose $DCOMP_PARAMS pull

echo "Starting stack up in background..."
docker-compose $DCOMP_PARAMS up -d

docker-compose ps -a

