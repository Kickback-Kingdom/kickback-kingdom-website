<?php
declare(strict_types=1);

namespace Kickback\Config;

/**
* @implements \ArrayAccess<string,mixed>
*/
final class ServiceCredentials implements \ArrayAccess
{
    // List of file locations to try when reading the config file(s).
    // The first element is tried first, the second element second, and so on.
    // The first file it encounters is the one that will be used.
    //
    // Config files are considered _optional_.
    // Config modules must specify default values that shall be used whenever
    // the config files are unavailable.
    //
    // TODO: As of this writing, we do not have code for loading these :(
    //
    /**
    * @var array<string>
    * @phpstan-ignore classConstant.unused
    */
    private const CONFIG_SOURCES =
        [
            "/srv/kickback-kingdom/config.ini",
            "/etc/kickback-kingdom/config.ini",
        ];

    // List of file locations to try when reading the credentials files.
    // The first element is tried first, the second element second, and so on.
    // The first file it encounters is the one that will be used.
    //
    // The service credentials file is MANDATORY.
    // Some values in this file cannot have default values due to security
    // concerns. At the same time, these same values tend to be required
    // for connecting to services that must exist for the server to
    // function at all (ex: SQL server).
    //
    // For an example of this config file, check out the
    // `meta/config-examples/credentials.ini` file,
    // as specified from the root of the project.
    //
    /** @var array<string> */
    private const SERVICE_CREDENTIAL_SOURCES =
        [
            "/srv/kickback-kingdom/credentials.ini",
            "/etc/kickback-kingdom/credentials.ini",
        ];

    /** @var null|ServiceCredentials */
    private static $instance = null;

    /** @var array<string,mixed> */
    protected $entries = [];

    /** @var bool */
    private $first_error = true;

    private function error_prolog() : void
    {
        $this->first_error = true;
    }

    private function error(string $err_msg) : void
    {
        // This function is a STUB that allows `validate` to exist.
        // Right now, this does nothing.
        // In the future, it could be used to log or display errors during service credential validation.

        // If you don't mind dumping the output to the webpage, then this works:
        if ( $this->first_error ) {
            echo "<!--\n";
        }
        echo $err_msg . "\n";
    }

    private function error_epilog() : void
    {
        if ( !$this->first_error ) {
            echo "-->\n";
            $this->first_error = true; // Redundant, but safe.
        }
    }

    /**
    * @param     string                     $item
    * @return    bool
    */
    private function credential_item_exists(string $item) : bool
    {
        if ( !array_key_exists($item, $this->entries) ) {
            $this->error("ERROR: Missing credential item '" . $item . "'");
            return false;
        }
        else
        if ( is_null($this->entries[$item]) ) {
            $this->error("ERROR: Credential item '" . $item . "' is NULL.");
            return false;
        }

        return true;
    }

    /**
    * @param     string                     $item
    * @return    bool
    */
    private function credential_string_exists(string $item) : bool
    {
        if ( !$this->credential_item_exists($item) )
                return false;
        else
        if ( !is_string($this->entries[$item]) ) {
            // NOTE: For security reasons, we must NOT output the value associated with the key.
            $this->error("ERROR: '" . $item . "' set to something that is not a string, but it should be.");
            return false;
        }

        return true;
    }

    /**
    * @param     string                $item
    * @param     string                $type_name
    * @param     int                   $filter
    * @param     int                   $flags
    * @return    bool
    */
    private function credential_of_given_type_exists(string $item, string $type_name, int $filter, int $flags = FILTER_FLAG_NONE) : bool
    {
        if ( !$this->credential_item_exists($item) )
                return false;
        else
        if ( is_null(filter_var($this->entries[$item], $filter, $flags | FILTER_NULL_ON_FAILURE)) ) {
            // NOTE: For security reasons, we must NOT output the value associated with the key.
            $this->error("ERROR: '" . $item . "' set to something that is not a/an " . $type_name . ", but it should be.");
            return false;
        }

        return true;
    }

    /**
    * @return    bool
    */
    private function validate() : bool
    {
        $error_count = 0;

        // SQL Server login/connection information.
        $error_count += (int)!$this->credential_of_given_type_exists("sql_server_host",    "hostname", FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        $error_count += (int)!$this->credential_string_exists       ("sql_server_db_name");
        $error_count += (int)!$this->credential_string_exists       ("sql_username");
        $error_count += (int)!$this->credential_string_exists       ("sql_password");

        // Parameters for connecting to the SMTP server for sending emails.
        // (ex: to send "forgot password" emails to people)
        $error_count += (int)!$this->credential_of_given_type_exists("smtp_auth",          "bool", FILTER_VALIDATE_BOOLEAN);
        $error_count += (int)!$this->credential_string_exists       ("smtp_secure");
        $error_count += (int)!$this->credential_of_given_type_exists("smtp_host",          "hostname", FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        $error_count += (int)!$this->credential_of_given_type_exists("smtp_port",          "integer", FILTER_VALIDATE_INT);
        $error_count += (int)!$this->credential_string_exists       ("smtp_username");
        $error_count += (int)!$this->credential_string_exists       ("smtp_password");
        $error_count += (int)!$this->credential_of_given_type_exists("smtp_from_email",    "email", FILTER_VALIDATE_EMAIL);
        $error_count += (int)!$this->credential_of_given_type_exists("smtp_replyto_email", "email", FILTER_VALIDATE_EMAIL);
        $error_count += (int)!$this->credential_string_exists       ("smtp_from_name");
        $error_count += (int)!$this->credential_string_exists       ("smtp_replyto_name");

        // Encryption key. As of this writing, used for encrypting quest IDs.
        $error_count += (int)!$this->credential_string_exists       ("crypt_key_quest_id");

        // Discord auth info; used for sending notifications about events and stuff.
        $error_count += (int)!$this->credential_string_exists       ("discord_api_url"); // https://discord.com/api/webhooks/<some_number>
        $error_count += (int)!$this->credential_string_exists       ("discord_api_key"); // Concatenated with `discord_api_url` to get the full URL for discord API access.

        // Kickback Kingdom auth info; used to establish sessions with backend API
        $error_count += (int)!$this->credential_string_exists       ("kk_service_key");

        return ($error_count > 0);
    }

    public function load_service_credentials() : void
    {
        // Inform the error-handling code that we have started a new load.
        $this->error_prolog();

        $file_found = null;
        foreach (self::SERVICE_CREDENTIAL_SOURCES as $ini_file)
        {
            if ( !file_exists($ini_file) )
                continue;

            if ( !is_readable($ini_file) ) {
                $this->error(
                    "ERROR: Found credential config file at path '" . $ini_file . "',\n" .
                    "    but could not open it for reading.\n" .
                    "    This is probably a permissions problem.");

                continue;
            }

            // Found an INI file AND we can read it.
            $entries = parse_ini_file($ini_file);
            if ( !is_array($entries) ) {
                $this->error(
                    "ERROR: Found credential config file at path '" . $ini_file . "',\n" .
                    "    but `parse_ini_file` returned bool (e.g. false) when reading it.\n" .
                    "    There may be a syntax error, or the file is somehow not a valid ini file.");

                continue;
            }

            // Found an INI file AND we can read it AND it's valid.
            $this->entries = $entries;
            $file_found = $ini_file;
            break;
        }
        unset($ini_file);

        if ( !isset($file_found) )
        {
            $this->error(
                "ERROR: Could not find usable credential configuration ini file.\n" .
                "    These paths are examined for this file: [" .
                implode(", ", self::SERVICE_CREDENTIAL_SOURCES) .
                "]");
        }

        if ( isset($file_found) ) {
            $error_count = $this->validate();
            if ($error_count)
                $this->error(
                    "There were $error_count errors in the credential file '$file_found'.\n" .
                    "The server may be unable to connect to necessary services.");
            // Note that we tolerate some errors in the credential file without
            // setting `$this->entries` to `null`.
            // This allows partially-populated credentials files to work,
            // ex: in development environments.
        }

        // Finalize/commit any error-related state.
        $this->error_epilog();
    }

    public static function load_new_service_credentials() : void
    {
        self::$instance = new ServiceCredentials();
        self::$instance->load_service_credentials();
    }

    /**
    * @return    null|array<string,mixed>
    */
    public static function get_all() : null|array
    {
        // Memoize results to avoid executing this over and over.
        // (Technically we could use `!is_null($kickback_service_credentials)` as
        // the condition here, but we use `isset` just in case caller code unsets
        // the global value for some reason, and we need to reload it.)
        if ( !isset(self::$instance) )
            self::load_new_service_credentials();

        // If it failed to load, return `null`.
        assert(isset(self::$instance));
        $entries = self::$instance->entries;
        /** @phpstan-ignore  empty.notAllowed */
        if ( empty($entries) ) {
            return null;
        }

        // Entries are available from a successful load.
        return $entries;
    }

    /** @return mixed */
    public static function get(string $entry_name)
    {
        $entries = self::get_all();
        if ( is_null($entries) ) {
            return null;
        } else {
            return $entries[$entry_name];
        }
    }

    public static function instance() : ServiceCredentials
    {
        if ( !isset(self::$instance) )
            self::load_new_service_credentials();

        assert(isset(self::$instance));
        return self::$instance;
    }

    /* ----- ArrayAccess implementation ----- */
    // (Unfortunately, this only works for object instances, not for static access.
    // So we still need (=want) things like `get` and `get_all`, declared earlier.)
    /**
    * @param mixed  $offset
    * @param mixed  $value
    */
    public function offsetSet(mixed $offset, mixed $value) : void {
        echo "<!-- ERROR: Attempt to set element on read-only ServiceCredentials object. -->";
    }

    /// @param mixed  $offset
    public function offsetExists(mixed $offset) : bool {
        return isset($this->entries[$offset]);
    }

    /// @param mixed  $offset
    public function offsetUnset(mixed $offset) : void {
        unset($this->entries[$offset]);
    }

    /**
    * @param mixed  $offset
    * @return mixed
    */
    public function offsetGet(mixed $offset) : mixed {
        return isset($this->entries[$offset]) ? $this->entries[$offset] : null;
    }
}
?>
