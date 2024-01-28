# Command to dump the Kickback Kingdom database's schema (but not data: this is NOT a backup).
# If the `--no-data` argument were elided, it might be possible to use this as a backup command.
# (And for a really pristine backup, albeit with a small amount of security
# leakage, also remove the `sed` command from this pipe.)
#
# Note that we are replacing the DEFINER user@host with placeholders (using the
# `sed` command), so that the schema dump doesn't contain any details about
# the server's configuration (for security reasons).
# This isn't absolutely necessary, because an attacker _probably_ can't do
# anything with just a username. But, because we _can_, it's usually better
# (from a security PoV) to avoid providing any unnecessary operational information.
#
# If you restore this database schema, you will need to replace the
# `$definer_user` and `$definer_host` placeholders with the user you want
# MySQL to use when defining `kickbackdb`'s functions, procedures, and views.
#
# Running this command will probably require you to enter the password for
# the MySQL database's `root` user.
#
mysqldump --user=root --single-transaction --add-drop-database --add-drop-trigger --comments --complete-insert --default-character-set=utf8mb4 --events --log-error --opt --routines --triggers --skip-compact --no-data --databases kickbackdb --password | sed -e 's:DEFINER[ ]*=[ ]*`[^`]*`@`%`:DEFINER=`\$definer_user`@`\$definer_host`:g' > kickback-kingdom-schema.mysql

# The above `mysqldump` command creates an empty `--opt` file for some reason.
# So after running it, we check for that and delete the file if it is made.
# If the arguments to `mysqldump` are changed, it might choose a different
# argument to make an empty file out of. In that case, please modify this
# command to have it aimed at the correct junk.
[ -f "./--opt" ] && rm "./--opt"
