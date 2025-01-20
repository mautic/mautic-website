#!/bin/bash

WP_DIR=$(pwd)
echo "Using current directory as WordPress directory: $WP_DIR"

DEFAULT_SQL="$WP_DIR/mautic-db.sql"
DEFAULT_UPLOADS="$WP_DIR/../uploads"
SQL_FILE="$DEFAULT_SQL"
UPLOADS_DIR="$DEFAULT_UPLOADS"
DO_GIT_CLONE=0  # Default: Skip cloning repo
GIT_REPO_URL="https://github.com/mautic/mautic-website.git" 

function show_help() {
  echo "Usage: $0 [options]"
  echo "Options:"
  echo "  -db, --database FILE     Path to the database SQL file (default: \"$DEFAULT_SQL\")"
  echo "  -u, --uploads DIR        Path to the uploads directory (default: \"$DEFAULT_UPLOADS\")"
  echo "  -g, --git-clone URL      Clone the Git repository from the specified URL"
  echo "  -h, --help               Show this help message"
  exit 0
}

# Check for MySQL/MariaDB
mysql_version=$(mysql --version 2>/dev/null)
if [[ $? -ne 0 ]]; then
  echo "Error: MySQL or MariaDB client (mysql) is not installed or not in PATH."
  exit 1
else
  echo "Detected: $mysql_version"
fi

# Parse arguments
while [[ "$#" -gt 0 ]]; do
  case $1 in
    -db|--database)
      SQL_FILE="$2"
      shift 2
      ;;
    -u|--uploads)
      UPLOADS_DIR="$2"
      shift 2
      ;;
    -g|--git-clone)
      DO_GIT_CLONE=1
      GIT_REPO_URL="$2"
      shift 2
      ;;
    -h|--help)
      show_help
      ;;
    *)
      echo "Unknown flag: $1"
      show_help
      ;;
  esac
done

# Perform Git Clone if flag is set
if [[ "$DO_GIT_CLONE" -eq 1 ]]; then
  echo "Cloning the Git repository from: $GIT_REPO_URL..."
  if [[ -z "$GIT_REPO_URL" ]]; then
    echo "Error: Git repository URL not provided."
    exit 1
  fi
  git clone "$GIT_REPO_URL" "$WP_DIR"
  if [[ $? -ne 0 ]]; then
    echo "Error: Git clone failed."
    exit 1
  fi
  echo "Git repository cloned successfully."
fi

if [[ ! -f "$WP_DIR/wp-config.php" ]]; then
  echo "Error: wp-config.php not found. Make sure you created a wp-config.php from the sample and ran this script from the WordPress root directory."
  exit 1
fi

echo "Using database SQL file: \"$SQL_FILE\""
echo "Using uploads directory: \"$UPLOADS_DIR\""

if [[ ! -f "$SQL_FILE" ]]; then
  echo "Error: SQL file not found at \"$SQL_FILE\""
  exit 1
fi

if [[ ! -d "$UPLOADS_DIR" ]]; then
  echo "Error: Uploads directory not found at \"$UPLOADS_DIR\""
  exit 1
fi

echo "Restoring database from \"$SQL_FILE\"..."
mysql -u root -p -D mauticstaging < "$SQL_FILE"
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to restore database."
  exit 1
fi
echo "Database restored successfully."

echo "Restoring uploads from \"$UPLOADS_DIR\"..."
rsync -avz "$UPLOADS_DIR/" "$WP_DIR/wp-content/uploads/"
if [[ $? -ne 0 ]]; then
  echo "Error: Failed to sync uploads."
  exit 1
fi
echo "Uploads synced successfully."

echo "Setup complete."

