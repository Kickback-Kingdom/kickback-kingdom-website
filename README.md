# Kickback Kingdom Website #

This repository houses the code for the Kickback Kingdom website:

https://www.kickback-kingdom.com/

## Caveats ##

At the moment, this repository might not contain everything needed to run the
website locally or create your own instance. To do that, you would need these
things:
* A database populated with at least an admin account, and some other essential records.
* Ideally, image files for the webpages. (It seems to work without them, but doesn't look like the real thing.)


This repository DOES contain an SQL schema for the database, it just might not have minimally viable data to go with it. (We are keeping the website's current database private for security reasons, but providing a minimal database for testing/dev purposes is something we want to do in the future.)

The images are currently stripped out of the repository because they required 300+MB and were a bit much.
And because the website installs into an `html`/`htdocs` folder AND creates a "beta" copy in its `beta` subfolder, it would occupy 600MB+ after everything is duplicated. There are even bigger issues with uploaded content being mixed with static assets, which are things we need to resolve before image files can be hosted in the site's git repository. So all of these things are a work-in-progress, and we hope to eventually have images available at some point, either by adding the static assets to the git repository, or providing an external download and configuration options in the website.

In spite of these caveats, this repository should still be very useful: it will allow anyone to see how scripts work on the website, make pull requests, submit bug reports, request features, and so on.

Of course, there has been some evidence to suggest that it might work anyways. It will probably load pages, but it won't look exactly like the public website (due to missing assets and missing database entries).

## Installation ##

Below are some instructions for setting up a minimal copy of the server.

##### Dependencies: #####
* Apache/HTTPD webserver
* MySQL/MariaDB database
* PHP version 8.2 or greater
* PHP Composer
* OS:
    * Linux (tested on both Gentoo Linux and Amazon AWS; works well)
    * Windows (works well with XAMPP)
    * It can probably work in any other environment where the aforementioned Apache+MySQL+PHP+... are available.

##### Steps: #####
1. Have the above dependencies met on the system the site is being installed to.
2. Set up the MySQL server to have a user account that the Kickback Kingdom website can log in to.
3. Upload the SQL schema from the `kickback-kingdom-website/schema/kickback-kingdom-schema.mysql` into the MySQL server.
* If using MariaDB it is necessary to run the command below before uploading:
    ```
    sed -i 's/DEFINER=`[^`]*`@`[^`]*`//g' your_file.mysql
    ```
    This command removes the 'DEFINER' statements that exist in MySQL but are incompatible with MariaDB. 
4. (WIP/TODO: Right now this step is not possible to do.) Add an admin account to the website, and any other records needed for it to function.
5. Create a "service credentials" config file in either `/srv/kickback-kingdom/credentials.ini` or `/etc/kickback-kingdom/credentials.ini`.
6. Fill the `credentials.ini` file with the necessary SQL account information (username+password+etc) and SMTP server information (username+password+etc, for "forgot password" feature).
    * See `kickback-kingdom-website/html/service-credentials-ini.php` for details about what fields are needed in the .ini file. (This path may change soon as the config file code gets refactored.)
<!-- 7. (WIP: For now, skip this step.) For the rest of the configuration (non-confidential settings), create a file named `/srv/kickback-kingdom/config.ini` or `/etc/kickback-kingdom/config.ini`, and populate it with configuration option.
    * TODO: This file isn't acknowledged by the website code yet, but will be necessary at some point, as it will be necessary for overriding paths in the website (like where the uploaded assets are located) that are currently hard-coded.
    * TODO: If we are lucky with time+motivation, we might provide a way for the website to populate default config files to make these steps easier.
-->
7. Find the folder that your Apache or HTTPD web server is configured to host its default website from:
    * On Gentoo Linux, it's declared in `/etc/apache2/vhosts.d/default_vhost.include`
    * And on Gentoo, the default folder is `/var/www/localhost/htdocs`
    * On an Amazon AWS instance, it's declared in `/etc/httpd/conf/httpd.conf`
    * And on an Amazon AWS instance, the default folder is `/var/www/html`
    * If you're on another Linux system and those don't work (or aren't relevant), you might be able to find it by grep'ing `/etc/apache`, `/etc/apache2`, or `/etc/httpd` for the text string "DocumentRoot". The "DocumentRoot" declaration is what defines Apache or HTTPD's folder to serve the webpage out of.
    * On Windows systems: (Not documented yet, sorry. But it DOES work! At least with XAMPP Apache+MariaDB+PHP.)
    * Since the web server (ex: Apache) might host the site in any number of locations, the rest of the instructions will refer to the `/var/www/html` path, because it is the shortest and easiest to read.
8. `git clone https://github.com/Kickback-Kingdom/kickback-kingdom-website.git /var/www/kickback-kingdom-website`
    * This will create the `/var/www/kickback-kingdom-website` folder, which is full of the site's source code, HTML, and some other miscellany.
9. Direct the web server (ex: Apache) to serve content from `/var/www/kickback-kingdom-website/html`
    * There is more than one way to do this:
        1. Change the `DocumentRoot` in Apache's config file(s)
            * Find a line like `DocumentRoot "/var/www/html"` in your Apache config
            * Replace that line with `DocumentRoot "/var/www/kickback-kingdom-website/html"`
        2. Use a symlink (Linux/Mac/Unix/etc only; probably not available in Windows)
            * `cd /var/www       # Navigate to the relevant directory`
            * `mv html html.bak  # Back up Apache's existing default website (optional)`
            * `ln -s kickback-kingdom-website/html html  # Create the symlink`
            * The above steps will create a symbolic link `/var/www/html -> /var/www/kickback-kingdom-website/html` that will redirect access to the correct directory.
            * This has a couple advantages:
                1. No need to change Apache config files (might be helpful if you don't have `root` access).
                2. Allows somewhat-easy switching between different websites or site versions by deleting and recreating the symlink.
10. `cd /var/www/kickback-kingdom-website`
    * E.g. in a shell (either a POSIX shell, a BASH shell (ex: in Linux), or a cygwin/git-for-windows shell in Windows), change directory to your `kickback-kingdom-website` repository's directory
11. Run `composer update` from inside the `/var/www/kickback-kingdom-website` directory
    * This should download and install all of the PHP dependencies that the webpage uses.
12. Make sure that Apache2/HTTPD is secured/configured the way that's most appropriate:
    * For dev machines:
        * It should only listen for traffic from 127.0.0.1 (localhost)
        * OR the system should have a firewall that denies access to Apache/HTTPD's serving ports (usually 80 and 443 by default).
    * For `beta` or `prod` machines: It should listen for traffic from any IP address (minus any security blacklists), and on ports 80 (http) and 443 (https).
        * Plus all of the other typical public server security concerns: firewall all unnecessary traffic, fail2ban, don't run unnecessary services, good logging with log rotation, etc etc.
13. Turn on the SQL Server and then Apache/HTTPD server.
14. Using a web browser navigate to `http://localhost/index.php`, or to wherever the apache server is hosting the website. This should load the Kickback Kingdom website! If it didn't work, try looking through Apache logs (ex: `/var/log/apache2/error_log`, `var/log/httpd/ssl_error_log`) to see what went wrong.

