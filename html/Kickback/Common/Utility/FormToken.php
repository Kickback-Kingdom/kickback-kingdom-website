<?php

namespace Kickback\Common\Utility;

// TODO: The \Kickback\Models\Response class should probably be moved into some
//   subnamespace of \Kickback\Common (maybe \Kickback\Common\Models?).
//   Right now, this looks like a circular dependency:
//     Backend depending on Common depending on Backend
//   (Which is very bad.)
//   But fixing this looks like a difficult refactoring to do from the commandline,
//   so I might wait for help from others or look for tools to help with that later.
//   On the upside, this seems to be an illusionary circular dependency right now,
//   so things should work in the meantime. (But it does make it easier for
//   other people to make mistakes, and it makes the code harder to understand.)
//   -- Lily Joan  2024-08-10
use Kickback\Models\Response;

class FormToken {
    /**
     * Generates a new form token and stores it in the session.
     */
    public static function generateFormToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['form_token'])) {
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Validates the form token sent via POST against the one stored in the session.
     * Regenerates the token after validation to prevent reuse.
     *
     * @return Response Returns an Response indicating the success or failure of the token validation.
     */
    public static function useFormToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_POST['form_token']) && isset($_SESSION['form_token']) && $_SESSION['form_token'] === $_POST['form_token']) {
            // Regenerate a new token to ensure a token is only used once
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            return new Response(true, "Token valid.");
        } else {
            return new Response(false, "Invalid or expired form submission token.");
        }
    }

    public static function getFormToken() : string {
        return $_SESSION["form_token"];
    }

    public static function registerForm() : string {
        echo "<input type='hidden' name='form_token' value='".self::getFormToken()."'>";
    }
}

?>
