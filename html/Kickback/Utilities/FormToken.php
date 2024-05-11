<?php

namespace Kickback\Utilities;

use Kickback\Models\Response;
use Session;

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
}

?>
