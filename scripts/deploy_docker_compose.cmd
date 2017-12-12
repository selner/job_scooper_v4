@Echo off

IF %CODEFRESH_API_KEY%.==. (
	ECHO "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
)

curl https://raw.githubusercontent.com/selner/job_scooper_v4/update_user_search_model/docker-compose.yml > docker-compose.yml

echo "Docker .env variables are set to: "
cat .env

ECHO "***************************************************************"
ECHO ""
ECHO "Logging into Codefresh private docker repository..."
ECHO ""
ECHO "***************************************************************"
docker login r.cfcr.io -u bryanselner -p %CODEFRESH_API_KEY%

ECHO "***************************************************************"
ECHO ""
ECHO "Removing any previous Docker containers"
ECHO ""
ECHO "***************************************************************"
docker-compose down --remove-orphans

docker-compose pull

docker-compose up -d

docker-compose logs -f

REM POPD
REM docker logs -f %CONTAINER_NAME%

:s_error


