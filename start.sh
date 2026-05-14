#!/bin/bash
set -e

MYSQL_DATA=/home/runner/mysql-data
MYSQL_RUN=/home/runner/mysql-run
MYSQL_LOGS=/home/runner/mysql-logs

mkdir -p "$MYSQL_DATA" "$MYSQL_RUN" "$MYSQL_LOGS"

# Start MariaDB if not already running
if ! mysqladmin -h 127.0.0.1 -P 3306 -u root ping 2>/dev/null; then
    echo "Starting MariaDB..."
    mysqld --no-defaults \
      --datadir="$MYSQL_DATA" \
      --socket="$MYSQL_RUN/mysqld.sock" \
      --pid-file="$MYSQL_RUN/mysqld.pid" \
      --log-error="$MYSQL_LOGS/error.log" \
      --innodb-use-native-aio=0 \
      --port=3306 \
      --bind-address=127.0.0.1 \
      --skip-grant-tables \
      --user="$(whoami)" &

    # Wait for MariaDB to be ready
    for i in $(seq 1 30); do
        if mysqladmin -h 127.0.0.1 -P 3306 -u root ping 2>/dev/null; then
            echo "MariaDB is ready."
            break
        fi
        sleep 1
    done
fi

# Create and import database if needed
DB_EXISTS=$(mysql -h 127.0.0.1 -P 3306 -u root -e "SHOW DATABASES LIKE 'sbiblioteca';" 2>/dev/null | grep -c sbiblioteca || true)
if [ "$DB_EXISTS" -eq 0 ]; then
    echo "Setting up database..."
    mysql -h 127.0.0.1 -P 3306 -u root -e "CREATE DATABASE IF NOT EXISTS sbiblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    mysql -h 127.0.0.1 -P 3306 -u root sbiblioteca < /home/runner/workspace/Bd/sbiblioteca.sql
    echo "Database imported successfully."
else
    echo "Database already exists."
fi

echo "Starting PHP web server on 0.0.0.0:5000..."
exec php -S 0.0.0.0:5000 -t /home/runner/workspace
