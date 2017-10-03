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
### Create the main source code directory on the image
###
########################################################
RUN mkdir /opt/jobs_scooper

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
ADD ./ /opt/jobs_scooper/

RUN cat /opt/jobs_scooper/bootstrap.php | grep "__APP_VERSION__"RUN ls -al /opt/jobs_scooper########################################################
###
### Add any user files to the image
###
########################################################
RUN ls -al /opt/jobs_scooper/userfilesRUN [ -f /opt/job_scooper/userfiles/scoop_docker.sh ] && echo "Using user-specific version of scoop_docker.sh" || CAT "echo Missing userfiles/scoop_docker.sh script file to run." > /opt/job_scooper/userfiles/scoop_docker.shRUN chmod +x /opt/jobs_scooper/userfiles/*.sh############################################################## Run job_scooper for a given config###########################################################WORKDIR /opt/jobs_scooper# Commenting Out Entry Point for Builds.  Needs to be called from# container start instead now.# CMD bash -C '/opt/jobs_scooper/userfiles/scoop_docker.sh;/bin/bash';'bash'