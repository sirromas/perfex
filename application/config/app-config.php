<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
 * |--------------------------------------------------------------------------
 * | Base Site URL
 * |--------------------------------------------------------------------------
 * |
 * | URL to your CodeIgniter root. Typically this will be your base URL,
 * | WITH a trailing slash:
 * |
 * | http://example.com/
 * |
 * | If this is not set then CodeIgniter will try guess the protocol, domain
 * | and path to your installation. However, you should always configure this
 * | explicitly and never rely on auto-guessing, especially in production
 * | environments.
 * |
 */

define('APP_BASE_URL', 'http://phpstack-102850-326790.cloudwaysapps.com/');

/*
 * |--------------------------------------------------------------------------
 * | Encryption Key
 * | IMPORTANT: Don't change this EVER
 * |--------------------------------------------------------------------------
 * |
 * | If you use the Encryption class, you must set an encryption key.
 * | See the user guide for more info.
 * |
 * | http://codeigniter.com/user_guide/libraries/encryption.html
 * |
 * | Auto updated added on install
 */

define('APP_ENC_KEY', '05aa666196838267edb04a3ebe94c321');

/* Database credentials - Auto added on install */

/* The hostname of your database server. */
define('APP_DB_HOSTNAME', 'localhost');
/* The username used to connect to the database */
define('APP_DB_USERNAME', 'pffxzcfwrq');
/* The password used to connect to the database */
define('APP_DB_PASSWORD', 'zXNb9GwmN2');
/* The name of the database you want to connect to */
define('APP_DB_NAME', 'pffxzcfwrq');

/**
 * Session handler driver
 * By default the database driver will be used.
 *
 * For files session use this config:
 * define('SESS_DRIVER','files');
 * define('SESS_SAVE_PATH',NULL);
 * In case you are having problem with the SESS_SAVE_PATH consult with your hosting provider to set "session.save_path" value to php.ini
 */

define('SESS_DRIVER', 'database');
define('SESS_SAVE_PATH', 'tblsessions');

/*
 * |--------------------------------------------------------------------------
 * | Error Logging Threshold
 * |--------------------------------------------------------------------------
 * |
 * | You can enable error logging by setting a threshold over zero. The
 * | threshold determines what gets logged. Threshold options are:
 * |
 * |	0 = Disables logging, Error logging TURNED OFF
 * |	1 = Error Messages (including PHP errors)
 * |	2 = Debug Messages
 * |	3 = Informational Messages
 * |	4 = All Messages
 * |
 * | You can also pass an array with threshold levels to show individual error types
 * |
 * | 	array(2) = Debug Messages, without Error Messages
 * |
 * | For a live site you'll usually only enable Errors (1) to be logged otherwise
 * | your log files will fill up very fast.
 * |
 */
// $config['log_threshold'] = (ENVIRONMENT !== 'production' ? 1 : 0);
$config['log_threshold'] = 4; // debug everything

/*
 * |--------------------------------------------------------------------------
 * | Error Logging Directory Path
 * |--------------------------------------------------------------------------
 * |
 * | Leave this BLANK unless you would like to set something other than the default
 * | application/logs/ directory. Use a full server path with trailing slash.
 * |
 */
$config['log_path'] = '';

/*
 * |--------------------------------------------------------------------------
 * | Log File Extension
 * |--------------------------------------------------------------------------
 * |
 * | The default filename extension for log files. The default 'php' allows for
 * | protecting the log files via basic scripting, when they are to be stored
 * | under a publicly accessible directory.
 * |
 * | Note: Leaving it blank will default to 'php'.
 * |
 */
$config['log_file_extension'] = '';

/*
 * |--------------------------------------------------------------------------
 * | Log File Permissions
 * |--------------------------------------------------------------------------
 * |
 * | The file system permissions to be applied on newly created log files.
 * |
 * | IMPORTANT: This MUST be an integer (no quotes) and you MUST use octal
 * | integer notation (i.e. 0700, 0644, etc.)
 */
$config['log_file_permissions'] = 0644;

/*
 * |--------------------------------------------------------------------------
 * | Date Format for Logs
 * |--------------------------------------------------------------------------
 * |
 * | Each item that is logged has an associated date. You can use PHP date
 * | codes to set your own date formatting
 * |
 */
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
 * |--------------------------------------------------------------------------
 * | Error Views Directory Path
 * |--------------------------------------------------------------------------
 * |
 * | Leave this BLANK unless you would like to set something other than the default
 * | application/views/errors/ directory. Use a full server path with trailing slash.
 * |
 */
$config['error_views_path'] = '';