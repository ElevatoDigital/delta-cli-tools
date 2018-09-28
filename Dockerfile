FROM composer:latest

#app directory to install delta cli in
RUN mkdir /delta-cli
WORKDIR /delta-cli

#install rsync, cause it is isn't included in the composer image
RUN apk add openssl
RUN apk add rsync

#copy to the container
COPY / /delta-cli/

#install delta cli
RUN composer install

#add delta cli to path
ENV PATH="/delta-cli/bin:${PATH}"

#fix-me: temporary patch
#COPY conf /delta-cli/vendor/deltasystems/delta-cli/library/DeltaCli/ApiClient.php

#volume directory for source code
VOLUME ['/app/src']
#volume directory for db files (deprecated)
VOLUME ['/app/db']
#generated delta api file
VOLUME ['/app/delta-api.json']
#delta cli configuration
VOLUME ['/app/delta-cli.php']
#api key location
VOLUME ['/root/.delta-api.json']

#app directory on the container
WORKDIR /app

#fix-me: this isn't really necessary
#go ahead and copy in delta files if present
#COPY ../ssh-keys /app/ssh-keys
#COPY ../delta-api.json /app/delta-api.json

#the location of the project ssh keys
VOLUME ['/delta-cli-ssh-keys']
