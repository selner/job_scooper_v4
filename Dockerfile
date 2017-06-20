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
    php5-xsl

#######################################################
##
## Install Composer
##
#######################################################

# Install Composer and make it available in the PATH
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

## Display version information.
RUN composer --version


########################################################
##
## TODO:  Install PHP XDebug
##
########################################################
#
# RUN pecl install xdebug
#
# RUN docker-php-ext-enable xdebug
#
# EXPOSE 9000
#
# RUN echo "zend_extension=/usr/lib/php5/20131226/xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.default_enable = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_autostart = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_port=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_log=/var/log/xdebug_remote.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_connect_back = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.profiler_enable = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
# RUN echo "xdebug.remote_host = 192.168.24.202" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

########################################################
##
## Configure SSH for Github repos
##
## Copy the SSH keys you will use into ./sshkeys and
## rename them to docker_rsa and docker_rsa.pub.
##
## Note:  ./sshkeys/* is excluded in .gitignore so those
##        keys will never get committed to github.
##
## Learn more at https://help.github.com/articles/connecting-to-github-with-ssh/.
##
########################################################

# Make ssh dir
RUN mkdir /root/.ssh/

# Copy over private key, and set permissions
ADD sshkeys/docker_rsa /root/.ssh/docker_rsa
ADD sshkeys/docker_rsa.pub /root/.ssh/docker_rsa.pub

RUN chmod 600 /root/.ssh/docker_*

RUN echo "Host github.com\n\tStrictHostKeyChecking no\n" >> /root/.ssh/config
RUN echo "IdentityFile /root/.ssh/docker_rsa" >> /etc/ssh/ssh_config

# Create known_hosts
RUN touch /root/.ssh/known_hosts

# Add github (or your git server) fingerprint to known hosts
RUN ssh-keyscan -t rsa github.com >> /root/.ssh/known_hosts

# Used only for debugging SSH issues with github
# RUN ssh -Tv git@github.com



########################################################
##
## Set up data volume for job output that will be
## mapped to the local hard drive of the actual PC
##
########################################################
VOLUME "/var/local/jobs_scooper"


########################################################
##
## Clone the github source repo to the container
## and install the dependencies
##
########################################################

WORKDIR /opt/jobs_scooper
RUN git clone https://github.com/selner/job_scooper_v4.git /opt/jobs_scooper

##
## To use local source files, comment out the previous line and
## use these lines instead
##
# ADD . /opt/jobs_scooper
# RUN rm /opt/jobs_scooper/src/*.lock
# RUN rm -Rf /opt/jobs_scooper/src/vendor
RUN ls /opt/jobs_scooper/src

WORKDIR /opt/jobs_scooper/src


########################################################
##
## Install PHP dependencies
##
########################################################
RUN composer install --no-interaction


########################################################
##
## Install python dependencies
##
########################################################
RUN pip install --no-cache-dir -v -r /opt/jobs_scooper/src/python/pyJobNormalizer/requirements.txt


########################################################
##
## Run job_scooper for a given config
##
########################################################

ENTRYPOINT php runJobs.php -ini /var/local/jobs_scooper/configs/evan/job_scooper_config.ini -all -days 1 --output /var/local/jobs_scooper/output -notify=1 -stages=1,2,3,4
