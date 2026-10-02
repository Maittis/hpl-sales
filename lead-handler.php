<?php
/**
 * Lead qualification: questions, server-side validation and persistence.
 *
 * The question definitions live here and the form markup is rendered from them,
 * so the whitelist used to reject unexpected values is the same list the visitor
 * was actually offered. There is no second copy to drift out of step.
 */

require_once __DIR__ . '/lead-schema.php';

if (!function_exists('hpl_lead_questions')) {
    /**
     * The five required qualification questions plus the optional message.
     *
     * Options are stored as value => supporting note. The value half is the
     * whitelist, and the note is the small line under each card.
     *
     * @return array<string,array{label:string,hint?:string,required:bool,options?:array<string,string>}>
     */
    function hpl_lead_questions(): array
    {
        return [
            'lead_looking_for' => [
                'label'    => 'What are you looking for?',
                'required' => true,
                'options'  => [
                    'Gold Detector'             => 'The machine that finds buried gold.',
                    'Mining Equipment'          => 'Pumps, hoses, cradles and digging gear.',
                    'Complete Mining Setup'     => 'Detector, equipment and support, ready to work.',
                    "I'm Not Sure - I Need Help" => 'We will match the right setup to your situation.',
                ],
            ],
            'lead_finding' => [
                'label'    => 'What are you looking to find?',
                'required' => true,
                'options'  => [
                    'Gold Nuggets'            => 'Loose nuggets in the soil.',
                    'Deep Gold'               => 'Gold buried deeper in the ground.',
                    'Gold in Rock / Reef'      => 'Gold trapped inside hard rock.',
                    'Alluvial Gold'            => 'Gold carried along in river gravel.',
                    "I'm Not Sure"             => 'No problem, we will help you narrow it down.',
                ],
            ],
            'lead_experience' => [
                'label'    => 'Have you used a gold detector before?',
                'required' => true,
                'options'  => [
                    'Yes'                          => 'You know what to expect from a machine.',
                    'No, this is my first time'    => 'We will start from the basics.',
                    'Yes, but I need more training' => 'You want sharper results, not just a new toy.',
                ],
            ],
            'lead_customer_type' => [
                'label'    => 'What best describes you?',
                'required' => true,
                'options'  => [
                    'I am already mining'                 => 'You are working a claim or site.',
                    'I am planning to start mining'       => 'You are getting ready to begin.',
                    'I have land and want to search for gold' => 'You own or have access to the ground.',
                    "I'm still researching"               => 'Still comparing before you decide.',
                ],
            ],
            'lead_timing' => [
                'label'    => 'When are you planning to buy?',
                'required' => true,
                'options'  => [
                    "I'm ready now"       => 'You would like to start as soon as possible.',
                    'Within 30 days'      => 'Actively budgeting for it.',
                    'Within 1-3 months'   => 'Planning ahead.',
                    "I'm just researching" => 'No fixed date, keeping options open.',
                ],
            ],
            'lead_message' => [
                'label'    => 'Tell us anything else you would like us to know',
                'hint'     => 'Example: "I have a mining area in Kitwe and I\'m looking for a detector for deep gold."',
                'required' => false,
            ],
        ];
    }
}

if (!function_exists('hpl_lead_message')) {
    /**
     * Validate and normalise one qualification submission.
     *
     * Returns every answer keyed by column name plus any per-field errors, so a
     * rejected submission can be redisplayed with everything the visitor typed.
     *
     * @param array<string,mixed> $post
     * @param array<string,string> $countries country names accepted for Country
     * @return array{data:array<string,mixed>,errors:array<string,string>,valid:bool}
     */
    function hpl_lead_message(array $post, array $countries): array
    {
        $errors = [];
        $data = [];

        $str = static function (string $key, int $max = 200) use ($post): string {
            $value = trim((string)($post[$key] ?? ''));
            // Strip control characters but keep the visitor's own spacing.
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            if (function_exists('mb_substr')) {
                return mb_substr($value, 0, $max);
            }

            return substr($value, 0, $max);
        };

        // ---- Step 1: contact details ----

        $name = $str('lead_name', 120);
        if ($name === '') {
            $errors['lead_name'] = 'Please enter your full name.';
        } elseif (function_exists('mb_strlen') ? mb_strlen($name) < 2 : strlen($name) < 2) {
            $errors['lead_name'] = 'Please enter your full name.';
        }

        $cc = preg_replace('/\D/', '', $str('lead_cc', 8));
        $digits = preg_replace('/\D/', '', $str('lead_phone', 30));
        $phone = $cc . $digits;
        if ($digits === '' || strlen($digits) < 6) {
            $errors['lead_phone'] = 'Please enter your WhatsApp number.';
        } elseif (strlen($phone) > 15) {
            $errors['lead_phone'] = 'That number looks too long. Please check it.';
        }
        $data['phone'] = $phone;
        $data['country_code'] = $cc !== '' ? '+' . $cc : '';

        $email = $str('lead_email', 160);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['lead_email'] = 'Please check that email address, or leave it blank.';
        }
        $data['email'] = $email !== '' ? $email : null;

        $country = $str('lead_country', 80);
        // Whitelisted against the country list the form itself offers.
        if ($country === '') {
            $errors['lead_country'] = 'Please select your country.';
        } elseif (!in_array($country, $countries, true)) {
            $errors['lead_country'] = 'Please select your country from the list.';
        }
        $data['country'] = $country;

        $city = $str('lead_city', 80);
        if ($city === '') {
            $errors['lead_city'] = 'Please enter your city or town.';
        }
        $data['city'] = $city;

        // ---- Step 2: qualification ----

        $questions = hpl_lead_questions();
        $columns = [
            'lead_looking_for'    => 'looking_for',
            'lead_finding'        => 'finding',
            'lead_experience'     => 'experience',
            'lead_customer_type'  => 'customer_type',
            'lead_timing'         => 'timing',
        ];

        foreach ($columns as $field => $column) {
            $value = $str($field, 120);
            $definition = $questions[$field];
            $options = array_keys($definition['options'] ?? []);
            if ($value === '') {
                $errors[$field] = 'Please choose an option to continue.';
            } elseif (!in_array($value, $options, true)) {
                // Anything not on the list was not offered, so it is not trusted.
                $errors[$field] = 'Please choose one of the options shown.';
            }
            $data[$column] = $value;
        }

        $message = $str('lead_message', 2000);
        $data['knowledge'] = $message;

        $data['needs_advice'] = !empty($post['lead_needs_advice']) ? 1 : 0;
        $data['source'] = $str('lead_source', 80);
        $data['name'] = $name;

        return ['data' => $data, 'errors' => $errors, 'valid' => !$errors];
    }
}

if (!function_exists('hpl_lead_country_names')) {
    /**
     * Country names offered by the form, derived from the phone country list so
     * the selector and the whitelist cannot disagree.
     *
     * @return array<int,string>
     */
    function hpl_lead_country_names(): array
    {
        $names = [];
        foreach (hpl_countries() as $info) {
            $name = trim((string)($info['name'] ?? ''));
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        sort($names);

        return $names;
    }
}

if (!function_exists('hpl_lead_duplicate')) {
    /**
     * True when the same WhatsApp number was captured moments ago.
     *
     * A visitor double-clicking Submit, or a flaky connection replaying the
     * POST, should not produce two rows for one conversation. Anything older
     * than the window is treated as a genuine new enquiry.
     */
    function hpl_lead_duplicate(mysqli $db, string $phone, int $windowSeconds = 300): bool
    {
        if ($phone === '') {
            return false;
        }
        // The cut-off is computed here rather than with "INTERVAL ? SECOND":
        // a placeholder inside INTERVAL is rejected outright by some MySQL and
        // MariaDB builds, which turned the duplicate check into a hard failure.
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $db->prepare('SELECT COUNT(*) FROM leads WHERE phone = ? AND submitted_at > ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $phone, $cutoff);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_row()[0] ?? 0);
        $stmt->close();

        return $count > 0;
    }
}

if (!function_exists('hpl_lead_save')) {
    /**
     * Persist one complete lead.
     *
     * Step 1 and Step 2 arrive in the same request and become the same row.
     */
    function hpl_lead_save(mysqli $db, array $data): bool
    {
        $needsAdvice = (int)($data['needs_advice'] ?? 0);
        // Matches the value the admin list filters and toggles on.
        $status = 'new';

        $values = [
            'name' => (string)($data['name'] ?? ''),
            'phone' => (string)($data['phone'] ?? ''),
            'email' => (string)($data['email'] ?? ''),
            'country_code' => (string)($data['country_code'] ?? ''),
            'country' => (string)($data['country'] ?? ''),
            'city' => (string)($data['city'] ?? ''),
            'looking_for' => (string)($data['looking_for'] ?? ''),
            'finding' => (string)($data['finding'] ?? ''),
            'experience' => (string)($data['experience'] ?? ''),
            'customer_type' => (string)($data['customer_type'] ?? ''),
            'timing' => (string)($data['timing'] ?? ''),
            'knowledge' => (string)($data['knowledge'] ?? ''),
            'needs_advice' => $needsAdvice,
            'source' => (string)($data['source'] ?? ''),
            'status' => $status,
        ];

        // The live table decides the column list. A table still carrying the old
        // form's terrain/target/wants_to_learn columns rejects an INSERT that
        // omits them when they are NOT NULL with no default, so any column the
        // table demands is supplied here rather than assumed away.
        $present = [];
        $required = [];
        $numeric = [];
        try {
            $result = $db->query('SHOW COLUMNS FROM `leads`');
        } catch (mysqli_sql_exception $e) {
            error_log('hpl: cannot read leads columns: ' . $e->getMessage());

            return false;
        }
        while ($result && ($row = $result->fetch_assoc())) {
            $column = (string)$row['Field'];
            $present[$column] = true;
            if (preg_match('/^(?:tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|bit|bool|boolean|year)\b/i', (string)$row['Type'])) {
                $numeric[$column] = true;
            }
            if (strtoupper((string)$row['Null']) === 'NO'
                && $row['Default'] === null
                && stripos((string)$row['Extra'], 'auto_increment') === false) {
                $required[$column] = true;
            }
        }

        $columns = [];
        $types = '';
        $arguments = [];
        foreach ($values as $column => $value) {
            if (!isset($present[$column])) {
                continue;   // column absent on this install, nothing to fill
            }
            $columns[] = '`' . $column . '`';
            $types .= ($numeric[$column] ?? false) ? 'i' : 's';
            $arguments[] = $value;
        }
        // Legacy columns this table still requires but the new form does not ask
        // for. Numeric ones get 0, everything else an empty string.
        foreach (array_keys($required) as $column) {
            if (isset($values[$column])) {
                continue;
            }
            $columns[] = '`' . $column . '`';
            if ($numeric[$column] ?? false) {
                $types .= 'i';
                $arguments[] = 0;
            } else {
                $types .= 's';
                $arguments[] = '';
            }
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $db->prepare(
            'INSERT INTO leads (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
        );
        if (!$stmt) {
            error_log('hpl: lead insert failed to prepare: ' . $db->error);

            return false;
        }

        $stmt->bind_param($types, ...$arguments);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('hpl: lead insert failed: ' . $stmt->error);
        }
        $stmt->close();

        return $ok;
    }
}
