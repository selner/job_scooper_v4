#!/bin/bash

echo off

if [ -z $CODEFRESH_API_KEY ]; then
	echo "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
)

curl https://raw.githubusercontent.com/selner/job_scooper_v4/update_user_search_model/docker-compose.yml > docker-compose.yml

echo "Docker .env variables are set to: "
cat .env

echo "***************************************************************"
echo ""
echo "Logging into Codefresh private docker repository..."
echo ""
echo "***************************************************************"
docker login r.cfcr.io -u bryanselner -p %CODEFRESH_API_KEY%

echo "***************************************************************"
echo ""
echo "Removing any previous Docker containers"
echo ""
echo "***************************************************************"
docker-compose down --remove-orphans

docker-compose pull

docker-compose up -d

docker-compose logs -f


