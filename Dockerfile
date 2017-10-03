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
### Run the user's start_jobscooper.sh script in the 
### local shared volume for results and config data
###
########################################################
WORKDIR /opt/jobs_scooper
CMD bash -C '/var/local/jobs_scooper/start_jobscooper.sh;';'bash'
