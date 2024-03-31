This directory contains the apache2/httpd configuration currently used on the
Kickback Kingdom website's AWS server.

Other systems DO NOT need to use this exact configuration.

This is mostly being placed here as a way to subject the server's configuration
to version control (and as a bonus, have an additional, easily restored, backup).

The `<DIRECTORY>` sections for the `/var/www/kickback-kingdom-*/html` can also
serve as a reference for how to set these things up on other servers.

Likewise, `SetEnv KICKBACK_* ...` directives set environment variables
(constants, really) for the scripts to use to know about their server and
role and such. This file will demonstrate what these constants are, and
possibly illustrate their use.

