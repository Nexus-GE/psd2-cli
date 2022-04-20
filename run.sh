#!/bin/bash
docker run -it --rm --name php8-running-script -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.0-cli php psd2 $*
