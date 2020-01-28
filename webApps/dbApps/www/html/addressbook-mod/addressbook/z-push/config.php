<?php
/***********************************************
* File      :   config.php
* Project   :   Z-Push
* Descr     :   Main configuration file
*
* Created   :   01.10.2007
*
* Copyright 2007 - 2010 Zarafa Deutschland GmbH
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License, version 3,
* as published by the Free Software Foundation with the following additional
* term according to sec. 7:
*
* According to sec. 7 of the GNU Affero General Public License, version 3,
* the terms of the AGPL are supplemented with the following terms:
*
* "Zarafa" is a registered trademark of Zarafa B.V.
* "Z-Push" is a registered trademark of Zarafa Deutschland GmbH
* The licensing of the Program under the AGPL does not imply a trademark license.
* Therefore any rights, title and interest in our trademarks remain entirely with us.
*
* However, if you propagate an unmodified version of the Program you are
* allowed to use the term "Z-Push" to indicate that you distribute the Program.
* Furthermore you may use our trademarks where it is necessary to indicate
* the intended purpose of a product or service provided you use it in accordance
* with honest practices in industrial or commercial matters.
* If you want to propagate modified versions of the Program under the name "Z-Push",
* you may only do so if you have a written permission by Zarafa Deutschland GmbH
* (to acquire a permission please contact Zarafa at trademark@zarafa.com).
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Consult LICENSE file for details
************************************************/

/**********************************************************************************
 *  Default settings
 */
    // Defines the default time zone, change e.g. to "Europe/London" if necessary
    define('TIMEZONE', '');

    // Defines the base path on the server
    define('BASE_PATH', dirname($_SERVER['SCRIPT_FILENAME']). '/');

    // Try to set unlimited timeout
    define('SCRIPT_TIMEOUT', 0);

    //Max size of attachments to display inline. Default is 2 MB
    define('MAX_EMBEDDED_SIZE', 2097152);


/**********************************************************************************
 *  Include the php-addressbook config.php
 */
    include(dirname(__FILE__).DIRECTORY_SEPARATOR.".."
                             .DIRECTORY_SEPARATOR."config"
                             .DIRECTORY_SEPARATOR."config.php");
    include(dirname(__FILE__).DIRECTORY_SEPARATOR.".."
                             .DIRECTORY_SEPARATOR."config"
                             .DIRECTORY_SEPARATOR."cfg.zpush.php");

/**********************************************************************************
 *  Default FileStateMachine settings
 */
    define('STATE_DIR', $zpush_states_dir);


/**********************************************************************************
 *  Logging settings
 */
    define('LOGFILEDIR', $zpush_logs_dir);
    define('LOGFILE', LOGFILEDIR . 'z-push.log');
    define('LOGERRORFILE', LOGFILEDIR . 'z-push-error.log');
    define('LOGLEVEL', $zpush_logs_level);
    define('LOGAUTHFAIL', false);


    // To save e.g. WBXML data only for selected users, add the usernames to the array
    // The data will be saved into a dedicated file per user in the LOGFILEDIR
    define('LOGUSERLEVEL', $zpush_log_users_level);
    $specialLogUsers = $zpush_log_users;


/**********************************************************************************
 *  Mobile settings
 */
    // Device Provisioning
    define('PROVISIONING', true);

    // This option allows the 'loose enforcement' of the provisioning policies for older
    // devices which don't support provisioning (like WM 5 and HTC Android Mail) - dw2412 contribution
    // false (default) - Enforce provisioning for all devices
    // true - allow older devices, but enforce policies on devices which support it
    define('LOOSE_PROVISIONING', false);

    // Default conflict preference
    // Some devices allow to set if the server or PIM (mobile)
    // should win in case of a synchronization conflict
    //   SYNC_CONFLICT_OVERWRITE_SERVER - Server is overwritten, PIM wins
    //   SYNC_CONFLICT_OVERWRITE_PIM    - PIM is overwritten, Server wins (default)
    define('SYNC_CONFLICT_DEFAULT', SYNC_CONFLICT_OVERWRITE_PIM);

    // Global limitation of items to be synchronized
    // The mobile can define a sync back period for calendar and email items
    // For large stores with many items the time period could be limited to a max value
    // If the mobile transmits a wider time period, the defined max value is used
    // Applicable values:
    //   SYNC_FILTERTYPE_ALL (default, no limitation)
    //   SYNC_FILTERTYPE_1DAY, SYNC_FILTERTYPE_3DAYS, SYNC_FILTERTYPE_1WEEK, SYNC_FILTERTYPE_2WEEKS,
    //   SYNC_FILTERTYPE_1MONTH, SYNC_FILTERTYPE_3MONTHS, SYNC_FILTERTYPE_6MONTHS
    define('SYNC_FILTERTIME_MAX', SYNC_FILTERTYPE_ALL);

    // Interval in seconds before checking if there are changes on the server when in Ping.
    // It means the highest time span before a change is pushed to a mobile. Set it to
    // a higher value if you have a high load on the server.
    define('PING_INTERVAL', 30);


    // Set the fileas order contacts. Possible values are:
    // SYNC_FILEAS_FIRSTLAST    - fileas will be "Firstname Middlename Lastname"
    // SYNC_FILEAS_LASTFIRST    - fileas will be "Lastname, Firstname Middlename"
    // SYNC_FILEAS_COMPANYONLY  - fileas will be "Company"
    // SYNC_FILEAS_COMPANYLAST  - fileas will be "Company (Lastname, Firstname Middlename)"
    // SYNC_FILEAS_COMPANYFIRST - fileas will be "Company (Firstname Middlename Lastname)"
    // SYNC_FILEAS_LASTCOMPANY  - fileas will be "Lastname, Firstname Middlename (Company)"
    // SYNC_FILEAS_FIRSTCOMPANY - fileas will be "Firstname Middlename Lastname (Company)"
    // The company-fileas will only be set if a contact has a company set. If one of
    // company-fileas is selected and a contact doesn't have a company set, it will default
    // to SYNC_FILEAS_FIRSTLAST or SYNC_FILEAS_LASTFIRST (depending on if last or first
    // option is selected for company).
    // If SYNC_FILEAS_COMPANYONLY is selected and company of the contact is not set
    // SYNC_FILEAS_FIRSTLAST will be used
    define('FILEAS_ORDER', SYNC_FILEAS_FIRSTLAST);

/**********************************************************************************
 *  Backend settings
 */
    // The data providers that we are using (see configuration below)
    define('BACKEND_PROVIDER', "BackendPhpaddressbook");
//    define('BACKEND_PROVIDER', "BackendVCardDir");
//    define('BACKEND_PROVIDER', "BackendZarafa");
//
//
//    // ************************
//    //  BackendZarafa settings
//    // ************************
//    // Defines the server to which we want to connect
//    define('MAPI_SERVER', 'file:///var/run/zarafa');
//
//
//    // ************************
//    //  BackendIMAP settings
//    // ************************
//    // Defines the server to which we want to connect
//    define('IMAP_SERVER', 'localhost');
//    // connecting to default port (143)
//    define('IMAP_PORT', 143);
//    // best cross-platform compatibility (see http://php.net/imap_open for options)
//    define('IMAP_OPTIONS', '/notls/norsh');
//    // overwrite the "from" header if it isn't set when sending emails
//    // options: 'username'    - the username will be set (usefull if your login is equal to your emailaddress)
//    //        'domain'    - the value of the "domain" field is used
//    //        '@mydomain.com' - the username is used and the given string will be appended
//    define('IMAP_DEFAULTFROM', '');
//    // copy outgoing mail to this folder. If not set z-push will try the default folders
//    define('IMAP_SENTFOLDER', '');
//    // forward messages inline (default false - as attachment)
//    define('IMAP_INLINE_FORWARD', false);
//    // use imap_mail() to send emails (default) - if false mail() is used
//    define('IMAP_USE_IMAPMAIL', true);
//
//
//    // ************************
//    //  BackendMaildir settings
//    // ************************
//    define('MAILDIR_BASE', '/tmp');
//    define('MAILDIR_SUBDIR', 'Maildir');
//
//    // **********************
//    //  BackendVCardDir settings
//    // **********************
//    define('VCARDDIR_DIR', '/home/www/z-push_vcards');


    // **********************
    //  BackendPhpaddressbook settings
    // **********************

    //
    // Define the tablenames,
    // if not defined in "config.php"
    if(!isset($table))         $table         = "addressbook";
    if(!isset($month_lookup))  $month_lookup  = "month_lookup";
    if(!isset($table_groups))  $table_groups  = "group_list";
    if(!isset($table_grp_adr)) $table_grp_adr = "address_in_groups";
    if(!isset($usertable))     $usertable     = "users";

// Apply the table prefix, if available
$table         = $table_prefix.$table;
$month_lookup  = $table_prefix.$month_lookup;
$table_groups  = $table_prefix.$table_groups;
$table_grp_adr = $table_prefix.$table_grp_adr;
$usertable     = $table_prefix.$usertable;

// Assemble the statements
if(true || $group_name == "") {
    $base_select = " * ";
    $base_from  = $table;
 } else {

    if($group_name == "[none]" || $group_name == "[no group]") {
      $base_select = " * ";
      $base_from   = "$table";
      $base_where  .= "AND $table.id not in (select distinct id from $table_grp_adr)";
    } elseif(isset($_REQUEST['nosubgroups']) ) {
      $base_select = " * ";
      $base_from  = "$table_grp_adr, $table_groups, $table";
      $base_where .= "AND $table.id = $table_grp_adr.id "
                   ."AND $table_grp_adr.group_id  = $table_groups.group_id "
                   ."AND $table_groups.group_name = '$group_name'";
    } else {
      $base_select = "DISTINCT $table.*";
      $base_from   = "$table_grp_adr, $sql_from, $table";
      $base_where  .= "AND $table.id = $table_grp_adr.id "
                    ."AND $table_grp_adr.group_id  = g0.group_id "
                    ."AND ($sql_where)";
    }
 }
$domain_id = 0;


/**********************************************************************************
 *  Search provider settings
 *
 *  Alternative backend to perform SEARCH requests (GAL search)
 *  By default the main Backend defines the preferred search functionality.
 *  If set, the Search Provider will always be preferred.
 *  Use 'BackendSearchLDAP' to search in a LDAP directory (see backend/searchldap/config.php)
 */
    define('SEARCH_PROVIDER', '');
    // Time in seconds for the server search. Setting it too high might result in timeout.
    // Setting it too low might not return all results. Default is 10.
    define('SEARCH_WAIT', 10);
    // The maximum number of results to send to the client. Setting it too high
    // might result in timeout. Default is 10.
    define('SEARCH_MAXRESULTS', 10);


/**********************************************************************************
 *  Synchronize additional folders to all mobiles
 *
 *  With this feature, special folders can be synchronized to all mobiles.
 *  This is useful for e.g. global company contacts.
 *
 *  This feature is supported only by certain devices, like iPhones.
 *  Check the compatibility list for supported devices:
 *      http://z-push.sf.net/compatibility
 *
 *  To synchronize a folder, add a section setting all parameters as below:
 *      store:      the ressource where the folder is located.
 *                  Zarafa users use 'SYSTEM' for the 'Public Folder'
 *      folderid:   folder id of the folder to be synchronized
 *      name:       name to be displayed on the mobile device
 *      type:       supported types are:
 *                      SYNC_FOLDER_TYPE_USER_CONTACT
 *                      SYNC_FOLDER_TYPE_USER_APPOINTMENT
 *                      SYNC_FOLDER_TYPE_USER_TASK
 *                      SYNC_FOLDER_TYPE_USER_MAIL
 *
 *  Additional notes:
 *  - on Zarafa systems use backend/zarafa/listfolders.php script to get a list
 *    of available folders
 *
 *  - all Z-Push users must have full writing permissions (secretary rights) so
 *    the configured folders can be synchronized to the mobile
 *
 *  - this feature is only partly suitable for multi-tenancy environments,
 *    as ALL users from ALL tenents need access to the configured store & folder.
 *    When configuring a public folder, this will cause problems, as each user has
 *    a different public folder in his tenant, so the folder are not available.

 *  - changing this configuration could cause HIGH LOAD on the system, as all
 *    connected devices will be updated and load the data contained in the
 *    added/modified folders.
 */

    $additionalFolders = array(
        // demo entry for the synchronization of contacts from the public folder.
        // uncomment (remove '/*' '*/') and fill in the folderid
/*
        array(
            'store'     => "SYSTEM",
            'folderid'  => "",
            'name'      => "Public Contacts",
            'type'      => SYNC_FOLDER_TYPE_USER_CONTACT,
        ),
*/
    );

?>