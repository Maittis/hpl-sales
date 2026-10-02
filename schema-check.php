<?php
/**
 * One-off schema diagnostic for the two-step lead form.
 *
 * Upload next to index.php, open it in a browser, read the result, then DELETE
 * IT. It only inspects and adds columns - it never reads or returns lead data.
 *
 * Deletes itself when the schema is already correct, so a successful run leaves
 * nothing behind.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lead-schema.php';

// Null-safe: mysqli can report null for host_info on some socket setups, and
// this page must never fatal - it exists precisely to explain failures.
$h = static fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

echo "<!doctype html><meta charset=\"utf-8\"><title>Lead schema check</title>";
echo "<style>body{font:14px/1.6 system-ui,Segoe UI,sans-serif;max-width:760px;margin:32px auto;color:#111;padding:0 16px}"
    . "pre{background:#f5f6f8;border:1px solid #e3e6ea;border-radius:8px;padding:12px;overflow:auto}"
    . ".ok{color:#0a7a3d;font-weight:600}.bad{color:#b3261e;font-weight:600}.warn{color:#8a6100;font-weight:600}"
    . "h1{font-size:18px}code{background:#f0f1f4;padding:1px 5px;border-radius:4px}</style>";

echo "<h1>Lead schema check</h1>";

echo "<p>PHP " . $h(PHP_VERSION)
    . " &middot; zlib " . (extension_loaded('zlib') ? 'yes' : 'no')
    . " &middot; mysqli " . (extension_loaded('mysqli') ? 'yes' : 'NO') . "</p>";

if (!function_exists('mysqli_connect')) {
    echo "<p class=\"bad\">mysqli extension is not enabled on this server.</p>";
    exit;
}

try {
    $db = db();
} catch (Throwable $e) {
    echo "<p class=\"bad\">Cannot reach the database:</p><pre>" . $h($e->getMessage()) . "</pre>";
    exit;
}
if (!$db) {
    echo "<p class=\"bad\">db() returned no connection. Check config.php.</p>";
    exit;
}
echo "<p class=\"ok\">Connected to " . $h($db->host_info) . " as " . $h($db->host) . ".</p>";

// ---- which tables actually exist? ----
$tables = [];
try {
    $res = $db->query('SHOW TABLES');
    while ($res && ($row = $res->fetch_row())) {
        $tables[] = (string)$row[0];
    }
} catch (mysqli_sql_exception $e) {
    echo "<p class=\"bad\">SHOW TABLES failed:</p><pre>" . $h($e->getMessage()) . "</pre>";
    exit;
}

$leadTables = array_values(array_filter($tables, static fn(string $t): bool => stripos($t, 'lead') !== false));
echo "<h2>Tables</h2><pre>" . ($tables ? $h(implode("\n", $tables)) : '(none)') . "</pre>";
if (!$leadTables) {
    echo "<p class=\"bad\">No table containing \"lead\" was found. The form expects "
        . "<code>leads</code>, so tell the developer the real table name.</p>";
    exit;
}
echo "<p>Lead-like table(s): <code>" . $h(implode('</code>, <code>', $leadTables)) . "</code></p>";

if (!in_array('leads', $tables, true)) {
    echo "<p class=\"bad\">There is no table called <code>leads</code>. "
        . "Rename your table to <code>leads</code>, or tell the developer its real name.</p>";
    exit;
}

// ---- current columns ----
$want = hpl_lead_columns();
$have = [];
try {
    $res = $db->query('SHOW COLUMNS FROM `leads`');
    while ($res && ($row = $res->fetch_assoc())) {
        $have[strtolower((string)$row['Field'])] = $row['Type'];
    }
} catch (mysqli_sql_exception $e) {
    echo "<p class=\"bad\">SHOW COLUMNS FROM leads failed:</p><pre>" . $h($e->getMessage()) . "</pre>";
    exit;
}

$missingBefore = array_diff_key($want, $have);
echo "<h2>Columns</h2>";
echo "<pre>present: " . $h(implode(', ', array_keys($have))) . "</pre>";
echo $missingBefore
    ? "<p class=\"warn\">still missing: <code>" . $h(implode('</code>, <code>', array_keys($missingBefore))) . "</code></p>"
    : "<p class=\"ok\">all required columns already present.</p>";

// ---- attempt the migration, reporting each statement ----
echo "<h2>Migration attempt</h2><pre>";
$added = [];
$failed = [];
if ($missingBefore) {
    foreach ($missingBefore as $column => $definition) {
        $sql = 'ALTER TABLE `leads` ADD COLUMN `' . $column . '` ' . $definition;
        try {
            $db->query($sql);
            $added[] = $column;
            echo "OK   " . $column . "\n";
        } catch (mysqli_sql_exception $e) {
            $failed[$column] = $e->getMessage();
            echo "FAIL " . $column . "  ->  " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "nothing to do\n";
}
echo "</pre>";

// ---- re-check ----
$stillMissing = [];
try {
    $res = $db->query('SHOW COLUMNS FROM `leads`');
    $haveNow = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $haveNow[strtolower((string)$row['Field'])] = true;
    }
    $stillMissing = array_keys(array_diff_key($want, $haveNow));
} catch (mysqli_sql_exception $e) {
    $stillMissing = array_keys($want);
}

if ($stillMissing) {
    echo "<p class=\"bad\">RESULT: <code>" . $h(implode('</code>, <code>', $stillMissing))
        . "</code> could not be added.</p>";
    if ($failed) {
        echo "<p class=\"bad\">Send this exact failure text to the developer:</p>";
        foreach ($failed as $col => $msg) {
            echo "<pre>" . $h($col . ' -> ' . $msg) . "</pre>";
        }
    }
    echo "<p>If the message mentions <em>privilege</em> or <em>denied</em>, the database user "
        . "cannot run ALTER TABLE. Run the statements below in cPanel &rarr; phpMyAdmin, "
        . "then reload this page.</p><pre>";
    foreach ($stillMissing as $col) {
        echo "ALTER TABLE `leads` ADD COLUMN `" . $col . '` ' . $want[$col] . ";\n";
    }
    echo "</pre>";
} else {
    echo "<p class=\"ok\">RESULT: schema is correct - all columns present.</p>";
    // One less public script to remember to remove.
    @unlink(__FILE__);
    echo "<p>This file has deleted itself. Now reload the lead form and submit again.</p>";
}
