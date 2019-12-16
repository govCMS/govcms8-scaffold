#!/usr/bin/env bash

##
# This emulates the process of locking/unlocked files in gitlab.
#

ACTION=${1:-"missing"}
if [[ ${ACTION} = "missing" ]] ; then
  echo "Pass a lock or unlock"
  exit 1
fi

# Saas lock
if [[ ${ACTION} = "lock" ]] ; then
  git lfs lock composer.json
  git lfs lock .gitlab-ci.yml
  git lfs lock docker-compose.yml
  git lfs lock .ahoy.yml
  git lfs lock .env.default
  exit 0
fi

# All unlock
if [[ ${ACTION} = "unlock" ]] ; then
  git lfs unlock .ahoy.yml
  git lfs unlock .env.default
  git lfs unlock .gitlab-ci.yml
  git lfs unlock .lagoon.yml
  git lfs unlock .version.yml
  git lfs unlock README.md
  git lfs unlock docker-compose.yml
  exit 0
fi
