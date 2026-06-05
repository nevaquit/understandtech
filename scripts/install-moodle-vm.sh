#!/usr/bin/env bash
# Install Moodle 4.5 LTS core on understandtech VM.
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
BRANCH="${MOODLE_BRANCH:-MOODLE_405_STABLE}"

if [ -d "$MOODLE_DIR/.git" ]; then
  echo "Moodle already cloned at $MOODLE_DIR"
  cd "$MOODLE_DIR" && sudo git fetch origin && sudo git checkout "$BRANCH" && sudo git pull origin "$BRANCH"
else
  sudo rm -rf "$MOODLE_DIR"/*
  sudo git clone --depth 1 -b "$BRANCH" https://github.com/moodle/moodle.git "$MOODLE_DIR"
fi

sudo chown -R www-data:www-data "$MOODLE_DIR" /var/www/moodledata
sudo find "$MOODLE_DIR" -type d -exec chmod 755 {} \;
sudo find "$MOODLE_DIR" -type f -exec chmod 644 {} \;
echo "Moodle core installed at $MOODLE_DIR"
