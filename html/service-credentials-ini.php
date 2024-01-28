<?php

// List of file locations to try when reading the credentials files.
// The first element is tried first, the second element second, and so on.
// The first file it encounters is the one that will be used.
$kickback_service_credential_sources =
    [
        "/srv/kickback-kingdom/credentials.ini",
        "/etc/kickback-kingdom/credentials.ini",
    ];

// Associative array that holds the credentials after `LoadServiceCredentials`
// function has been called.
$kickback_service_credentials = null;


function CredentialError($err_msg)
{
    // This function is a STUB that allows `ValidateServiceCredentials` to exist.
    // Right now, this does nothing.
    // In the future, it could be used to log or display errors during service credential validation.

    // If you don't mind dumping the output to the webpage, then this works:
       echo $err_msg . "<br>";
}

function CredentialItemExists($item, &$credentials)
{
    if ( !array_key_exists($item,$credentials) ) {
        CredentialError("ERROR: Missing credential item '" . $item . "'");
        return false;
    }
    else
    if ( is_null($credentials[$item]) ) {
        CredentialError("ERROR: Credential item '" . $item . "' is NULL.");
        return false;
    }

    return true;
}

function CredentialStringExists($item, &$credentials)
{
    if ( !CredentialItemExists($item,$credentials) )
            return false;
    else
    if ( !is_string($credentials[$item]) ) {
        // NOTE: For security reasons, we must NOT output the value associated with the key.
        CredentialError("ERROR: '" . $item . "' set to something that is not a string, but it should be.");
        return false;
    }

    return true;
}

function CredentialOfGivenTypeExists($item, &$credentials, $type_name, $filter, $flags = FILTER_FLAG_NONE)
{
    if ( !CredentialItemExists($item,$credentials) )
            return false;
    else
    if ( is_null(filter_var($credentials[$item], $filter, $flags | FILTER_NULL_ON_FAILURE)) ) {
        // NOTE: For security reasons, we must NOT output the value associated with the key.
        CredentialError("ERROR: '" . $item . "' set to something that is not a/an " . $type_name . ", but it should be.");
        return false;
    }

    return true;
}

function ValidateServiceCredentials(&$credentials)
{
    $error_count = 0;

    // SQL Server login/connection information.
    $error_count += !CredentialOfGivenTypeExists("sql_server_host",    $credentials, "hostname", FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    $error_count += !CredentialStringExists     ("sql_server_db_name", $credentials);
    $error_count += !CredentialStringExists     ("sql_username",       $credentials);
    $error_count += !CredentialStringExists     ("sql_password",       $credentials);

    // Parameters for connecting to the SMTP server for sending emails.
    // (ex: to send "forgot password" emails to people)
    $error_count += !CredentialOfGivenTypeExists("smtp_auth",          $credentials, "bool", FILTER_VALIDATE_BOOLEAN);
    $error_count += !CredentialStringExists     ("smtp_secure",        $credentials);
    $error_count += !CredentialOfGivenTypeExists("smtp_host",          $credentials, "hostname", FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    $error_count += !CredentialOfGivenTypeExists("smtp_port",          $credentials, "integer", FILTER_VALIDATE_INT);
    $error_count += !CredentialStringExists     ("smtp_username",      $credentials);
    $error_count += !CredentialStringExists     ("smtp_password",      $credentials);
    $error_count += !CredentialOfGivenTypeExists("smtp_from_email",    $credentials, "email", FILTER_VALIDATE_EMAIL);
    $error_count += !CredentialOfGivenTypeExists("smtp_replyto_email", $credentials, "email", FILTER_VALIDATE_EMAIL);
    $error_count += !CredentialStringExists     ("smtp_from_name",     $credentials);
    $error_count += !CredentialStringExists     ("smtp_replyto_name",  $credentials);

    // Encryption key. As of this writing, used for encrypting quest IDs.
    $error_count += !CredentialStringExists     ("crypt_key_quest_id", $credentials);

    return ($error_count > 0);
}

function LoadServiceCredentials()
{
    global $kickback_service_credential_sources;
    global $kickback_service_credentials;

    $file_found = null;
    foreach ($kickback_service_credential_sources as $ini_file)
    {
        if ( !file_exists($ini_file) )
            continue;

        if ( !is_readable($ini_file) ) {
            CredentialError(
                "ERROR: Found credential config file at path '" . $ini_file . "', " .
                "but could not open it for reading. " .
                "This is probably a permissions problem.");

            continue;
        }

        // Found an INI file AND we can read it.
        $kickback_service_credentials = parse_ini_file($ini_file);
        $file_found = $ini_file;
        break;
    }
    unset($ini_file);

    if ( isset($file_found) )
    {
        $have_validation_errors = ValidateServiceCredentials($kickback_service_credentials);
        if ($have_validation_errors)
            CredentialError(
                "There were errors in the credential file '" . $file_found . "'.  " .
                "The server may be unable to connect to necessary services.");
    }
    else
    {
        CredentialError("ERROR: Could not find usable credential configuration ini file. " .
            "These paths are examined for this file: [" .
            implode(", ", $kickback_service_credential_sources) .
            "]");
    }

    $GLOBALS["kickback_service_credentials"] = $kickback_service_credentials;
}
?>
