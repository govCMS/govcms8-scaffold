#!/usr/bin/env bash

# This replicates the process of AWX applying a scaffold update.

# 1. do you need to run "change-the-locks.
# 2. clone the scaffold repo.
# 3. recursive copy everything except .lagoon.yml, .env , .git
# 4. Move themes directory and files directory
# 5. Set the scaffold version to 1.x
# 6. Commit to new branch (and push, if lfs unlocked).


