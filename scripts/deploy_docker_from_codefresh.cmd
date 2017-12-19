@Echo off
IF %~1.==. SET BRANCH=use-propel-orm
IF NOT %~1.==. SET BRANCH=%1

IF %CODEFRESH_API_KEY%.==. (
	ECHO "Missing required environment variable CODEFRESH_API_KEY."
	goto s_error
)


REM "***************************************************************"
REM ""
REM "Setting variable parameters to use during this script"
REM ""
REM "***************************************************************"

SET CONTAINER_NAME=jobs-%BRANCH%
SET DOCKER_REPO_IMAGE=r.cfcr.io/bryanselner/selner/js4-%BRANCH%:latest
echo Branch is %BRANCH%.
echo Container name is %CONTAINER_NAME%.
echo Docker image source is %DOCKER_REPO_IMAGE%.


ECHO "***************************************************************"
ECHO ""
ECHO "Logging into Codefresh private docker repository..."
ECHO ""
ECHO "***************************************************************"
docker login r.cfcr.io -u bryanselner -p %CODEFRESH_API_KEY%

ECHO "***************************************************************"
ECHO ""
ECHO "Removing any previous Docker container %CONTAINER_NAME%"
ECHO ""
ECHO "***************************************************************"
docker rm -f %CONTAINER_NAME%

ECHO "***************************************************************"
ECHO ""
ECHO "Pulling most recent Docker image from %DOCKER_REPO_IMAGE%"
ECHO ""
ECHO "***************************************************************"
docker pull %DOCKER_REPO_IMAGE%

ECHO "***************************************************************"
ECHO ""
ECHO "Starting docker container %CONTAINER_NAME%"
ECHO ""
ECHO "***************************************************************"
docker run --volume C:\var\local\jobs_scooper:/var/local/jobs_scooper --volume C:\var\local\jobs_scooper:/private/var/local/jobs_scooper --volume %JOBSCOOPER_PROPEL_INI%:/private/var/local/jobs_scooper/configs/propel.ini -e "NLTK_DATA=/private/var/local/jobs_scooper/nltk_data" -e "JOBSCOOPER_OUTPUT=/private/var/local/jobs_scooper/output" --hostname %COMPUTERNAME%_docker --name %CONTAINER_NAME% -d %DOCKER_REPO_IMAGE%

POPD
docker logs -f %CONTAINER_NAME%

:s_error


