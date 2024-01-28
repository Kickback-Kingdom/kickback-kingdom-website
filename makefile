# Define the default target directory (parameter required when invoking the `install` rule)
TARGET_DIR :=

# Define the (html/php) source directory relative to the project root
HTML_DIR := html

# Define the target subdirectory
BETA_SUBDIR := beta

# Define the branch for the beta subdirectory
BETA_BRANCH := main

# Shorthands for internal use.
override target := $(TARGET_DIR)
override html := $(HTML_DIR)
override beta := $(BETA_SUBDIR)

# The `project_root` variable was originally intended to allow `git clone` to clone the
# repository using the `file://` protocol without complaining about the
# `--filter=tree:0` and `--depth=1` parameters.
# (Passing it `.` instead of `file://$(project_root)` results in a warning.)
# However, experimentation showed that avoiding the `file://` protocol and
# just passing it a plain path causes it to run MUCH faster.
# Since `--filter=tree:0` and `--depth=1` are intended as optimizations anyways,
# we don't need them, so they have been removed.
# (They would probably still be good for remote work, but this will never be remote.)
# Does that make `project_root` pointless? No.
# It's still useful for ensuring that the makefile can be invoked from
# paths besides the project's directory.
#
# Just to set a good example for future makefiles, I have procured a fairly
# robust way to calculate this value.
#
# The calculation's meaning is like so:
# Double quotes because "[this solution] is susceptible to path splitting" - user7343148; Sep 14, 2022
# (So it handles filenames with spaces in them.)
# MAKEFILE_LIST is a special variable that `make` populates with exactly-what-it-sounds-like.
# The first element of that list will be the root makefile.
# (Assumption: "To reference the current makefile, the use of $MAKEFILE_LIST must precede any include operations" - Brent Bradburn; Nov 11, 2015)
# `realpath` gets an absolute path for the file and dereferences symlinks and such.
# `shell dirname` gets the directory part of the path, and also removes any trailing '/'.
# `strip` handles if the directory has leading whitespace. (Thorsten Lorenz; May 2, 2015)
# Source:
# https://stackoverflow.com/a/23324703
# (Cited names/dates are from commenters on that stackoverflow answer.)
override project_root := $(strip $(shell dirname "$(realpath $(firstword $(MAKEFILE_LIST)))"))

# File status-es used for backup logic.
override target_exists := $(shell test -e "$(target)"        && echo "1" || echo "")
override backup_exists := $(shell test -e "$(target).backup" && echo "1" || echo "")

# Twiddly knob for turning the backup logic on/off.
#
# Right now I have it set to off:
# I realized that there is no way to remind the admin to delete the backup
# once they are done testing, and the backup folder is quite big, just
# like the folder created when installing (about 260MB as of this writing).
# Given that this is really a last-ditch-recovery kind of thing and not
# really the best way to recover, I think it's better to leave it off.
# Using `git` to roll back to stable code and then installing that stable
# code would be a much better way to recovery from any mishaps that might
# occur after an install.
override backup_enabled :=
#override backup_enabled := 1

# Define the `install` rule.
#
# Note that the `git clone` command has this `--git-dir=/dev/null` option in it.
# That option is NOT an option of the `clone` tool, but an option of the `git` itself.
# In this case, it is used to ensure that the destination (`target/beta`) does not
# contain a `.git` subdirectory (e.g. to ensure it isn't a git repository).
# Giving the `beta` directory a git repository is undesirable because it
# would be confusing to have this repository that is not-quite-like the
# one it came from (ex: missing assets and 3rd party includes), and because
# it wastes a little bit of disk space unnecessarily.
# Also the "this is not a git repository" error message would be one last chance
# to warn someone that they might be about to edit the wrong files.
# The `--git-dir=/dev/null` trick was taken from a comment by `HumanSky` (posted Nov 13, 2015)
# on this stackoverflow post: https://stackoverflow.com/a/3946745
#
# As mentioned in the `project_root` comment, there were a couple extra options
# for the `git ... clone` command that were experimented with:
# `--filter=tree:0` and `--depth=1`
# But they proved to be unhelpful because they required the cloning operation
# to be treated like a remote copy, and that was very inefficient.
# More can be learned about exactly what those _do_ by reading
# this article about "partial" and "shallow" git clones:
# https://github.blog/2020-12-21-get-up-to-speed-with-partial-clone-and-shallow-clone/
#
install: check_target_dir
ifeq (1,$(and $(backup_enabled),$(target_exists)))
	@echo "Install path '$(target)' already exists."
ifeq (1,$(backup_exists))
		@# Right now, you only get one mulligan.
		@# Installing more than twice will delete a backup.
		@# Doing anymore and we'd need rotation logic, filename manipulation, etc.
		@# It is STRONGLY recommended that you do not rely on or plan on
		@# using this backup.
		@# A better way to revert changes would be to use `git` to checkout/reset
		@# the correct (stable) code, and then install that.
		@echo "    A backup already exists at '$(target).backup.'"
		@echo "    It will be deleted to allow the more recent backup to be made."
		@rm -rf "$(target).backup"
endif
	@echo "    Making backup: '$(target)' -> '$(target).backup'"
	@mv "$(target)" "$(target).backup"
	@echo "    Backup done."
endif
	@echo "Installing HTML+PHP content to '$(target)'..."
	mkdir -p "$(target)"
	@# rsync is used to copy $(project_root)/$(html)/* into $(target)/
	@# We use `rsync` instead of `cp` because it won't waste time copying
	@# anything that is already up-to-date, and because the `--delete`
	@# option allows it to remove any files that shouldn't be in the
	@# target directory, thus creating a pristine like-new install directory,
	@# even if there was already an older version of code in there.
	rsync -axHAX --delete "$(project_root)/$(html)/" "$(target)"
	@echo "    ...HTML+PHP copy finished."
	@echo "Populating '$(beta)' subdirectory with 'git clone'."
	@git --git-dir=/dev/null clone --progress --branch $(BETA_BRANCH) --single-branch \
		"$(project_root)" \
		"$(target)/$(beta).temp"
	@echo "Renaming:  $(target)/$(beta).temp/$(html) -> $(target)/$(beta)"
	@mkdir -p "$(target)/$(beta)"
	@mv  "$(target)/$(beta).temp/$(html)"/*  "$(target)/$(beta)/"
	@echo "Deleting:  $(target)/$(beta).temp/"
	@rm -rf "$(target)/$(beta).temp"
	@echo "Installation complete."

# Check if the user provided the target directory
check_target_dir:
	@if [ -z "$(target)" ]; then \
		echo "Error: Please provide a target directory. Example: make install TARGET_DIR=/path/to/target"; \
		exit 1; \
	fi

# Thanks to https://unix.stackexchange.com/a/516476
# for providing a way to do this slick heredoc-like thing
# in a makefile.
define message =
Usage:
	make install TARGET_DIR=/path/to/target

Remarks:

This will install the contents of the currently selected
Kickback Kingdom website html+php+code into the `TARGET_DIR` directory.

This rule will NOT automatically use `prod` as the install branch.
Instead, it synchronizes whatever is currently present in the project folder.
If installing a production build, be sure that the directory's current
git branch is `prod` (and is up-to-date) _before_ running this make rule.

In non-production environments, it is perfectly valid, _intended_ even,
that branches besides `prod` can be installed. The way to do it is similar:
have git switched to whatever branch or commit (ex: `main`) that you'd
like to use, then use `make install TARGET_DIR=<somewhere>` to install
that branch or commit.
endef

help:; @ $(info $(message)) :
