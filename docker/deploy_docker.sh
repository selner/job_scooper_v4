#!/bin/bash
echo off

if [ -z $CODEFRESH_API_KEY ]; then
	echo "Missing required environment variable CODEFRESH_API_KEY."
	exit
fi

DCOMP_VERBOSE=""
# Uncomment this line to turn on verbose output for all docker-compose calls
# DCOMP_VERBOSE=" --verbose "

DEPLOY_STACK=$1

if [ -z ${1} ]; then
    DEPLOY_STACK="codefresh"
fi

DOCKCOMP_STACK_FILE="docker-compose.$DEPLOY_STACK.yml"
DCOMP_PARAMS="$DCOMP_VERBOSE -f docker-compose.yml -f $DOCKCOMP_STACK_FILE"

if [ ! -f "$DOCKCOMP_STACK_FILE" ]; then
    echo "Unable to find override file $DOCKCOMP_STACK_FILE.  Aborting."
    exit
fi

if [ -f "./docker-compose.override.yml" ]; then
    DCOMP_PARAMS="$DCOMP_PARAMS -f ./docker-compose.override.yml"
fi

echo "****************************************************************"
echo ""
echo "          DEPLOYING:  $DEPLOY_STACK"
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
echo "DEPLOY_STACK=$DEPLOY_STACK" >> .env

echo "........................................."
echo "   Docker .env variables are set to: "
echo ""
cat .env
echo "........................................."
echo ""

echo "***************************************************************"
echo ""
echo "Removing any previous Docker containers"
echo ""
echo "***************************************************************"

docker-compose $DCOMP_PARAMS config

docker-compose $DCOMP_PARAMS down --remove-orphans

if [ $DEPLOY_STACK == 'dev' ]; then
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
docker-compose $DCOMP_PARAMS up -d --remove-orphans

docker-compose ps

