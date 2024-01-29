# Kickback Kingdom Website #

This repository houses the code for the Kickback Kingdom website:

https://www.kickback-kingdom.com/

## Caveats ##

At the moment, this repository does not contain everything needed to run the
website locally or create your own instance. To do that, you would need these
things:
* A database populated with at least an admin account, and some other essential records.
* Ideally, image files for the webpages. (It seems to work without them, but doesn't look like the real thing.)

This repository DOES contain an SQL schema for the database, it just might not have minimally viable data to go with it. (We are keeping the website's current database private for security reasons, but providing a minimal database for testing/dev purposes is something we want to do in the future.)

The images are currently stripped out of the repository because they required 300+MB and were a bit much. And because the website installs into an `html`/`htdocs` folder AND creates a "beta" copy in its `beta` subfolder, it would occupy 600MB+ after everything is duplicated. There are even bigger issues with uploaded content being mixed with static assets, which are things we need to resolve before image files can be hosted in the site's git repository. So all of these things are a work-in-progress, and we hope to eventually have images available at some point, either by adding the static assets to the git repository, or providing an external download and configuration options in the website.

In spite of these caveats, this repository should still be very useful: it will allow anyone to see how scripts work on the website, make pull requests, submit bug reports, request features, and so on.

## Installation ##

As of right now, it may be impossible to completely install the website and get it working. But it's close. Here are most of the instructions for it; once the website is "clonable", the below instructions will be part of the process.

##### Dependencies: #####
* Apache/HTTPD webserver
* MySQL/MariaDB database
* PHP version 7.2.34 or greater (soon PHP 8.1 or 8.2 will be required)
* Only tested on Linux host so far

##### Steps: #####
1. Have the above dependencies met on the system the site is being installed to.
2. `git clone https://github.com/Kickback-Kingdom/kickback-kingdom-website.git` 
3. Set up the MySQL server to have a user account that the Kickback Kingdom website can log in to.
4. Upload the SQL schema from the `kickback-kingdom-website/schema/kickback-kingdom-schema.mysql` into the MySQL server.
5. (WIP/TODO: Right now this step is not possible to do.) Add an admin account to the website, and any other records needed for it to function.
6. Create a "service credentials" config file in either `/srv/kickback-kingdom/credentials.ini` or `/etc/kickback-kingdom/credentials.ini`.
7. Fill the `credentials.ini` file with the necessary SQL account information (username+password+etc) and SMTP server information (username+password+etc, for "forgot password" feature).
    * See `kickback-kingdom-website/html/service-credentials-ini.php` for details about what fields are needed in the .ini file. (This path may change soon as the config file code gets refactored.)
8. (WIP: For now, skip this step.) For the rest of the configuration (non-confidential settings), create a file named `/srv/kickback-kingdom/config.ini` or `/etc/kickback-kingdom/config.ini`, and populate it with configuration option.
    * TODO: This file isn't acknowledged by the website code yet, but will be necessary at some point, as it will be necessary for overriding paths in the website (like where the uploaded assets are located) that are currently hard-coded.
    * TODO: If we are lucky with time+motivation, we might provide a way for the website to populate default config files to make these steps easier.
8. In a shell (either a POSIX shell, a BASH shell (ex: in Linux), or a cygwin/git-for-windows shell in Windows), change directory to the `kickback-kingdom-website` repository's directory, e.g. `cd kickback-kingdom-website`.
9. From inside the `kickback-kingdom-website` directory, run `make install TARGET_DIR=...`, with TARGET_DIR being pointed to Apache's "document root", which will be something like `/var/www/html` or `/var/www/localhost/htdocs`.
    * Alternatively, copy the _contents_ of the `kickback-kingdom-website/html` directory into one of those directories (being sure to include hidden files like `.htaccess`). This is not the preferred way to do things, but it might work. The `beta` feature will not work this way, however.
10. Turn on the Apache server and SQL server.
11. Using a web browser navigate to `http://localhost/index.php`, or to wherever the apache server is hosting the website. This should load the Kickback Kingdom website! If it didn't work, try looking through Apache logs (ex: `/var/log/apache2/error_log`, `var/log/httpd/ssl_error_log`) to see what went wrong.

