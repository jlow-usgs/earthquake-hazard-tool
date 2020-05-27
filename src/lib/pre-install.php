<?php

// ----------------------------------------------------------------------
// PREAMBLE
//
// Sets up some well-known values for the configuration environment.
//
// Most likely do not need to edit anything in this section.
// ----------------------------------------------------------------------

include_once 'install-funcs.inc.php';

// set default timezone
date_default_timezone_set('UTC');

$OLD_PWD = $_SERVER['PWD'];

// work from lib directory
chdir(dirname($argv[0]));

if ($argv[0] === './pre-install.php' || $_SERVER['PWD'] !== $OLD_PWD) {
  // pwd doesn't resolve symlinks
  $LIB_DIR = $_SERVER['PWD'];
} else {
  // windows doesn't update $_SERVER['PWD']...
  $LIB_DIR = getcwd();
}

$APP_DIR = dirname($LIB_DIR);
$CONF_DIR = $APP_DIR . DIRECTORY_SEPARATOR . 'conf';

$CONFIG_FILE = $CONF_DIR . DIRECTORY_SEPARATOR . 'config.ini';
$APACHE_CONFIG_FILE = $CONF_DIR . DIRECTORY_SEPARATOR . 'httpd.conf';


// ----------------------------------------------------------------------
// CONFIGURATION
//
// Define the configuration parameters necessary in order
// to install/run this application. Some basic parameters are provided
// by default. Ensure that you add matching keys to both the $DEFAULTS
// and $HELP_TEXT arrays so the install process goes smoothly.
//
// This is the most common section to edit.
// ----------------------------------------------------------------------

$DEFAULTS = array(
  'APP_DIR' => $APP_DIR,
  'DATA_DIR' => str_replace('/apps/', '/data/', $APP_DIR),
  'MOUNT_PATH' => '/hazards/interactive',

  'DB_DSN' => 'pgsql:host=localhost;port=5432;dbname=earthquake',
  'DB_SCHEMA' => 'hazard',
  'DB_USER' => 'web',
  'DB_PASS' => '',

  'CURVE_SERVICES' => 'staticcurve|/hazws/staticcurve/1|HazardResponse,dynamiccurve|/nshmp-haz-ws/hazard|DynamicHazardResponse',
  'DEAGG_SERVICES' => 'dynamicdeagg|/nshmp-haz-ws/deagg|deagg/DeaggResponse'
);

$HELP_TEXT = array(
  'APP_DIR' => 'Absolute path to application root directory',
  'DATA_DIR' => 'Absolute path to application data directory',
  'MOUNT_PATH' => 'Url path to application',

  'DB_DSN' => 'Database connection DSN string',
  'DB_SCHEMA' => 'Database schema (if applicable)',
  'DB_USER' => 'Read-only username for database connections',
  'DB_PASS' => 'Password for database user'
);

foreach ($argv as $arg) {
  if ($arg === '--non-interactive') {
    define('NON_INTERACTIVE', true);
  }
}
if (!defined('NON_INTERACTIVE')) {
  define('NON_INTERACTIVE', false);
}


// ----------------------------------------------------------------------
// MAIN
//
// Run the interactive configuration and write configuration files to
// to file system (httpd.conf and config.ini).
//
// Edit this section if this application requires additional installation
// steps such as setting up a database schema etc... When editing this
// section, note the helpful install-funcs.inc.php functions that are
// available to you.
// ----------------------------------------------------------------------

include_once 'configure.php';


// output apache configuration
file_put_contents($APACHE_CONFIG_FILE, '
  # auto generated by ' . __FILE__ . ' at ' . date('r') . '
  Alias ' . $CONFIG['MOUNT_PATH'] . '/data ' . $CONFIG['DATA_DIR'] . '
  Alias ' . $CONFIG['MOUNT_PATH'] . ' ' . $CONFIG['APP_DIR'] . '/htdocs

  RewriteEngine On
  RewriteRule /hazws/staticcurve/1(.*)$ ' .
      $CONFIG['MOUNT_PATH'] . '/curve.ws.php?rewrite=$1 [L,PT]

	<Location ' . $CONFIG['MOUNT_PATH'] . '>
		# apache 2.2
		<IfModule !mod_authz_core.c>
			Order allow,deny
			Allow from all
			<LimitExcept GET OPTIONS>
				Order allow,deny
				Deny from all
			</LimitExcept>
		</IfModule>
		
		# apache 2.4
		<IfModule mod_authz_core.c>
			Require all granted
			<LimitExcept GET OPTIONS>
				Require all denied
			</LimitExcept>
		</IfModule>
	</Location>
');

if (promptYesNo('Would you like to perform database installation', false)) {
  include_once 'db/setup.php';
}
