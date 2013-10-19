<?php
/**
 * PHP built-in webserver for use with PHPUnit and travis-ci
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2013, The volkszaehler.org project
 * @package default
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

// Command that starts the built-in web server
$command = sprintf(
    (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?
        'php -S %s:%d -t %s > null 2>&1 && echo 0' :
        'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    WEB_SERVER_DOCROOT
);

// Execute the command and store the process ID
$output = array();
exec($command, $output);
$pid = (int) $output[0];
 
echo sprintf(
    '%s - Web server started on %s:%d with PID %d',
    date('r'),
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    $pid
) . PHP_EOL;
 
// Kill the web server when the process ends
if ($pid) {
    register_shutdown_function(function() use ($pid) {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . $pid);
    });
}

?>
