#!/bin/bash

echo off

if [ -z $CODEFRESH_API_KEY ]; then
	echo "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
fi

echo "Docker .env variables are set to: "
cat .env
BRANCH=`git symbolic-ref --short HEAD`
echo "Branch is $BRANCH"

curl https://raw.githubusercontent.com/selner/job_scooper_v4/$BRANCH/docker-compose.yml > docker-compose.yml

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

docker-compose pull

docker-compose up -d

docker-compose logs -f


