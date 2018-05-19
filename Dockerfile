FROM php:7.2-cli as php

ENV DEBIAN_FRONTEND=noninteractive
ARG GIT_COMMIT_HASH

RUN echo "Git Commit Hash = $GIT_COMMIT_HASH"

#######################################################
##
## Install and update the core package install toolsets
##
#######################################################

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
#    ca-certificates \  ## in base image
    apt-transport-https \
    apt-utils \
    bzip2 \
    git \
    libicu-dev \
    libmcrypt-dev \
    mysql-client \
    openntpd \
    sendmail \
    sqlite3 \
    tzdata \
    vim \
    wget \
    zip


#######################################################
##
## Set the timezone on the image
##
#######################################################
ENV TZ=America/Los_Angeles
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install common PHP packages
#  Possible values for ext-name:
#  bcmath bz2 calendar ctype curl dba dom enchant exif fileinfo filter ftp gd gettext gmp hash iconv imap
#  interbase intl json ldap mbstring mysqli oci8 odbc opcache pcntl pdo pdo_dblib pdo_firebird pdo_mysql
#  pdo_oci pdo_odbc pdo_pgsql pdo_sqlite pgsql phar posix pspell readline recode reflection session shmop
#  simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem sysvshm tidy tokenizer wddx xml xmlreader
#  xmlrpc xmlwriter xsl zend_test zip

RUN docker-php-ext-install \
      bcmath \
      pdo_mysql \
      intl


#######################################################
##
## Install the GD PHP package
## (required for php-spreadsheet)
##
#######################################################

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
        libicu-dev \
        libmcrypt-dev \
        libpq-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev


RUN docker-php-ext-configure gd \
			--with-gd \
			--with-freetype-dir=/usr/include/ \
			--with-png-dir=/usr/include/ \
			--with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable intl


#######################################################
##
## Install the Xdebug PHP package
##
#######################################################
RUN pecl install xdebug-2.6.0 \
                 zip \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-enable zip \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=\"PHPSTORM\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

#######################################################
##
## Install Composer
##
#######################################################
RUN wget https://getcomposer.org/download/1.1.1/composer.phar -O composer \
    && mv composer /usr/bin/composer \
    && chmod +x /usr/bin/composer \
    && composer self-update

#COPY --from=composer:1.5 /usr/bin/composer /usr/bin/composer

# Install Composer and make it available in the PATH
#RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

## Display version information.
RUN composer --version


#######################################################
##
## Install Miniconda with Python 2.7
##
#######################################################

RUN apt-get -qq update && apt-get -qq -y install curl bzip2 \
    && curl -sSL https://repo.continuum.io/miniconda/Miniconda2-latest-Linux-x86_64.sh -o /tmp/miniconda.sh \
    && bash /tmp/miniconda.sh -bfp /usr/local \
    && rm -rf /tmp/miniconda.sh \
    && conda install -y python=2 \
    && conda install -y pip \
    && conda update conda \
    && apt-get -qq -y remove curl bzip2 \
    && apt-get -qq -y autoremove \
    && apt-get autoclean \
    && rm -rf /var/lib/apt/lists/* /var/log/dpkg.log \
    && conda clean --all --yes

ENV PATH /opt/conda/bin:$PATH

#######################################################
##
## Install pip
##
#######################################################
RUN which python
RUN echo PATH=$PATH
RUN python -v


#######################################################
##
## Install Docker exe so we can stop/start Selenium
##
#######################################################
#RUN mkdir /etc/apk/
#RUN echo "http://dl-3.alpinelinux.org/alpine/latest-stable/community/x86_64/" >> /etc/apk/repositories
#
#RUN apt-get install -y \
#    docker
#
#RUN echo "chown -R dev:dev /var/run/docker.sock" >> ~/.bash_profile

########################################################
###
### Download the full nltk data set needed for python
###
########################################################
# You can uncomment the following two lines to have the
# data autoinstalled during the Docker build stage.
#
# Or if you need to install the files in a specific
# location or want to build faster by skipping this
# step, install the data files manually and set the
# NLTK_DATA variable in .env to point to the data folder.
#
# Install instructions at http://www.nltk.org/data.html
# RUN pip install --no-cache-dir -v nltk
# RUN python -m nltk.downloader -d /root/nltk_data all

#RUN curl -fsSLO https://get.docker.com/builds/Linux/x86_64/docker-17.03.1-ce.tgz33 && \
#tar --strip-components=1 -xvzf docker-17.03.1-ce.tgz -C /usr/local/bin
#

#######################################################
##
## Download & build the math extensions for SQLite
## that we need for geospatial queries
##
#######################################################
##
### Uncomment these lines if you are using SQLite
##
# RUN mkdir /opt/sqlite
# RUN mkdir /opt/sqlite/extensions
# RUN echo "Downloading and compiling SQLite3 math extensions..."
# RUN wget https://www.sqlite.org/contrib/download/extension-functions.c?get=25 -O /opt/sqlite/extensions/extension-functions.c
# RUN gcc -fPIC -lm -shared /opt/sqlite/extensions/extension-functions.c -o /opt/sqlite/extensions/libsqlitefunctions.so
# ADD ./Config/etc/30-pdo_sqlite_ext.ini /etc/php5/cli/conf.d/30-pdo_sqlite_ext.ini

########################################################
###
### Set up data volume for job output that will be
### mapped to the local hard drive of the actual PC
###
########################################################
VOLUME "/var/local/jobs_scooper"

########################################################
###
### Create the main source code directory structure on
### the image
###
########################################################
RUN mkdir /opt/jobs_scooper

########################################################
###
### Add the PHP composer configuration file into image
### and install the dependencies
###
########################################################
RUN ls -al /usr/bin
RUN docker-php-ext-enable zip

WORKDIR /opt/jobs_scooper
ADD ./composer.json /opt/jobs_scooper/
RUN composer install --no-interaction -vv

########################################################
###
### Install python dependencies
###
########################################################
ADD ./python/pyJobNormalizer/requirements.txt /opt/jobs_scooper/python/pyJobNormalizer/requirements.txt
RUN pip install --no-cache-dir -v -r /opt/jobs_scooper/python/pyJobNormalizer/requirements.txt


# Dockerfile
# add this and below command will run without cache
ARG GIT_COMMIT_HASH

########################################################
###
### Add the full, remaining source code from the repo
### to the image
###
########################################################
RUN echo "Adding all source files from `pwd` to /opt/jobs_scooper"
ADD ./ /opt/jobs_scooper/

RUN echo "Verifying correct source installed..."
RUN ls -al /opt/jobs_scooper

########################################################
###
### Run the user's start_jobscooper.sh script in the
### local shared volume for results and config data
###
########################################################
WORKDIR /opt/jobs_scooper
CMD ["php", "run_job_scooper.php"]