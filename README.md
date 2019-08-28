# GovCMS8 Project Scaffolding

## Known Issues

* This repository is still a Work-in-Progress, and may be subject to slight alterations

## Requirements and Preliminary Setup

* [Docker](https://docs.docker.com/install/) - Follow documentation at https://docs.amazee.io/local_docker_development/local_docker_development.html to configure local development environment.

* [Mac/Linux](https://docs.amazee.io/local_docker_development/pygmy.html) - Make sure you don't have anything running on port 80 on the host machine (like a web server):

        gem install pygmy
        pygmy up

* [Windows](https://docs.amazee.io/local_docker_development/windows.html):    

        git clone https://github.com/amazeeio/amazeeio-docker-windows amazeeio-docker-windows; cd amazeeio-docker-windows
        docker-compose up -d; cd ..

* [Ahoy (optional)](http://ahoy-cli.readthedocs.io/en/latest/#installation) - The commands are listed in `.ahoy.yml` all include their docker-compose versions for use on Windows, or on systems without Ahoy.

## Project Setup

1. Checkout project repo and confirm the path is in Docker's file sharing config (https://docs.docker.com/docker-for-mac/#file-sharing):

        Mac/Linux: git clone https://www.github.com/govcms/govcms8-scaffold.git {INSERT_PROJECT_NAME} && cd $_
        Windows:   git clone https://www.github.com/govcms/govcms8-scaffold.git {INSERT_PROJECT_NAME}; cd {INSERT_PROJECT_NAME}

2. Build and start the containers:

2.1 Pull new docker images (and remove existing local cached ones - only do this if you are building a new site)

All:

        docker-compose down

Linux/Mac:

        ahoy pull

Windows (cmd):

        docker image ls --format "{{.Repository}}:{{.Tag}}" | findstr "govcms8lagoon/" | for /f %f in ('findstr /V "none"') do docker pull %f 

Windows (git bash):

        alias docker="winpty -Xallow-non-tty docker"
        alias docker-compose="winpty -Xallow-non-tty docker-compose"
        docker image ls --format \"{{.Repository}}:{{.Tag}}\" | grep govcms8lagoon/ | grep -v none | awk "{print $1}" | xargs -n1 docker pull | cat


Windows (powershell):

        docker image ls --format "{{.Repository}}:{{.Tag}}" | Select-String -Pattern "govcms8lagoon/" | Select-String -Pattern "none" -NotMatch | ForEach-Object -Process {docker pull $_}

All:

        docker-compose build

2.2 Start docker containers

        Mac/Linux:  ahoy up
        Windows:    docker-compose up -d

3. Install GovCMS (only do this if you are building a new site - otherwise see the Databases section below):

        Mac/Linux:  ahoy install
        Windows:    docker-compose exec -T test drush si -y govcms

4. Login to Drupal:

        Mac/Linux:  ahoy login
        Windows:    docker-compose exec -T test drush uli

## Commands

Additional commands are listed in `.ahoy.yml`, or available from the command line `ahoy -v`

## Databases

The GovCMS projects have been designed to be able to import a nightly copy of the latest `master` branch database in two ways:

1: Using the GitLab container registry nightly backup
* these instructions are for https://projects.govcms.gov.au/{org}/{project}/container_registry
* copy the .env.default file to .env on your local
* remove the # from in front of #MARIADB_DATA_IMAGE=gitlab-registry-production.govcms.amazee.io/{org}/{project}/mariadb-drupal-data
* add a GitLab Personal Access Token with `read_registry` scope (profile/personal_access_tokens)
* `docker login gitlab-registry-production.govcms.amazee.io` (and use the PAT created above as the password)
* `ahoy up` (or the docker-compose equivalent)
* to refresh the db with a newer version, run `ahoy up` again

2: Use the backups accessible via the UI
* head to https://dashboard.govcms.gov.au/backups?name={project}-master
* click "Prepare download" for the most recent mysql backup you want - note that you will have to refresh the page to see when it is complete.
* download that backup into your project folder.
* `ahoy mysql-import` to import the dump you just saved

## Development

* You should create your theme(s) in folders under `/themes`
* Tests specific to your site can be committed to the `/tests` folders
* The files folder is not (currently) committed to GitLab.
* Do not make changes to `docker-compose.yml`, `lagoon.yml`, `.gitlab-ci.yml` or the Dockerfiles under `/.docker` - these will result in your project being unable to deploy to GovCMS SaaS

## Image inheritance

This project is designed to provision a Drupal 8 project onto GovCMS SaaS, using the GovCMS8 distribution, and has been prepared thus

1. The vanilla GovCMS8 Distribution is available at [Github Source](https://github.com/govcms/govcms8) and as [Public DockerHub images](https://hub.docker.com/r/govcms8)
2. Those GovCMS8 images are then customised for Lagoon and GovCMS, and are available at [Github Source](https://github.com/govcms/govcms8lagoon) and as [Public DockerHub images](https://hub.docker.com/r/govcms8lagoon)
3. Those GovCMS8lagoon images are then retrieved in this scaffold repository.

## Configuration management

GovCMS8 has default configuration management built in. It assumes all configuration is tracked (in `config/default`).

1. Export latest configuration to `config/default`:

        Mac/Linux:  ahoy cex
        Windows:    docker-compose exec -T test drush cex sync

2. Import any configuration changes from `config/default`:

        Mac/Linux:  ahoy cim
        Windows:    docker-compose exec -T test drush cim sync

3. Import development environment configuration overrides:

        Mac/Linux:  ahoy cim dev
        Windows:    docker-compose exec -T test drush cim dev --partial


*Note*: Configuration overrides are snippets of configuration that may be imported over the base configuration. These (optional) files should exist in `config/dev`.
For example a development project may include a file such as `config/dev/shield.settings.yml` which provides Shield authentication configuration that would only apply to a development environment, not production.
