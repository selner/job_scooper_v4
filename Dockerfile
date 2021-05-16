####################################################################################
#
#  Adapted from the great work at https://github.com/elecena/python-php
#  by elecena.pl (c) 2015-2021
#
####################################################################################
#
#######################################################
#
#  Install Python as base image
#
######################################################## @see https://hub.docker.com/_/python/
FROM python:3.9-buster
#------------------------------------------------------
#
# Install python base tools
#
RUN pip install virtualenv && rm -rf /root/.cache

#######################################################
#
#   Install Debian Packages
#
#######################################################

#------------------------------------------------------
#
# Add repo for Debian packages on Buster
#
RUN echo "deb http://ftp.de.debian.org/debian buster-backports main " > /etc/apt/sources.list.d/buster-backports.list

RUN apt-get update

#------------------------------------------------------
#
# Install required packages
#
RUN apt-get install -y \
    wget \
    ssh \
    git-all \
    apt-transport-https \
    apt-utils \
    lsb-release \
    ca-certificates

#######################################################
#
#    Install PHP
#
#######################################################

#------------------------------------------------------
#
# Add PHP Sources
#
# @see https://www.noobunbox.net/serveur/auto-hebergement/installer-php-7-1-sous-debian-et-ubuntu
#
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update

#------------------------------------------------------
#
# Install PHP8
#

RUN  apt-get -y install \
  php8.0

#------------------------------------------------------
#
# Install PHP8 extensions
#

RUN apt-get -y install \
  php8.0-curl 		\
  php8.0-dev 		\
  php8.0-gd 		\
  php8.0-intl 		\
  php8.0-mbstring	\
  php8.0-mcrypt     \
  php8.0-xsl		\
  php8.0-yaml		\
  php8.0-zip		\
  php8.0-xdebug		\
  libapache2-mod-php8.0


#------------------------------------------------------
#
# Install Composer
#
RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer



######################################################
###
### Configure SSH for Github Repos
###
######################################################

#------------------------------------------------------
#
# Copy the SSH keys you will use into ./sshkeys and
# rename them to docker_rsa and docker_rsa.pub.
#
# Note:  ./sshkeys/* is excluded in .gitignore so those
#        keys will never get committed to github.
#
# Learn more at https://help.github.com/articles/connecting-to-github-with-ssh/.

RUN mkdir /root/.ssh/

ARG SSHKEY_DIR=./configs/sshkeys

# Copy over private key, and set permissions
# Copy over private key, and set permissions
ADD ${SSHKEY_DIR}/id_rsa_github /root/.ssh/id_rsa_github
ADD ${SSHKEY_DIR}/id_rsa_github.pub /root/.ssh/id_rsa_github.pub

RUN chmod 600 /root/.ssh/*

RUN echo "Host github.com\n\tStrictHostKeyChecking no\n" >> /root/.ssh/config
RUN echo "Include ./hosts/*"  >> /root/.ssh/config
RUN mkdir /root/.ssh/hosts
RUN echo "IdentityFile /root/.ssh/id_rsa_github" >> /root/.ssh/hosts/github

# Create known_hosts
RUN touch /root/.ssh/known_hosts

# Add github (or your git server) fingerprint to known hosts
### BUGBUG
RUN ssh-keyscan -t rsa github.com >> /root/.ssh/known_hosts

# Used only for debugging SSH issues with github
# RUN ssh -Tv git@github.com



########################################################
###
### Set up data volume for job output that will be
### mapped to the local hard drive of the actual PC
###
########################################################

VOLUME "/var/local/job_scooper"
VOLUME "/root/nltk_data"


########################################################
###
### Clone the github source repo to the container
### and install the dependencies
###
########################################################

WORKDIR /app/job_scooper
ARG BRANCH=2021_resurrection
RUN echo "Using ${BRANCH} branch of job_scooper_v4"
ARG CACHEBUST=1
RUN git clone https://github.com/selner/job_scooper_v4.git /app/job_scooper -b ${BRANCH}


#########################################################
###
### Install JobScooper's PHP dependencies
###
#########################################################
WORKDIR /app/job_scooper
RUN composer install --no-interaction -vv


#########################################################
####
#### Install JobScooper's python dependencies
####
#########################################################
RUN pip install --no-cache-dir -v -r /app/job_scooper/python/pyJobNormalizer/requirements.txt


#########################################################
#
#   Run job_scooper app
#
#########################################################
WORKDIR /app/job_scooper

CMD [ "php", "./run_job_scooper.php" ]

