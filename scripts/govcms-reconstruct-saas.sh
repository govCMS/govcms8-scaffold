#!/usr/bin/env bash
IFS=$'\n\t'
set -euo pipefail

##
# This demonstrates the process of AWX applying a scaffold update.
#

TMP_DIR=${TMPDIR:-/tmp/}
CUR_DIR=$(pwd)

SCAFFOLD_REPO=${SCAFFOLD_REPO:-https://github.com/simesy/govcms8-scaffold}
SCAFFOLD_BRANCH=${SCAFFOLD_BRANCH:-paas-saas-mash-up}
SCAFFOLD_DIR=${TMP_DIR}govmcs8-scaffold
TARGET_DIR=${TARGET_DIR:-$CUR_DIR}

# Prep scaffold source.
rm -Rf ${TMP_DIR}/govmcs8-scaffold
git clone --depth 1 ${SCAFFOLD_REPO} -b ${SCAFFOLD_BRANCH} ${SCAFFOLD_DIR}

# Scaffold update.
rsync -rl --stats --exclude ".git/" --exclude ".env" --exclude ".lagoon.yml" ${SCAFFOLD_DIR}/ ${TARGET_DIR}

# Move themes.
mv ${TARGET_DIR}/themes/* ${TARGET_DIR}/web/themes/custom/
rm -Rf themes

#
cd ${TARGET_DIR}
git add themes && git add web/themes
# git commit -m"SaaS Scaffold Update: Moved themes to standard location."

git add custom && git add drush && git add 
# git commit -m"SaaS Scaffold Update: Moved themes to standard location."

git add .
# git commit -m"SaaS Scaffold Update: Update scaffold files."
