<?php
/**
 * Lead qualification schema.
 *
 * The leads table already carried the first-generation form (name, phone,
 * email, terrain, target, timing, knowledge, wants_to_learn). Those are reused
 * where the meaning is unchanged, so the leads already captured keep working
 * and the admin CSV export stays compatible:
 *
 *   name        <- Full Name
 *   phone       <- WhatsApp number, stored as full international digits
 *   email       <- Email address
 *   timing      <- Q5 "When are you planning to buy?"
 *   knowledge   <- Q6 "Tell us anything else"
 *
 * Everything else is additive, so hpl_ensure_lead_schema() only ever adds
 * columns. It is safe to run on every request and on every deploy.
 */

if (!function_exists('hpl_lead_columns')) {
    /**
     * Columns the qualification form adds, with the SQL definition for each.
     *
     * @return array<string,string> column name => column definition
     */
    function hpl_lead_columns(): array
    {
        return [
            'country_code'  => "VARCHAR(8) NOT NULL DEFAULT ''",
            'country'       => "VARCHAR(80) NOT NULL DEFAULT ''",
            'city'          => "VARCHAR(80) NOT NULL DEFAULT ''",
            'looking_for'   => "VARCHAR(80) NOT NULL DEFAULT ''",
            'finding'       => "VARCHAR(80) NOT NULL DEFAULT ''",
            'experience'    => "VARCHAR(80) NOT NULL DEFAULT ''",
            'customer_type' => "VARCHAR(120) NOT NULL DEFAULT ''",
            'needs_advice'  => "TINYINT(1) NOT NULL DEFAULT 0",
            'source'        => "VARCHAR(80) NOT NULL DEFAULT ''",
        ];
    }
}

if (!function_exists('hpl_missing_lead_columns')) {
    /**
     * Which of the wanted columns the table does not have yet.
     *
     * @return array<int,string>
     */
    function hpl_missing_lead_columns(mysqli $db): array
    {
        $have = [];
        try {
            $result = $db->query('SHOW COLUMNS FROM leads');
        } catch (mysqli_sql_exception $e) {
            // config.php runs mysqli in strict mode, so a failed read throws.
            // An unreadable table must not take the whole page down; the insert
            // path reports a normal inline error instead.
            error_log('hpl: cannot read leads table: ' . $e->getMessage());

            return [];
        }
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $have[strtolower((string)$row['Field'])] = true;
            }
        }

        $missing = [];
        foreach (hpl_lead_columns() as $column => $definition) {
            if (!isset($have[$column])) {
                $missing[$column] = $definition;
            }
        }

        return $missing;
    }
}

if (!function_exists('hpl_ensure_lead_schema')) {
    /**
     * Add any missing qualification columns.
     *
     * Idempotent: a table that is already current costs one SHOW COLUMNS and
     * nothing else. Returns the columns it added, or null if the table itself
     * is not reachable.
     *
     * @return array<int,string>|null
     */
    function hpl_ensure_lead_schema(mysqli $db): ?array
    {
        $missing = hpl_missing_lead_columns($db);
        if (!$missing) {
            return [];
        }

        $added = [];
        foreach ($missing as $column => $definition) {
            // Backticks only ever wrap a key from the literal list above.
            $sql = 'ALTER TABLE `leads` ADD COLUMN `' . $column . '` ' . $definition;
            try {
                if ($db->query($sql)) {
                    $added[] = $column;
                }
            } catch (mysqli_sql_exception $e) {
                // Strict mode turns a rejected ALTER into an exception, which
                // would otherwise escape as a 500 on the visitor's submit.
                error_log('hpl: could not add leads.' . $column . ': ' . $e->getMessage());
            }
        }

        return $added;
    }
}

if (!function_exists('hpl_public_csrf_token')) {
    /**
     * Session CSRF token for the public lead form.
     *
     * The project already guards admin with a session token (admin/config.php);
     * that file force-redirects to the login screen, so the public form cannot
     * reuse it. This follows the same pattern on its own session key.
     */
    function hpl_public_csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['hpl_lead_csrf'])) {
            $_SESSION['hpl_lead_csrf'] = bin2hex(random_bytes(16));
        }

        return (string)$_SESSION['hpl_lead_csrf'];
    }
}

if (!function_exists('hpl_public_csrf_field')) {
    function hpl_public_csrf_field(): string
    {
        return '<input type="hidden" name="lead_csrf" value="' . h(hpl_public_csrf_token()) . '">';
    }
}

if (!function_exists('hpl_public_csrf_ok')) {
    function hpl_public_csrf_ok(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sent = (string)($_POST['lead_csrf'] ?? '');

        return $sent !== '' && !empty($_SESSION['hpl_lead_csrf'])
            && hash_equals((string)$_SESSION['hpl_lead_csrf'], $sent);
    }
}
