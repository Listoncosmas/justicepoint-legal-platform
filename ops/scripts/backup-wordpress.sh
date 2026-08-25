#!/bin/sh

set -eu
umask 077
cd /

jp_site_root='/var/www/justicepoint.crmpl.us/current'
jp_backup_dir='/var/www/justicepoint.crmpl.us/shared/backups'
jp_backup_stamp="$(date -u '+%Y%m%dT%H%M%SZ')"
jp_backup_file="${jp_backup_dir}/justicepoint-${jp_backup_stamp}.sql"

/usr/local/bin/wp db export "${jp_backup_file}" --add-drop-table --quiet --path="${jp_site_root}"
/usr/bin/gzip -9 "${jp_backup_file}"
/usr/bin/find "${jp_backup_dir}" -type f -name 'justicepoint-*.sql.gz' -mtime +14 -delete
