cd#!/usr/bin/env bash

docker rm -f selenium

docker run -d --name selenium -p 4447:4447 --shm-size 2g --restart=always -e JAVA_OPTS=-Xmx512m -e SE_OPTS="-port 4447" selenium/standalone-firefox:latest

