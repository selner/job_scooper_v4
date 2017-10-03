FROM python:2.7

ENV DEBIAN_FRONTEND=noninteractive

#######################################################
##
## Install and update the core package install toolsets
##
#######################################################

RUN apt-get update

RUN apt-get install -y \
    curl \
    wget \
    zip \
    ca-certificates

RUN apt-get install -y \
    apt-transport-https \
    apt-utils

#######################################################
##
## Install pip
##
#######################################################
RUN which python
RUN echo PATH=$PATH

########################################################
##
## Install PHP5.6 Packages
##
#######################################################


RUN apt-get update && apt-get install -y \
    php5-cli \
    php5-dev \
    php-pear \
    php5-curl \
    php5-gd \
    php5-intl \
    php5-mcrypt \
    php5-xsl \
    php5-sqlite



#######################################################
##
## Install Composer
##
#######################################################

# Install Composer and make it available in the PATH
RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

## Display version information.
RUN composer --version

########################################################
###
### Set up data volume for job output that will be
### mapped to the local hard drive of the actual PC
###
########################################################
VOLUME "/var/local/jobs_scooper"
VOLUME "/root/nltk_data"

########################################################
###
### Create the main source code directory structure on
### the image
###
########################################################
RUN mkdir /opt/jobs_scooper
RUN mkdir /opt/jobs_scooper/userfiles

########################################################
###
### Add the PHP composer configuration file into image
### and install the dependencies
###
########################################################
WORKDIR /opt/jobs_scooper
ADD composer.json /opt/jobs_scooper
RUN composer install --no-interaction -vv

########################################################
###
### Install python dependencies
###
########################################################
ADD ./python/pyJobNormalizer/requirements.txt /opt/jobs_scooper/python/pyJobNormalizer/requirements.txt
RUN pip install --no-cache-dir -v -r /opt/jobs_scooper/python/pyJobNormalizer/requirements.txt

########################################################
###
### Add the full, remaining source code from the repo
### to the image
###
########################################################
RUN echo "Adding all source files from `pwd` to /opt/jobs_scooper"
ADD ./ /opt/jobs_scooper/

RUN echo "Verifying correct source installed..."
RUN cat /opt/jobs_scooper/bootstrap.php | grep "__APP_VERSION__"
RUN ls -al /opt/jobs_scooper

########################################################
###
### Verify we have a scoop_docker.sh file to execute;
### generate one if not.
###
########################################################
# BUGBUG:  this is still not functional so commenting out for the time being
# RUN echo "Looking for user-specific scoop_docker.sh file to start container with..."
# RUN ls -al /opt/jobs_scooper/userfiles
# 
# RUN [ -f /opt/job_scooper/userfiles/scoop_docker.sh ] && echo "Successfully found user-specific version of scoop_docker.sh"
# RUN [ ! -f $USERFILES/scoop_docker.sh ] && MISSINGMSG="Missing `pwd`/userfiles/scoop_docker.sh script file to run.  Created placeholder file."; echo $MISSINGMSG > $USERFILES/scoop_docker.sh; echo $MISSINGMSG
# 
RUN chmod +x /opt/jobs_scooper/userfiles/*.sh

########################################################
###
### Run job_scooper via the userfiles/scoop_docker.sh file
###
########################################################
WORKDIR /opt/jobs_scooper

# Commenting Out Entry Point for Builds.  Needs to be called from
# container start instead now.
CMD bash -C '/opt/jobs_scooper/userfiles/scoop_docker.sh;';'bash'
