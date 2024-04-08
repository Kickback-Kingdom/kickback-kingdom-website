#!/bin/sh

# This script is for exporting a full copy of the Kickback Kingdom database,
# including all data, along with the schema.
#
# To export just the schema, use the `schema-dump.sh` script instead.

# Notably, we export the database to somewhere else that ISN'T the
# Kickback Kingdom website's repository.
#
# This is a safety measure to make it less likely that the
# database's data is accidentally committed to the git repository, because
# that would be a significant security concern (disclosure vulnerability).
#
KKDB_EXPORT_PATH="${KKDB_EXPORT_PATH:-$HOME/kickback-kingdom-full-database-backup.mysql}"
KKDB_DATABASE_NAME="${KKDB_DATABASE_NAME:-kickbackdb}"

if [ -f "$KKDB_EXPORT_PATH" ]; then
    KKDB_TIMESTAMP=$(date '+%Y-%m-%d.%H.%M.%S');
    echo "!!! Existing database file '$KKDB_EXPORT_PATH' detected !!!"
    echo "... Moving it to '$KKDB_EXPORT_PATH.$KKDB_TIMESTAMP' before proceeding with export."
    mv "$KKDB_EXPORT_PATH" "$KKDB_EXPORT_PATH.$KKDB_TIMESTAMP"
fi

echo "Full export of the database '$KKDB_DATABASE_NAME' will now begin."
mysqldump --user=root --single-transaction --add-drop-database --add-drop-trigger --comments --complete-insert --default-character-set=utf8mb4 --events --log-error --opt --routines --triggers --skip-compact --databases "$KKDB_DATABASE_NAME" --password > "$KKDB_EXPORT_PATH"
success="$?"

if [ "0" -eq "$success" ]; then
    if [ -f "$KKDB_EXPORT_PATH" ]; then
        echo "Database successfully exported to '$KKDB_EXPORT_PATH'"
    else
        echo "ERROR: 'mysqldump' reports success, but no file was written to '$KKDB_EXPORT_PATH'"
    fi
else
    if [ -f "$KKDB_EXPORT_PATH" ]; then
        echo "ERROR: 'mysqldump' reports failure at export, but file '$KKDB_EXPORT_PATH' now exists regardless. It might not be correct, though!"
    else
        echo "ERROR: Could not export database, errors were encountered."
    fi
fi

# The above `mysqldump` command creates an empty `--opt` file for some reason.
# So after running it, we check for that and delete the file if it is made.
# If the arguments to `mysqldump` are changed, it might choose a different
# argument to make an empty file out of. In that case, please modify this
# command to have it aimed at the correct junk.
if [ -f "./--opt" ]; then
    echo "(Sidenote: Removing the extraneous ./--opt file that mysqldump usually creates.)"
    rm "./--opt"
fi

# Also check it in the same directory as the export target, because who knows,
# maybe the dang thing will end up there too!
KKDB_EXPORT_DIR=$(dirname "$KKDB_EXPORT_PATH")
if [ -f "$KKDB_EXPORT_DIR/--opt" ]; then
    echo "(Sidenote: Removing the extraneous $KKDB_EXPORT_DIR/--opt file that mysqldump usually creates.)"
    rm "$KKDB_EXPORT_DIR/--opt"
fi

