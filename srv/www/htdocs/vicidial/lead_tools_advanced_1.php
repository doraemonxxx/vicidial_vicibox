<?php
# lead_tools_advanced.php - Various tools for lead basic lead management, advanced version.
#
# Copyright (C) 2022  Matt Florell,Michael Cargile <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 131016-1948 - Initial Build based upon lead_tools.php
# 140113-0853 - Added USERONLY to ANYONE callback switcher
# 140606-1242 - Added the state field as an option to Move, Update, and Delete
# 141007-2036 - Finalized adding QXZ translation to all admin files
# 141229-2028 - Added code for on-the-fly language translations display
# 150312-1508 - Allow for single quotes in vicidial_list data fields
# 170225-1436 - Added date/time range options
# 170409-1554 - Added IP List validation code
# 170711-1104 - Added screen colors
# 170819-1003 - Added allow_manage_active_lists option, Changed list selection to multi
# 191119-1817 - Fixes for translations compatibility, issue #1142
# 210607-0923 - Added option to reset called_count to 0
# 220228-1012 - Added allow_web_debug system setting
#

require_once("globalFn.php");

$version = '2.14-13';
$build = '220228-1012';

# This limit is to prevent data inconsistancies.
# If there are too many leads in a list this
# script might not finish before the php execution limit.
$list_lead_limit = 100000;

# maximum call count the script will work with
$max_count = 20;

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW = $_SERVER['PHP_AUTH_PW'];
$PHP_SELF = $_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i', '.php', $PHP_SELF);
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");

$DB = 0;
$copy_submit = "";
$move_submit = "";
$update_submit = "";
$delete_submit = "";
$reset_called_count_submit = "";
$callback_submit = "";
$confirm_move = "";
$confirm_copy = "";
$confirm_update = "";
$confirm_delete = "";
$confirm_reset_called_count = "";
$confirm_callback = "";

if (isset($_GET["DB"])) {
	$DB = $_GET["DB"];
} elseif (isset($_POST["DB"])) {
	$DB = $_POST["DB"];
}

### copy submit
if (isset($_GET["copy_submit"])) {
	$copy_submit = $_GET["copy_submit"];
} elseif (isset($_POST["copy_submit"])) {
	$copy_submit = $_POST["copy_submit"];
}
### copy submit

if (isset($_GET["move_submit"])) {
	$move_submit = $_GET["move_submit"];
} elseif (isset($_POST["move_submit"])) {
	$move_submit = $_POST["move_submit"];
}
if (isset($_GET["update_submit"])) {
	$update_submit = $_GET["update_submit"];
} elseif (isset($_POST["update_submit"])) {
	$update_submit = $_POST["update_submit"];
}
if (isset($_GET["delete_submit"])) {
	$delete_submit = $_GET["delete_submit"];
} elseif (isset($_POST["delete_submit"])) {
	$delete_submit = $_POST["delete_submit"];
}
if (isset($_GET["reset_called_count_submit"])) {
	$reset_called_count_submit = $_GET["reset_called_count_submit"];
} elseif (isset($_POST["reset_called_count_submit"])) {
	$reset_called_count_submit = $_POST["reset_called_count_submit"];
}
if (isset($_GET["callback_submit"])) {
	$callback_submit = $_GET["callback_submit"];
} elseif (isset($_POST["callback_submit"])) {
	$callback_submit = $_POST["callback_submit"];
}
if (isset($_GET["confirm_move"])) {
	$confirm_move = $_GET["confirm_move"];
} elseif (isset($_POST["confirm_move"])) {
	$confirm_move = $_POST["confirm_move"];
}

if (isset($_GET["confirm_copy"])) {
	$confirm_copy = $_GET["confirm_copy"];
} elseif (isset($_POST["confirm_copy"])) {
	$confirm_copy = $_POST["confirm_copy"];
}


if (isset($_GET["confirm_update"])) {
	$confirm_move = $_GET["confirm_update"];
} elseif (isset($_POST["confirm_update"])) {
	$confirm_update = $_POST["confirm_update"];
}



if (isset($_GET["confirm_delete"])) {
	$confirm_delete = $_GET["confirm_delete"];
} elseif (isset($_POST["confirm_delete"])) {
	$confirm_delete = $_POST["confirm_delete"];
}
if (isset($_GET["confirm_reset_called_count"])) {
	$confirm_reset_called_count = $_GET["confirm_reset_called_count"];
} elseif (isset($_POST["confirm_reset_called_count"])) {
	$confirm_reset_called_count = $_POST["confirm_reset_called_count"];
}
if (isset($_GET["confirm_callback"])) {
	$confirm_callback = $_GET["confirm_callback"];
} elseif (isset($_POST["confirm_callback"])) {
	$confirm_callback = $_POST["confirm_callback"];
}
# Several sets of variable inputs are further down in the code as well

$DB = preg_replace('/[^0-9]/', '', $DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,outbound_autodial_active,sounds_central_control_active,enable_languages,language_method,admin_screen_colors,report_default_format,allow_manage_active_lists,allow_web_debug FROM system_settings;";
$sys_settings_rslt = mysql_to_mysqli($sys_settings_stmt, $link);
#if ($DB) {echo "$sys_settings_stmt\n";}
$num_rows = mysqli_num_rows($sys_settings_rslt);
if ($num_rows > 0) {
	$sys_settings_row = mysqli_fetch_row($sys_settings_rslt);
	$non_latin =						$sys_settings_row[0];
	$SSoutbound_autodial_active =		$sys_settings_row[1];
	$sounds_central_control_active =	$sys_settings_row[2];
	$SSenable_languages =				$sys_settings_row[3];
	$SSlanguage_method =				$sys_settings_row[4];
	$SSadmin_screen_colors =			$sys_settings_row[5];
	$SSreport_default_format =			$sys_settings_row[6];
	$SSallow_manage_active_lists =		$sys_settings_row[7];
	$SSallow_web_debug =				$sys_settings_row[8];
} else {
	# there is something really weird if there are no system settings
	exit;
}
if ($SSallow_web_debug < 1) {
	$DB = 0;
}
##### END SETTINGS LOOKUP #####
###########################################

$copy_submit =  preg_replace('/[^-_0-9a-zA-Z]/', '', $copy_submit);
$move_submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $move_submit);
$update_submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $update_submit);
$delete_submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $delete_submit);
$reset_called_count_submit = preg_replace('/[^- _0-9a-zA-Z]/', '', $reset_called_count_submit);
$callback_submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $callback_submit);
$confirm_move = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_move);
$confirm_copy = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_copy);
$confirm_update = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_update);
$confirm_delete = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_delete);
$confirm_reset_called_count = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_reset_called_count);
$confirm_callback = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_callback);
$delete_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $delete_status);
$reset_called_count_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $reset_called_count_status);

if ($non_latin < 1) {
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
} else {
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
}

$DBlink = '';
if ($DB) {
	$DBlink = "?DB=$DB";
	echo "<p>DB = $DB | " . _QXZ("move_submit") . " = $move_submit | " . _QXZ("copy_submit") . " = $copy_submit | " . _QXZ("update_submit") . " = $update_submit | " . _QXZ("delete_submit") . " = $delete_submit | " . _QXZ("reset_called_count_submit") . " = $reset_called_count_submit | " . _QXZ("callback_submit") . " = $callback_submit | " . _QXZ("confirm_move") . " = $confirm_move | " . _QXZ("confirm_copy") . " = $confirm_copy | " . _QXZ("confirm_update") . " = $confirm_update | " . _QXZ("confirm_delete") . " = $confirm_delete | " . _QXZ("confirm_reset_called_count") . " = $confirm_reset_called_count | " . _QXZ("confirm_callback") . " = $confirm_callback</p>";
}

$stmt = "SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {
	echo "|$stmt|\n";
}
$rslt = mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0) {
	$row = mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
}

$auth = 0;
$auth_message = user_authorization($PHP_AUTH_USER, $PHP_AUTH_PW, '', 1, 0);
if ($auth_message == 'GOOD') {
	$auth = 1;
}

if ($auth < 1) {
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK') {
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
	}
	if ($auth_message == 'IPBLOCK') {
		$VDdisplayMESSAGE = _QXZ("Your IP Address is not allowed") . ": $ip";
		Header("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
	}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
}

header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header("Pragma: no-cache");      // HTTP/1.0

# valid user
$rights_stmt = "SELECT load_leads,user_group, delete_lists, modify_leads, modify_lists from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {
	echo "|$stmt|\n";
}
echo "$rights_stmt\n";
$rights_rslt = mysql_to_mysqli($rights_stmt, $link);
$rights_row = mysqli_fetch_row($rights_rslt);
$load_leads =      $rights_row[0];
$user_group =      $rights_row[1];
$delete_lists =  $rights_row[2];
$modify_leads =  $rights_row[3];
$modify_lists =  $rights_row[4];

# check their permissions
if ($load_leads < 1) {
	header("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to load leads") . "\n";
	exit;
}
if ($modify_leads < 1) {
	header("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads") . "\n";
	exit;
}
if ($modify_lists < 1) {
	header("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify lists") . "\n";
	exit;
}

$SSmenu_background = '015B91';
$SSframe_background = 'D9E6FE';
$SSstd_row1_background = '9BB9FB';
$SSstd_row2_background = 'B9CBFD';
$SSstd_row3_background = '8EBCFD';
$SSstd_row4_background = 'B6D3FC';
$SSstd_row5_background = 'A3C3D6';
$SSalt_row1_background = 'BDFFBD';
$SSalt_row2_background = '99FF99';
$SSalt_row3_background = 'CCFFCC';

if ($SSadmin_screen_colors != 'default') {
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt = mysql_to_mysqli($stmt, $link);
	if ($DB) {
		echo "$stmt\n";
	}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0) {
		$row = mysqli_fetch_row($rslt);
		$SSmenu_background =		$row[0];
		$SSframe_background =		$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =				$row[10];
	}
}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

$selected_logo = "./images/vicidial_admin_web_logo.png";
$selected_small_logo = "./images/vicidial_admin_web_logo.png";
$logo_new = 0;
$logo_old = 0;
$logo_small_old = 0;
if (file_exists('./images/vicidial_admin_web_logo.png')) {
	$logo_new++;
}
if (file_exists('vicidial_admin_web_logo_small.gif')) {
	$logo_small_old++;
}
if (file_exists('vicidial_admin_web_logo.gif')) {
	$logo_old++;
}
if ($SSweb_logo == 'default_new') {
	$selected_logo = "./images/vicidial_admin_web_logo.png";
	$selected_small_logo = "./images/vicidial_admin_web_logo.png";
}
if (($SSweb_logo == 'default_old') and ($logo_old > 0)) {
	$selected_logo = "./vicidial_admin_web_logo.gif";
	$selected_small_logo = "./vicidial_admin_web_logo_small.gif";
}
if (($SSweb_logo != 'default_new') and ($SSweb_logo != 'default_old')) {
	if (file_exists("./images/vicidial_admin_web_logo$SSweb_logo")) {
		$selected_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		$selected_small_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
	}
}


echo "<html>\n";
echo "<head>\n";
echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- VERSION: <?php echo $version ?>     BUILD: <?php echo $build ?> -->\n";
?>
<script type="text/javascript">
	window.onload = function() {

		// copy lead functions
		document.getElementById("enable_copy_status").onclick = enableCopyStatus;
		document.getElementById("enable_copy_count").onclick = enableCopyCount;
		document.getElementById("enable_copy_country_code").onclick = enableCopyCountryCode;
		document.getElementById("enable_copy_vendor_lead_code").onclick = enableCopyVendorLeadCode;
		document.getElementById("enable_copy_source_id").onclick = enableCopyCountrySourceId;
		document.getElementById("enable_copy_owner").onclick = enableCopyOwner;
		document.getElementById("enable_copy_state").onclick = enableCopyState;
		document.getElementById("enable_copy_entry_date").onclick = enableCopyEntryDate;
		document.getElementById("enable_copy_modify_date").onclick = enableCopyModifyDate;
		document.getElementById("enable_copy_security_phrase").onclick = enableCopySecurityPhrase;


		// move functions initialization
		document.getElementById("enable_move_status").onclick = enableMoveStatus;
		document.getElementById("enable_move_count").onclick = enableMoveCount;
		document.getElementById("enable_move_country_code").onclick = enableMoveCountryCode;
		document.getElementById("enable_move_vendor_lead_code").onclick = enableMoveVendorLeadCode;
		document.getElementById("enable_move_source_id").onclick = enableMoveCountrySourceId;
		document.getElementById("enable_move_owner").onclick = enableMoveOwner;
		document.getElementById("enable_move_state").onclick = enableMoveState;
		document.getElementById("enable_move_entry_date").onclick = enableMoveEntryDate;
		document.getElementById("enable_move_modify_date").onclick = enableMoveModifyDate;
		document.getElementById("enable_move_security_phrase").onclick = enableMoveSecurityPhrase;

		// update functions initialization
		document.getElementById("enable_update_from_status").onclick = enableUpdateFromStatus;
		document.getElementById("enable_update_count").onclick = enableUpdateCount;
		document.getElementById("enable_update_country_code").onclick = enableUpdateCountryCode;
		document.getElementById("enable_update_vendor_lead_code").onclick = enableUpdateVendorLeadCode;
		document.getElementById("enable_update_source_id").onclick = enableUpdateCountrySourceId;
		document.getElementById("enable_update_owner").onclick = enableUpdateOwner;
		document.getElementById("enable_update_state").onclick = enableUpdateState;
		document.getElementById("enable_update_entry_date").onclick = enableUpdateEntryDate;
		document.getElementById("enable_update_modify_date").onclick = enableUpdateModifyDate;
		document.getElementById("enable_update_security_phrase").onclick = enableUpdateSecurityPhrase;

		// delete functions initialization
		document.getElementById("enable_delete_count").onclick = enableDeleteCount;
		document.getElementById("enable_delete_country_code").onclick = enableDeleteCountryCode;
		document.getElementById("enable_delete_vendor_lead_code").onclick = enableDeleteVendorLeadCode;
		document.getElementById("enable_delete_source_id").onclick = enableDeleteCountrySourceId;
		document.getElementById("enable_delete_owner").onclick = enableDeleteOwner;
		document.getElementById("enable_delete_state").onclick = enableDeleteState;
		document.getElementById("enable_delete_entry_date").onclick = enableDeleteEntryDate;
		document.getElementById("enable_delete_modify_date").onclick = enableDeleteModifyDate;
		document.getElementById("enable_delete_security_phrase").onclick = enableDeleteSecurityPhrase;
		document.getElementById("enable_delete_lead_id").onclick = enableDeleteLeadId;

		// reset called count functions initialization
		document.getElementById("enable_reset_called_count_count").onclick = enableResetCallCountCount;
		document.getElementById("enable_reset_called_count_country_code").onclick = enableResetCallCountCountryCode;
		document.getElementById("enable_reset_called_count_vendor_lead_code").onclick = enableResetCallCountVendorLeadCode;
		document.getElementById("enable_reset_called_count_source_id").onclick = enableResetCallCountCountrySourceId;
		document.getElementById("enable_reset_called_count_owner").onclick = enableResetCallCountOwner;
		document.getElementById("enable_reset_called_count_state").onclick = enableResetCallCountState;
		document.getElementById("enable_reset_called_count_entry_date").onclick = enableResetCallCountEntryDate;
		document.getElementById("enable_reset_called_count_modify_date").onclick = enableResetCallCountModifyDate;
		document.getElementById("enable_reset_called_count_security_phrase").onclick = enableResetCallCountSecurityPhrase;
		document.getElementById("enable_reset_called_count_lead_id").onclick = enableResetCallCountLeadId;

		// callback functions initialization
		document.getElementById("enable_callback_entry_date").onclick = enableCallbackEntryDate;
		document.getElementById("enable_callback_callback_date").onclick = enableCallbackCallbackDate;
	}


	// copy lead functions JS start
	function enableCopyStatus() {
		if (document.getElementById("enable_copy_status").checked) {
			document.getElementById("copy_status").disabled = false;
		} else {
			document.getElementById("copy_status").disabled = true;
		}
	}

	function enableCopyCountryCode() {
		if (document.getElementById("enable_copy_country_code").checked) {
			document.getElementById("copy_country_code").disabled = false;
		} else {
			document.getElementById("copy_country_code").disabled = true;
		}
	}

	function enableCopyVendorLeadCode() {
		if (document.getElementById("enable_copy_vendor_lead_code").checked) {
			document.getElementById("copy_vendor_lead_code").disabled = false;
		} else {
			document.getElementById("copy_vendor_lead_code").disabled = true;
		}
	}

	function enableCopyCountrySourceId() {
		if (document.getElementById("enable_copy_source_id").checked) {
			document.getElementById("copy_source_id").disabled = false;
		} else {
			document.getElementById("copy_source_id").disabled = true;
		}
	}

	function enableCopyOwner() {
		if (document.getElementById("enable_copy_owner").checked) {
			document.getElementById("copy_owner").disabled = false;
		} else {
			document.getElementById("copy_owner").disabled = true;
		}
	}

	function enableCopyState() {
		if (document.getElementById("enable_copy_state").checked) {
			document.getElementById("copy_state").disabled = false;
		} else {
			document.getElementById("copy_state").disabled = true;
		}
	}

	function enableCopyEntryDate() {
		if (document.getElementById("enable_copy_entry_date").checked) {
			document.getElementById("copy_entry_date").disabled = false;
			document.getElementById("copy_entry_date_end").disabled = false;
			document.getElementById("copy_entry_date_op").disabled = false;
		} else {
			document.getElementById("copy_entry_date").disabled = true;
			document.getElementById("copy_entry_date_end").disabled = true;
			document.getElementById("copy_entry_date_op").disabled = true;
		}
	}

	function enableCopyModifyDate() {
		if (document.getElementById("enable_copy_modify_date").checked) {
			document.getElementById("copy_modify_date").disabled = false;
			document.getElementById("copy_modify_date_end").disabled = false;
			document.getElementById("copy_modify_date_op").disabled = false;
		} else {
			document.getElementById("copy_modify_date").disabled = true;
			document.getElementById("copy_modify_date_end").disabled = true;
			document.getElementById("copy_modify_date_op").disabled = true;
		}
	}

	function enableCopySecurityPhrase() {
		if (document.getElementById("enable_copy_security_phrase").checked) {
			document.getElementById("copy_security_phrase").disabled = false;
		} else {
			document.getElementById("copy_security_phrase").disabled = true;
		}
	}

	function enableCopyCount() {
		if (document.getElementById("enable_copy_count").checked) {
			document.getElementById("copy_count_op").disabled = false;
			document.getElementById("copy_count_num").disabled = false;
		} else {
			document.getElementById("copy_count_op").disabled = true;
			document.getElementById("copy_count_num").disabled = true;
		}
	}
	// copy lead function JS end


	// move functions
	function enableMoveStatus() {
		if (document.getElementById("enable_move_status").checked) {
			document.getElementById("move_status").disabled = false;
		} else {
			document.getElementById("move_status").disabled = true;
		}
	}

	function enableMoveCountryCode() {
		if (document.getElementById("enable_move_country_code").checked) {
			document.getElementById("move_country_code").disabled = false;
		} else {
			document.getElementById("move_country_code").disabled = true;
		}
	}

	function enableMoveVendorLeadCode() {
		if (document.getElementById("enable_move_vendor_lead_code").checked) {
			document.getElementById("move_vendor_lead_code").disabled = false;
		} else {
			document.getElementById("move_vendor_lead_code").disabled = true;
		}
	}

	function enableMoveCountrySourceId() {
		if (document.getElementById("enable_move_source_id").checked) {
			document.getElementById("move_source_id").disabled = false;
		} else {
			document.getElementById("move_source_id").disabled = true;
		}
	}

	function enableMoveOwner() {
		if (document.getElementById("enable_move_owner").checked) {
			document.getElementById("move_owner").disabled = false;
		} else {
			document.getElementById("move_owner").disabled = true;
		}
	}

	function enableMoveState() {
		if (document.getElementById("enable_move_state").checked) {
			document.getElementById("move_state").disabled = false;
		} else {
			document.getElementById("move_state").disabled = true;
		}
	}

	function enableMoveEntryDate() {
		if (document.getElementById("enable_move_entry_date").checked) {
			document.getElementById("move_entry_date").disabled = false;
			document.getElementById("move_entry_date_end").disabled = false;
			document.getElementById("move_entry_date_op").disabled = false;
		} else {
			document.getElementById("move_entry_date").disabled = true;
			document.getElementById("move_entry_date_end").disabled = true;
			document.getElementById("move_entry_date_op").disabled = true;
		}
	}

	function enableMoveModifyDate() {
		if (document.getElementById("enable_move_modify_date").checked) {
			document.getElementById("move_modify_date").disabled = false;
			document.getElementById("move_modify_date_end").disabled = false;
			document.getElementById("move_modify_date_op").disabled = false;
		} else {
			document.getElementById("move_modify_date").disabled = true;
			document.getElementById("move_modify_date_end").disabled = true;
			document.getElementById("move_modify_date_op").disabled = true;
		}
	}

	function enableMoveSecurityPhrase() {
		if (document.getElementById("enable_move_security_phrase").checked) {
			document.getElementById("move_security_phrase").disabled = false;
		} else {
			document.getElementById("move_security_phrase").disabled = true;
		}
	}

	function enableMoveCount() {
		if (document.getElementById("enable_move_count").checked) {
			document.getElementById("move_count_op").disabled = false;
			document.getElementById("move_count_num").disabled = false;
		} else {
			document.getElementById("move_count_op").disabled = true;
			document.getElementById("move_count_num").disabled = true;
		}
	}

	// update functions
	function enableUpdateFromStatus() {
		if (document.getElementById("enable_update_from_status").checked) {
			document.getElementById("update_from_status").disabled = false;
		} else {
			document.getElementById("update_from_status").disabled = true;
		}
	}

	function enableUpdateCountryCode() {
		if (document.getElementById("enable_update_country_code").checked) {
			document.getElementById("update_country_code").disabled = false;
		} else {
			document.getElementById("update_country_code").disabled = true;
		}
	}

	function enableUpdateVendorLeadCode() {
		if (document.getElementById("enable_update_vendor_lead_code").checked) {
			document.getElementById("update_vendor_lead_code").disabled = false;
		} else {
			document.getElementById("update_vendor_lead_code").disabled = true;
		}
	}

	function enableUpdateCountrySourceId() {
		if (document.getElementById("enable_update_source_id").checked) {
			document.getElementById("update_source_id").disabled = false;
		} else {
			document.getElementById("update_source_id").disabled = true;
		}
	}

	function enableUpdateOwner() {
		if (document.getElementById("enable_update_owner").checked) {
			document.getElementById("update_owner").disabled = false;
		} else {
			document.getElementById("update_owner").disabled = true;
		}
	}

	function enableUpdateState() {
		if (document.getElementById("enable_update_state").checked) {
			document.getElementById("update_state").disabled = false;
		} else {
			document.getElementById("update_state").disabled = true;
		}
	}

	function enableUpdateEntryDate() {
		if (document.getElementById("enable_update_entry_date").checked) {
			document.getElementById("update_entry_date").disabled = false;
			document.getElementById("update_entry_date_end").disabled = false;
			document.getElementById("update_entry_date_op").disabled = false;
		} else {
			document.getElementById("update_entry_date").disabled = true;
			document.getElementById("update_entry_date_end").disabled = true;
			document.getElementById("update_entry_date_op").disabled = true;
		}
	}

	function enableUpdateModifyDate() {
		if (document.getElementById("enable_update_modify_date").checked) {
			document.getElementById("update_modify_date").disabled = false;
			document.getElementById("update_modify_date_end").disabled = false;
			document.getElementById("update_modify_date_op").disabled = false;
		} else {
			document.getElementById("update_modify_date").disabled = true;
			document.getElementById("update_modify_date_end").disabled = true;
			document.getElementById("update_modify_date_op").disabled = true;
		}
	}

	function enableUpdateSecurityPhrase() {
		if (document.getElementById("enable_update_security_phrase").checked) {
			document.getElementById("update_security_phrase").disabled = false;
		} else {
			document.getElementById("update_security_phrase").disabled = true;
		}
	}

	function enableUpdateCount() {
		if (document.getElementById("enable_update_count").checked) {
			document.getElementById("update_count_op").disabled = false;
			document.getElementById("update_count_num").disabled = false;
		} else {
			document.getElementById("update_count_op").disabled = true;
			document.getElementById("update_count_num").disabled = true;
		}
	}

	// delete functions
	function enableDeleteCount() {
		if (document.getElementById("enable_delete_count").checked) {
			document.getElementById("delete_count_op").disabled = false;
			document.getElementById("delete_count_num").disabled = false;
		} else {
			document.getElementById("delete_count_op").disabled = true;
			document.getElementById("delete_count_num").disabled = true;
		}
	}

	function enableDeleteCountryCode() {
		if (document.getElementById("enable_delete_country_code").checked) {
			document.getElementById("delete_country_code").disabled = false;
		} else {
			document.getElementById("delete_country_code").disabled = true;
		}
	}

	function enableDeleteVendorLeadCode() {
		if (document.getElementById("enable_delete_vendor_lead_code").checked) {
			document.getElementById("delete_vendor_lead_code").disabled = false;
		} else {
			document.getElementById("delete_vendor_lead_code").disabled = true;
		}
	}

	function enableDeleteCountrySourceId() {
		if (document.getElementById("enable_delete_source_id").checked) {
			document.getElementById("delete_source_id").disabled = false;
		} else {
			document.getElementById("delete_source_id").disabled = true;
		}
	}

	function enableDeleteOwner() {
		if (document.getElementById("enable_delete_owner").checked) {
			document.getElementById("delete_owner").disabled = false;
		} else {
			document.getElementById("delete_owner").disabled = true;
		}
	}

	function enableDeleteState() {
		if (document.getElementById("enable_delete_state").checked) {
			document.getElementById("delete_state").disabled = false;
		} else {
			document.getElementById("delete_state").disabled = true;
		}
	}

	function enableDeleteEntryDate() {
		if (document.getElementById("enable_delete_entry_date").checked) {
			document.getElementById("delete_entry_date").disabled = false;
			document.getElementById("delete_entry_date_end").disabled = false;
			document.getElementById("delete_entry_date_op").disabled = false;
		} else {
			document.getElementById("delete_entry_date").disabled = true;
			document.getElementById("delete_entry_date_end").disabled = true;
			document.getElementById("delete_entry_date_op").disabled = true;
		}
	}

	function enableDeleteModifyDate() {
		if (document.getElementById("enable_delete_modify_date").checked) {
			document.getElementById("delete_modify_date").disabled = false;
			document.getElementById("delete_modify_date_end").disabled = false;
			document.getElementById("delete_modify_date_op").disabled = false;
		} else {
			document.getElementById("delete_modify_date").disabled = true;
			document.getElementById("delete_modify_date_end").disabled = true;
			document.getElementById("delete_modify_date_op").disabled = true;
		}
	}

	function enableDeleteSecurityPhrase() {
		if (document.getElementById("enable_delete_security_phrase").checked) {
			document.getElementById("delete_security_phrase").disabled = false;
		} else {
			document.getElementById("delete_security_phrase").disabled = true;
		}
	}

	function enableDeleteLeadId() {
		if (document.getElementById("enable_delete_lead_id").checked) {
			document.getElementById("delete_lead_id").disabled = false;
		} else {
			document.getElementById("delete_lead_id").disabled = true;
		}
	}

	// reset called count functions
	function enableResetCallCountCount() {
		if (document.getElementById("enable_reset_called_count_count").checked) {
			document.getElementById("reset_called_count_count_op").disabled = false;
			document.getElementById("reset_called_count_count_num").disabled = false;
		} else {
			document.getElementById("reset_called_count_count_op").disabled = true;
			document.getElementById("reset_called_count_count_num").disabled = true;
		}
	}

	function enableResetCallCountCountryCode() {
		if (document.getElementById("enable_reset_called_count_country_code").checked) {
			document.getElementById("reset_called_count_country_code").disabled = false;
		} else {
			document.getElementById("reset_called_count_country_code").disabled = true;
		}
	}

	function enableResetCallCountVendorLeadCode() {
		if (document.getElementById("enable_reset_called_count_vendor_lead_code").checked) {
			document.getElementById("reset_called_count_vendor_lead_code").disabled = false;
		} else {
			document.getElementById("reset_called_count_vendor_lead_code").disabled = true;
		}
	}

	function enableResetCallCountCountrySourceId() {
		if (document.getElementById("enable_reset_called_count_source_id").checked) {
			document.getElementById("reset_called_count_source_id").disabled = false;
		} else {
			document.getElementById("reset_called_count_source_id").disabled = true;
		}
	}

	function enableResetCallCountOwner() {
		if (document.getElementById("enable_reset_called_count_owner").checked) {
			document.getElementById("reset_called_count_owner").disabled = false;
		} else {
			document.getElementById("reset_called_count_owner").disabled = true;
		}
	}

	function enableResetCallCountState() {
		if (document.getElementById("enable_reset_called_count_state").checked) {
			document.getElementById("reset_called_count_state").disabled = false;
		} else {
			document.getElementById("reset_called_count_state").disabled = true;
		}
	}

	function enableResetCallCountEntryDate() {
		if (document.getElementById("enable_reset_called_count_entry_date").checked) {
			document.getElementById("reset_called_count_entry_date").disabled = false;
			document.getElementById("reset_called_count_entry_date_end").disabled = false;
			document.getElementById("reset_called_count_entry_date_op").disabled = false;
		} else {
			document.getElementById("reset_called_count_entry_date").disabled = true;
			document.getElementById("reset_called_count_entry_date_end").disabled = true;
			document.getElementById("reset_called_count_entry_date_op").disabled = true;
		}
	}

	function enableResetCallCountModifyDate() {
		if (document.getElementById("enable_reset_called_count_modify_date").checked) {
			document.getElementById("reset_called_count_modify_date").disabled = false;
			document.getElementById("reset_called_count_modify_date_end").disabled = false;
			document.getElementById("reset_called_count_modify_date_op").disabled = false;
		} else {
			document.getElementById("reset_called_count_modify_date").disabled = true;
			document.getElementById("reset_called_count_modify_date_end").disabled = true;
			document.getElementById("reset_called_count_modify_date_op").disabled = true;
		}
	}

	function enableResetCallCountSecurityPhrase() {
		if (document.getElementById("enable_reset_called_count_security_phrase").checked) {
			document.getElementById("reset_called_count_security_phrase").disabled = false;
		} else {
			document.getElementById("reset_called_count_security_phrase").disabled = true;
		}
	}

	function enableResetCallCountLeadId() {
		if (document.getElementById("enable_reset_called_count_lead_id").checked) {
			document.getElementById("reset_called_count_lead_id").disabled = false;
		} else {
			document.getElementById("reset_called_count_lead_id").disabled = true;
		}
	}

	// callback functions
	function enableCallbackEntryDate() {
		if (document.getElementById("enable_callback_entry_date").checked) {
			document.getElementById("callback_entry_start_date").disabled = false;
			document.getElementById("callback_entry_end_date").disabled = false;
		} else {
			document.getElementById("callback_entry_start_date").disabled = true;
			document.getElementById("callback_entry_end_date").disabled = true;
		}
	}

	function enableCallbackCallbackDate() {
		if (document.getElementById("enable_callback_callback_date").checked) {
			document.getElementById("callback_callback_start_date").disabled = false;
			document.getElementById("callback_callback_end_date").disabled = false;
		} else {
			document.getElementById("callback_callback_start_date").disabled = true;
			document.getElementById("callback_callback_end_date").disabled = true;
		}
	}
</script>

<?php
echo "<title>" . _QXZ("ADMINISTRATION") . ": " . _QXZ("Advanced Lead Tools") . "</title>\n";

##### BEGIN Set variables to make header show properly #####
$ADD =  '999998';
$hh =       'admin';
$LOGast_admin_access = '1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width = '870';
$section_width = '850';
$header_font_size = '3';
$subheader_font_size = '2';
$subcamp_font_size = '2';
$header_selected_bold = '<b>';
$header_nonselected_bold = '';
$admin_color =    '#FFFF99';
$admin_font =      'BLACK';
$admin_color =    '#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

echo "<table width=$page_width bgcolor=#$SSframe_background cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='#E6E6E6'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b> &nbsp; <a href=\"lead_tools.php\">" . _QXZ("Basic Lead Tools") . "</a> &nbsp; | &nbsp; " . _QXZ("Advanced Lead Tools") . "</b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2><b> &nbsp; </td>\n";
echo "</tr>\n";



echo "<tr bgcolor='#$SSframe_background'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3> &nbsp; \n";

##### BEGIN move functions #####




###################### BEGIN COPY FUNCTION ########################
if ($copy_submit == _QXZ("copy")) {
	# get the variables
	$enable_copy_status = "";
	$enable_copy_country_code = "";
	$enable_copy_vendor_lead_code = "";
	$enable_copy_source_id = "";
	$enable_copy_owner = "";
	$enable_copy_state = "";
	$enable_copy_entry_date = "";
	$enable_copy_modify_date = "";
	$enable_copy_security_phrase = "";
	$enable_copy_count = "";
	$copy_country_code = "";
	$copy_vendor_lead_code = "";
	$copy_source_id = "";
	$copy_owner = "";
	$copy_state = "";
	$copy_entry_date = "";
	$copy_entry_date_end = "";
	$copy_entry_date_op = "";
	$copy_modify_date = "";
	$copy_modify_date_end = "";
	$copy_modify_date_op = "";
	$copy_security_phrase = "";
	$copy_from_list = "";
	$copy_to_list = "";
	$copy_status = "";
	$copy_count_op = "";
	$copy_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_copy_status"])) {
		$enable_copy_status = $_GET["enable_copy_status"];
	} elseif (isset($_POST["enable_copy_status"])) {
		$enable_copy_status = $_POST["enable_copy_status"];
	}
	if (isset($_GET["enable_copy_country_code"])) {
		$enable_copy_country_code = $_GET["enable_copy_country_code"];
	} elseif (isset($_POST["enable_copy_country_code"])) {
		$enable_copy_country_code = $_POST["enable_copy_country_code"];
	}
	if (isset($_GET["enable_copy_vendor_lead_code"])) {
		$enable_copy_vendor_lead_code = $_GET["enable_copy_vendor_lead_code"];
	} elseif (isset($_POST["enable_copy_vendor_lead_code"])) {
		$enable_copy_vendor_lead_code = $_POST["enable_copy_vendor_lead_code"];
	}
	if (isset($_GET["enable_copy_source_id"])) {
		$enable_copy_source_id = $_GET["enable_copy_source_id"];
	} elseif (isset($_POST["enable_copy_source_id"])) {
		$enable_copy_source_id = $_POST["enable_copy_source_id"];
	}
	if (isset($_GET["enable_copy_owner"])) {
		$enable_copy_owner = $_GET["enable_copy_owner"];
	} elseif (isset($_POST["enable_copy_owner"])) {
		$enable_copy_owner = $_POST["enable_copy_owner"];
	}
	if (isset($_GET["enable_copy_state"])) {
		$enable_copy_state = $_GET["enable_copy_state"];
	} elseif (isset($_POST["enable_copy_state"])) {
		$enable_copy_state = $_POST["enable_copy_state"];
	}
	if (isset($_GET["enable_copy_entry_date"])) {
		$enable_copy_entry_date = $_GET["enable_copy_entry_date"];
	} elseif (isset($_POST["enable_copy_entry_date"])) {
		$enable_copy_entry_date = $_POST["enable_copy_entry_date"];
	}
	if (isset($_GET["enable_copy_modify_date"])) {
		$enable_copy_modify_date = $_GET["enable_copy_modify_date"];
	} elseif (isset($_POST["enable_copy_modify_date"])) {
		$enable_copy_modify_date = $_POST["enable_copy_modify_date"];
	}
	if (isset($_GET["enable_copy_security_phrase"])) {
		$enable_copy_security_phrase = $_GET["enable_copy_security_phrase"];
	} elseif (isset($_POST["enable_copy_security_phrase"])) {
		$enable_copy_security_phrase = $_POST["enable_copy_security_phrase"];
	}
	if (isset($_GET["enable_copy_count"])) {
		$enable_copy_count = $_GET["enable_copy_count"];
	} elseif (isset($_POST["enable_copy_count"])) {
		$enable_copy_count = $_POST["enable_copy_count"];
	}
	if (isset($_GET["copy_country_code"])) {
		$copy_country_code = $_GET["copy_country_code"];
	} elseif (isset($_POST["copy_country_code"])) {
		$copy_country_code = $_POST["copy_country_code"];
	}
	if (isset($_GET["copy_vendor_lead_code"])) {
		$copy_vendor_lead_code = $_GET["copy_vendor_lead_code"];
	} elseif (isset($_POST["copy_vendor_lead_code"])) {
		$copy_vendor_lead_code = $_POST["copy_vendor_lead_code"];
	}
	if (isset($_GET["copy_source_id"])) {
		$copy_source_id = $_GET["copy_source_id"];
	} elseif (isset($_POST["copy_source_id"])) {
		$copy_source_id = $_POST["copy_source_id"];
	}
	if (isset($_GET["copy_owner"])) {
		$copy_owner = $_GET["copy_owner"];
	} elseif (isset($_POST["copy_owner"])) {
		$copy_owner = $_POST["copy_owner"];
	}
	if (isset($_GET["copy_state"])) {
		$copy_state = $_GET["copy_state"];
	} elseif (isset($_POST["copy_state"])) {
		$copy_state = $_POST["copy_state"];
	}
	if (isset($_GET["copy_entry_date"])) {
		$copy_entry_date = $_GET["copy_entry_date"];
	} elseif (isset($_POST["copy_entry_date"])) {
		$copy_entry_date = $_POST["copy_entry_date"];
	}
	if (isset($_GET["copy_entry_date_end"])) {
		$copy_entry_date_end = $_GET["copy_entry_date_end"];
	} elseif (isset($_POST["copy_entry_date_end"])) {
		$copy_entry_date_end = $_POST["copy_entry_date_end"];
	}
	if (isset($_GET["copy_entry_date_op"])) {
		$copy_entry_date_op = $_GET["copy_entry_date_op"];
	} elseif (isset($_POST["copy_entry_date_op"])) {
		$copy_entry_date_op = $_POST["copy_entry_date_op"];
	}
	if (isset($_GET["copy_modify_date"])) {
		$copy_modify_date = $_GET["copy_modify_date"];
	} elseif (isset($_POST["copy_modify_date"])) {
		$copy_modify_date = $_POST["copy_modify_date"];
	}
	if (isset($_GET["copy_modify_date_end"])) {
		$copy_modify_date_end = $_GET["copy_modify_date_end"];
	} elseif (isset($_POST["copy_modify_date_end"])) {
		$copy_modify_date_end = $_POST["copy_modify_date_end"];
	}
	if (isset($_GET["copy_modify_date_op"])) {
		$copy_modify_date_op = $_GET["copy_modify_date_op"];
	} elseif (isset($_POST["copy_modify_date_op"])) {
		$copy_modify_date_op = $_POST["copy_modify_date_op"];
	}
	if (isset($_GET["copy_security_phrase"])) {
		$copy_security_phrase = $_GET["copy_security_phrase"];
	} elseif (isset($_POST["copy_security_phrase"])) {
		$copy_security_phrase = $_POST["copy_security_phrase"];
	}
	if (isset($_GET["copy_from_list"])) {
		$copy_from_list = $_GET["copy_from_list"];
	} elseif (isset($_POST["copy_from_list"])) {
		$copy_from_list = $_POST["copy_from_list"];
	}
	if (isset($_GET["copy_to_list"])) {
		$copy_to_list = $_GET["copy_to_list"];
	} elseif (isset($_POST["copy_to_list"])) {
		$copy_to_list = $_POST["copy_to_list"];
	}
	if (isset($_GET["copy_status"])) {
		$copy_status = $_GET["copy_status"];
	} elseif (isset($_POST["copy_status"])) {
		$copy_status = $_POST["copy_status"];
	}
	if (isset($_GET["copy_count_op"])) {
		$copy_count_op = $_GET["copy_count_op"];
	} elseif (isset($_POST["copy_count_op"])) {
		$copy_count_op = $_POST["copy_count_op"];
	}
	if (isset($_GET["copy_count_num"])) {
		$copy_count_num = $_GET["copy_count_num"];
	} elseif (isset($_POST["copy_count_num"])) {
		$copy_count_num = $_POST["copy_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_copy_status") . " = $enable_copy_status | " . _QXZ("enable_copy_country_code") . " = $enable_copy_country_code | " . _QXZ("enable_copy_vendor_lead_code") . " = $enable_copy_vendor_lead_code | " . _QXZ("enable_copy_source_id") . " = $enable_copy_source_id | " . _QXZ("enable_copy_owner") . " = $enable_copy_owner | " . _QXZ("enable_copy_state") . " = $enable_copy_state | " . _QXZ("enable_copy_entry_date") . " = $enable_copy_entry_date | " . _QXZ("enable_copy_modify_date") . " = $enable_copy_modify_date | " . _QXZ("enable_copy_security_phrase") . " = $enable_copy_security_phrase | " . _QXZ("enable_copy_count") . " = $enable_copy_count | " . _QXZ("copy_country_code") . " = $copy_country_code | " . _QXZ("copy_vendor_lead_code") . " = $copy_vendor_lead_code | " . _QXZ("copy_source_id") . " = $copy_source_id | " . _QXZ("copy_owner") . " = $copy_owner | " . _QXZ("copy_state") . " = $copy_state | " . _QXZ("copy_entry_date") . " = $copy_entry_date | " . _QXZ("copy_entry_date_end") . " = $copy_entry_date_end | " . _QXZ("copy_entry_date_op") . " = $copy_entry_date_op | " . _QXZ("copy_modify_date") . " = $copy_modify_date | " . _QXZ("copy_modify_date_end") . " = $copy_modify_date_end | " . _QXZ("copy_modify_date_op") . " = $copy_modify_date_op | " . _QXZ("copy_security_phrase") . " = $copy_security_phrase | " . _QXZ("copy_from_list") . " = $copy_from_list | " . _QXZ("copy_to_list") . " = $copy_to_list | " . _QXZ("copy_status") . " = $copy_status | " . _QXZ("copy_count_op") . " = $copy_count_op | " . _QXZ("copy_count_num") . " = $copy_count_num</p>";
	}

	# filter out anything bad
	$enable_copy_status = preg_replace('/[^a-zA-Z]/', '', $enable_copy_status);
	$enable_copy_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_copy_country_code);
	$enable_copy_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_copy_vendor_lead_code);
	$enable_copy_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_copy_source_id);
	$enable_copy_owner = preg_replace('/[^a-zA-Z]/', '', $enable_copy_owner);
	$enable_copy_state = preg_replace('/[^a-zA-Z]/', '', $enable_copy_state);
	$enable_copy_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_copy_entry_date);
	$enable_copy_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_copy_modify_date);
	$enable_copy_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_copy_security_phrase);
	$enable_copy_count = preg_replace('/[^a-zA-Z]/', '', $enable_copy_count);
	$copy_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $copy_country_code);
	$copy_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_vendor_lead_code);
	$copy_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_source_id);
	$copy_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_owner);
	$copy_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $copy_state);
	$copy_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_entry_date);
	$copy_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_entry_date_end);
	$copy_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $copy_entry_date_op);
	$copy_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_modify_date);
	$copy_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_modify_date_end);
	$copy_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $copy_modify_date_op);
	$copy_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_security_phrase);
	$copy_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $copy_status);
	$copy_from_list = preg_replace('/[^0-9\|]/', '', $copy_from_list);
	$copy_to_list = preg_replace('/[^0-9]/', '', $copy_to_list);
	$copy_count_num = preg_replace('/[^0-9]/', '', $copy_count_num);
	$copy_count_op = preg_replace('/[^<>=]/', '', $copy_count_op);

	# build the count operation phrase
	$copy_count_op_phrase = "";
	if ($copy_count_op == "<") {
		$copy_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_count_op == "<=") {
		$copy_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_count_op == ">") {
		$copy_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_count_op == ">=") {
		$copy_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the copy_entry_date operation phrase
	$copy_entry_date_op_phrase = "";
	$copy_entry_operator_a = '>=';
	$copy_entry_operator_b = '<=';
	if ($copy_entry_date_op == "<") {
		$copy_entry_operator_a = '>=';
		$copy_entry_operator_b = '<';
		$copy_entry_date_end = $copy_entry_date;
		$copy_entry_date = '0000-00-00 00:00:00';
		$copy_entry_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_entry_date_op == "<=") {
		$copy_entry_date_end = $copy_entry_date;
		$copy_entry_date = '0000-00-00 00:00:00';
		$copy_entry_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_entry_date_op == ">") {
		$copy_entry_operator_a = '>';
		$copy_entry_operator_b = '<';
		$copy_entry_date_end = '2100-00-00 00:00:00';
		$copy_entry_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_entry_date_op == ">=") {
		$copy_entry_date_end = '2100-00-00 00:00:00';
		$copy_entry_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($copy_entry_date_op == "range") {
		$copy_entry_date_op_phrase = _QXZ("range") . " ";
	} elseif ($copy_entry_date_op == "=") {
		$copy_entry_date_end = $copy_entry_date;
		$copy_entry_date_op_phrase = _QXZ("equal to") . " ";
	}

	# build the copy_modify_date operation phrase
	$copy_modify_date_op_phrase = "";
	$copy_modify_operator_a = '>=';
	$copy_modify_operator_b = '<=';
	if ($copy_modify_date_op == "<") {
		$copy_modify_operator_a = '>=';
		$copy_modify_operator_b = '<';
		$copy_entry_date_end = $copy_entry_date;
		$copy_entry_date = '0000-00-00 00:00:00';
		$copy_modify_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_modify_date_op == "<=") {
		$copy_entry_date_end = $copy_entry_date;
		$copy_entry_date = '0000-00-00 00:00:00';
		$copy_modify_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_modify_date_op == ">") {
		$copy_modify_operator_a = '>';
		$copy_modify_operator_b = '<';
		$copy_entry_date_end = '2100-00-00 00:00:00';
		$copy_modify_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_modify_date_op == ">=") {
		$copy_entry_date_end = '2100-00-00 00:00:00';
		$copy_modify_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($copy_modify_date_op == "range") {
		$copy_modify_date_op_phrase = _QXZ("range") . " ";
	} elseif ($copy_modify_date_op == "=") {
		$copy_entry_date_end = $copy_entry_date;
		$copy_modify_date_op_phrase = _QXZ("equal to") . " ";
	}

	if (strlen($copy_entry_date) == 10) {
		$copy_entry_date .= " 00:00:00";
	}
	if (strlen($copy_entry_date_end) == 10) {
		$copy_entry_date_end .= " 23:59:59";
	}
	if (strlen($copy_modify_date) == 10) {
		$copy_modify_date .= " 00:00:00";
	}
	if (strlen($copy_modify_date_end) == 10) {
		$copy_modify_date_end .= " 23:59:59";
	}


	if ($DB) {
		echo "<p>" . _QXZ("enable_copy_status") . " = $enable_copy_status | " . _QXZ("enable_copy_country_code") . " = $enable_copy_country_code | " . _QXZ("enable_copy_vendor_lead_code") . " = $enable_copy_vendor_lead_code | " . _QXZ("enable_copy_source_id") . " = $enable_copy_source_id | " . _QXZ("enable_copy_owner") . " = $enable_copy_owner | " . _QXZ("enable_copy_state") . " = $enable_copy_state | " . _QXZ("enable_copy_entry_date") . " = $enable_copy_entry_date | " . _QXZ("enable_copy_modify_date") . " = $enable_copy_modify_date | " . _QXZ("enable_copy_security_phrase") . " = $enable_copy_security_phrase | " . _QXZ("enable_copy_count") . " = $enable_copy_count | " . _QXZ("copy_country_code") . " = $copy_country_code | " . _QXZ("copy_vendor_lead_code") . " = $copy_vendor_lead_code | " . _QXZ("copy_source_id") . " = $copy_source_id | " . _QXZ("copy_owner") . " = $copy_owner | " . _QXZ("copy_state") . " = $copy_state | " . _QXZ("copy_entry_date") . " = $copy_entry_date | " . _QXZ("copy_entry_date_end") . " = $copy_entry_date_end | " . _QXZ("copy_entry_date_op") . " = $copy_entry_date_op | " . _QXZ("copy_modify_date") . " = $copy_modify_date | " . _QXZ("copy_modify_date_end") . " = $copy_modify_date_end | " . _QXZ("copy_modify_date_op") . " = $copy_modify_date_op | " . _QXZ("copy_security_phrase") . " = $copy_security_phrase | " . _QXZ("copy_from_list") . " = $copy_from_list | " . _QXZ("copy_to_list") . " = $copy_to_list | " . _QXZ("copy_status") . " = $copy_status | " . _QXZ("copy_count_op") . " = $copy_count_op | " . _QXZ("copy_count_num") . " = $copy_count_num</p>";
	}

	# make sure the required fields are set
	if ($copy_from_list == '') {
		missing_required_field('From List');
	}
	if ($copy_to_list == '') {
		missing_required_field('To List');
	}


	# build the sql query's where phrase and the copy phrase
	$sql_where = "";
	$copy_parm = "";
	if (($enable_copy_status == "enabled") && ($copy_status != '')) {
		if ($copy_status == '---BLANK---') {
			$copy_status = '';
		}
		$sql_where = $sql_where . " and status like '$copy_status' ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $copy_status<br />";
		if ($copy_status == '') {
			$copy_status = '---BLANK---';
		}
	} elseif ($enable_copy_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_copy_country_code == "enabled") && ($copy_country_code != '')) {
		if ($copy_country_code == '---BLANK---') {
			$copy_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$copy_country_code\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $copy_country_code<br />";
		if ($copy_country_code == '') {
			$copy_country_code = '---BLANK---';
		}
	} elseif ($enable_copy_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_copy_vendor_lead_code == "enabled") && ($copy_vendor_lead_code != '')) {
		if ($copy_vendor_lead_code == '---BLANK---') {
			$copy_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$copy_vendor_lead_code\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $copy_vendor_lead_code<br />";
		if ($copy_vendor_lead_code == '') {
			$copy_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_copy_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_copy_source_id == "enabled") && ($copy_source_id != '')) {
		if ($copy_source_id == '---BLANK---') {
			$copy_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$copy_source_id\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $copy_source_id<br />";
		if ($copy_source_id == '') {
			$copy_source_id = '---BLANK---';
		}
	} elseif ($enable_copy_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_copy_owner == "enabled") && ($copy_owner != '')) {
		if ($copy_owner == '---BLANK---') {
			$copy_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$copy_owner\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $copy_owner<br />";
		if ($copy_owner == '') {
			$copy_owner = '---BLANK---';
		}
	} elseif ($enable_copy_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_copy_state == "enabled") && ($copy_state != '')) {
		if ($copy_state == '---BLANK---') {
			$copy_state = '';
		}
		$sql_where = $sql_where . " and state like \"$copy_state\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $copy_state<br />";
		if ($copy_state == '') {
			$copy_state = '---BLANK---';
		}
	} elseif ($enable_copy_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_copy_security_phrase == "enabled") && ($copy_security_phrase != '')) {
		if ($copy_security_phrase == '---BLANK---') {
			$copy_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$copy_security_phrase\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $copy_security_phrase<br />";
		if ($copy_security_phrase == '') {
			$copy_security_phrase = '---BLANK---';
		}
	} elseif ($enable_copy_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_copy_entry_date == "enabled") && ($copy_entry_date != '')) {
		if ($copy_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $copy_entry_operator_a '$copy_entry_date' and entry_date $copy_entry_operator_b '$copy_entry_date_end' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was on") . " $copy_entry_date - $copy_entry_date_end<br />";
		}
	} elseif ($enable_copy_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_copy_modify_date == "enabled") && ($copy_modify_date != '')) {
		if ($copy_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $copy_modify_operator_a '$copy_modify_date' and modify_date $copy_modify_operator_b '$copy_modify_date_end' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was on") . " $copy_modify_date - $copy_modify_date_end<br />";
		}
	} elseif ($enable_copy_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_copy_count == "enabled") && ($copy_count_op != '') && ($copy_count_num != '')) {
		if ($copy_count_op == '---BLANK---') {
			$copy_count_op = '';
		}
		if ($copy_count_num == '---BLANK---') {
			$copy_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $copy_count_op $copy_count_num";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $copy_count_op_phrase $copy_count_num<br />";
		if ($copy_count_op == '') {
			$copy_count_op = '---BLANK---';
		}
		if ($copy_count_num == '') {
			$copy_count_num = '---BLANK---';
		}
	} elseif ($enable_copy_count == "enabled") {
		blank_field('copy Count', true);
	}

	# get the number of leads this action will copy
	$copy_lead_count = 0;
	$copy_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id IN('" . implode("','", $copy_from_list) . "') $sql_where";
	if ($DB) {
		echo "|$copy_lead_count_stmt|\n";
	}
	$copy_lead_count_rslt = mysql_to_mysqli($copy_lead_count_stmt, $link);
	$copy_lead_count_row = mysqli_fetch_row($copy_lead_count_rslt);
	$copy_lead_count = $copy_lead_count_row[0];

	# get the number of leads in the list this action will copy to
	$to_list_lead_count = 0;
	$to_list_lead_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$copy_to_list'";
	if ($DB) {
		echo "|$to_list_lead_stmt|\n";
	}
	$to_list_lead_rslt = mysql_to_mysqli($to_list_lead_stmt, $link);
	$to_list_lead_row = mysqli_fetch_row($to_list_lead_rslt);
	$to_list_lead_count = $to_list_lead_row[0];

	# check to see if we will exceed list_lead_limit in the copy to list
	$copyListAndFromListIsSame = false;
	if (is_array($copy_from_list)) {
		if (in_array($copy_to_list, $copy_from_list)) {
			$copyListAndFromListIsSame = true;
		}
	}

	if ($to_list_lead_count + $copy_lead_count > $list_lead_limit) {
		echo "<html>\n";
		echo "<head>\n";
		echo "<!-- VERSION: $version     BUILD: $build -->\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<p>" . _QXZ("Sorry. This operation will cause list") . " $copy_to_list " . _QXZ("to exceed") . " $list_lead_limit " . _QXZ("leads which is not allowed") . ".</p>\n";
		echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
		echo "</body>\n</html>\n";
	} else if ($copyListAndFromListIsSame) {
		// echo paragraph saying that the lists are the same
		echo "<p> You are copying the leads from the same list!</p>";
		echo "From List: <b>" . implode(", ", $copy_from_list) . "</b><br>";
		echo "To List:<b> $copy_to_list</b><br>";
		echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
		echo "</body>\n</html>\n";
	} else {
		echo "<p>" . _QXZ("You are about to copy") . " <B>$copy_lead_count</B> " . _QXZ("leads from list") . " " . implode(",", $copy_from_list) . " " . _QXZ("to") . " $copy_to_list " . _QXZ("with the following parameters") . ":<br /><br />$copy_parm <br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
		echo "<center><form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=enable_copy_status value='$enable_copy_status'>\n";
		echo "<input type=hidden name=enable_copy_country_code value='$enable_copy_country_code'>\n";
		echo "<input type=hidden name=enable_copy_vendor_lead_code value='$enable_copy_vendor_lead_code'>\n";
		echo "<input type=hidden name=enable_copy_source_id value='$enable_copy_source_id'>\n";
		echo "<input type=hidden name=enable_copy_owner value='$enable_copy_owner'>\n";
		echo "<input type=hidden name=enable_copy_state value='$enable_copy_state'>\n";
		echo "<input type=hidden name=enable_copy_entry_date value='$enable_copy_entry_date'>\n";
		echo "<input type=hidden name=enable_copy_modify_date value='$enable_copy_modify_date'>\n";
		echo "<input type=hidden name=enable_copy_security_phrase value='$enable_copy_security_phrase'>\n";
		echo "<input type=hidden name=enable_copy_count value='$enable_copy_count'>\n";
		echo "<input type=hidden name=copy_country_code value=\"$copy_country_code\">\n";
		echo "<input type=hidden name=copy_vendor_lead_code value=\"$copy_vendor_lead_code\">\n";
		echo "<input type=hidden name=copy_source_id value=\"$copy_source_id\">\n";
		echo "<input type=hidden name=copy_owner value=\"$copy_owner\">\n";
		echo "<input type=hidden name=copy_state value=\"$copy_state\">\n";
		echo "<input type=hidden name=copy_entry_date value=\"$copy_entry_date\">\n";
		echo "<input type=hidden name=copy_entry_date_end value=\"$copy_entry_date_end\">\n";
		echo "<input type=hidden name=copy_entry_date_op value=\"$copy_entry_date_op\">\n";
		echo "<input type=hidden name=copy_modify_date value=\"$copy_modify_date\">\n";
		echo "<input type=hidden name=copy_modify_date_end value=\"$copy_modify_date_end\">\n";
		echo "<input type=hidden name=copy_modify_date_op value=\"$copy_modify_date_op\">\n";
		echo "<input type=hidden name=copy_security_phrase value=\"$copy_security_phrase\">\n";
		echo "<input type=hidden name=copy_from_list value='" . implode("|", $copy_from_list) . "'>\n";
		echo "<input type=hidden name=copy_to_list value=\"$copy_to_list\">\n";
		echo "<input type=hidden name=copy_status value=\"$copy_status\">\n";
		echo "<input type=hidden name=copy_count_op value=\"$copy_count_op\">\n";
		echo "<input type=hidden name=copy_count_num value=\"$copy_count_num\">\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_copy value='" . _QXZ("confirm") . "'>\n";
		echo "</form></center>\n";
		echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
		echo "</body>\n</html>\n";
	}
}
###################### END COPY FUNCTION ########################

###################### copy function process start ########################
if ($confirm_copy == _QXZ("confirm")) {
	# get the variables
	$enable_copy_status = "";
	$enable_copy_country_code = "";
	$enable_copy_vendor_lead_code = "";
	$enable_copy_source_id = "";
	$enable_copy_owner = "";
	$enable_copy_state = "";
	$enable_copy_entry_date = "";
	$enable_copy_modify_date = "";
	$enable_copy_security_phrase = "";
	$enable_copy_count = "";
	$copy_country_code = "";
	$copy_vendor_lead_code = "";
	$copy_source_id = "";
	$copy_owner = "";
	$copy_state = "";
	$copy_entry_date = "";
	$copy_entry_date_end = "";
	$copy_entry_date_op = "";
	$copy_modify_date = "";
	$copy_modify_date_end = "";
	$copy_modify_date_op = "";
	$copy_security_phrase = "";
	$copy_from_list = "";
	$copy_to_list = "";
	$copy_status = "";
	$copy_count_op = "";
	$copy_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_copy_status"])) {
		$enable_copy_status = $_GET["enable_copy_status"];
	} elseif (isset($_POST["enable_copy_status"])) {
		$enable_copy_status = $_POST["enable_copy_status"];
	}
	if (isset($_GET["enable_copy_country_code"])) {
		$enable_copy_country_code = $_GET["enable_copy_country_code"];
	} elseif (isset($_POST["enable_copy_country_code"])) {
		$enable_copy_country_code = $_POST["enable_copy_country_code"];
	}
	if (isset($_GET["enable_copy_vendor_lead_code"])) {
		$enable_copy_vendor_lead_code = $_GET["enable_copy_vendor_lead_code"];
	} elseif (isset($_POST["enable_copy_vendor_lead_code"])) {
		$enable_copy_vendor_lead_code = $_POST["enable_copy_vendor_lead_code"];
	}
	if (isset($_GET["enable_copy_source_id"])) {
		$enable_copy_source_id = $_GET["enable_copy_source_id"];
	} elseif (isset($_POST["enable_copy_source_id"])) {
		$enable_copy_source_id = $_POST["enable_copy_source_id"];
	}
	if (isset($_GET["enable_copy_owner"])) {
		$enable_copy_owner = $_GET["enable_copy_owner"];
	} elseif (isset($_POST["enable_copy_owner"])) {
		$enable_copy_owner = $_POST["enable_copy_owner"];
	}
	if (isset($_GET["enable_copy_state"])) {
		$enable_copy_state = $_GET["enable_copy_state"];
	} elseif (isset($_POST["enable_copy_state"])) {
		$enable_copy_state = $_POST["enable_copy_state"];
	}
	if (isset($_GET["enable_copy_entry_date"])) {
		$enable_copy_entry_date = $_GET["enable_copy_entry_date"];
	} elseif (isset($_POST["enable_copy_entry_date"])) {
		$enable_copy_entry_date = $_POST["enable_copy_entry_date"];
	}
	if (isset($_GET["enable_copy_modify_date"])) {
		$enable_copy_modify_date = $_GET["enable_copy_modify_date"];
	} elseif (isset($_POST["enable_copy_modify_date"])) {
		$enable_copy_modify_date = $_POST["enable_copy_modify_date"];
	}
	if (isset($_GET["enable_copy_security_phrase"])) {
		$enable_copy_security_phrase = $_GET["enable_copy_security_phrase"];
	} elseif (isset($_POST["enable_copy_security_phrase"])) {
		$enable_copy_security_phrase = $_POST["enable_copy_security_phrase"];
	}
	if (isset($_GET["enable_copy_count"])) {
		$enable_copy_count = $_GET["enable_copy_count"];
	} elseif (isset($_POST["enable_copy_count"])) {
		$enable_copy_count = $_POST["enable_copy_count"];
	}
	if (isset($_GET["copy_country_code"])) {
		$copy_country_code = $_GET["copy_country_code"];
	} elseif (isset($_POST["copy_country_code"])) {
		$copy_country_code = $_POST["copy_country_code"];
	}
	if (isset($_GET["copy_vendor_lead_code"])) {
		$copy_vendor_lead_code = $_GET["copy_vendor_lead_code"];
	} elseif (isset($_POST["copy_vendor_lead_code"])) {
		$copy_vendor_lead_code = $_POST["copy_vendor_lead_code"];
	}
	if (isset($_GET["copy_source_id"])) {
		$copy_source_id = $_GET["copy_source_id"];
	} elseif (isset($_POST["copy_source_id"])) {
		$copy_source_id = $_POST["copy_source_id"];
	}
	if (isset($_GET["copy_owner"])) {
		$copy_owner = $_GET["copy_owner"];
	} elseif (isset($_POST["copy_owner"])) {
		$copy_owner = $_POST["copy_owner"];
	}
	if (isset($_GET["copy_state"])) {
		$copy_state = $_GET["copy_state"];
	} elseif (isset($_POST["copy_state"])) {
		$copy_state = $_POST["copy_state"];
	}
	if (isset($_GET["copy_entry_date"])) {
		$copy_entry_date = $_GET["copy_entry_date"];
	} elseif (isset($_POST["copy_entry_date"])) {
		$copy_entry_date = $_POST["copy_entry_date"];
	}
	if (isset($_GET["copy_entry_date_end"])) {
		$copy_entry_date_end = $_GET["copy_entry_date_end"];
	} elseif (isset($_POST["copy_entry_date_end"])) {
		$copy_entry_date_end = $_POST["copy_entry_date_end"];
	}
	if (isset($_GET["copy_entry_date_op"])) {
		$copy_entry_date_op = $_GET["copy_entry_date_op"];
	} elseif (isset($_POST["copy_entry_date_op"])) {
		$copy_entry_date_op = $_POST["copy_entry_date_op"];
	}
	if (isset($_GET["copy_modify_date"])) {
		$copy_modify_date = $_GET["copy_modify_date"];
	} elseif (isset($_POST["copy_modify_date"])) {
		$copy_modify_date = $_POST["copy_modify_date"];
	}
	if (isset($_GET["copy_modify_date_end"])) {
		$copy_modify_date_end = $_GET["copy_modify_date_end"];
	} elseif (isset($_POST["copy_modify_date_end"])) {
		$copy_modify_date_end = $_POST["copy_modify_date_end"];
	}
	if (isset($_GET["copy_modify_date_op"])) {
		$copy_modify_date_op = $_GET["copy_modify_date_op"];
	} elseif (isset($_POST["copy_modify_date_op"])) {
		$copy_modify_date_op = $_POST["copy_modify_date_op"];
	}
	if (isset($_GET["copy_security_phrase"])) {
		$copy_security_phrase = $_GET["copy_security_phrase"];
	} elseif (isset($_POST["copy_security_phrase"])) {
		$copy_security_phrase = $_POST["copy_security_phrase"];
	}
	if (isset($_GET["copy_from_list"])) {
		$copy_from_list = $_GET["copy_from_list"];
	} elseif (isset($_POST["copy_from_list"])) {
		$copy_from_list = $_POST["copy_from_list"];
	}
	if (isset($_GET["copy_to_list"])) {
		$copy_to_list = $_GET["copy_to_list"];
	} elseif (isset($_POST["copy_to_list"])) {
		$copy_to_list = $_POST["copy_to_list"];
	}
	if (isset($_GET["copy_status"])) {
		$copy_status = $_GET["copy_status"];
	} elseif (isset($_POST["copy_status"])) {
		$copy_status = $_POST["copy_status"];
	}
	if (isset($_GET["copy_count_op"])) {
		$copy_count_op = $_GET["copy_count_op"];
	} elseif (isset($_POST["copy_count_op"])) {
		$copy_count_op = $_POST["copy_count_op"];
	}
	if (isset($_GET["copy_count_num"])) {
		$copy_count_num = $_GET["copy_count_num"];
	} elseif (isset($_POST["copy_count_num"])) {
		$copy_count_num = $_POST["copy_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_copy_status") . " = $enable_copy_status | " . _QXZ("enable_copy_country_code") . " = $enable_copy_country_code | " . _QXZ("enable_copy_vendor_lead_code") . " = $enable_copy_vendor_lead_code | " . _QXZ("enable_copy_source_id") . " = $enable_copy_source_id | " . _QXZ("enable_copy_owner") . " = $enable_copy_owner | " . _QXZ("enable_copy_state") . " = $enable_copy_state | " . _QXZ("enable_copy_entry_date") . " = $enable_copy_entry_date | " . _QXZ("enable_copy_modify_date") . " = $enable_copy_modify_date | " . _QXZ("enable_copy_security_phrase") . " = $enable_copy_security_phrase | " . _QXZ("enable_copy_count") . " = $enable_copy_count | " . _QXZ("copy_country_code") . " = $copy_country_code | " . _QXZ("copy_vendor_lead_code") . " = $copy_vendor_lead_code | " . _QXZ("copy_source_id") . " = $copy_source_id | " . _QXZ("copy_owner") . " = $copy_owner | " . _QXZ("copy_state") . " = $copy_state | " . _QXZ("copy_entry_date") . " = $copy_entry_date | " . _QXZ("copy_entry_date_end") . " = $copy_entry_date_end | " . _QXZ("copy_entry_date_op") . " = $copy_entry_date_op | " . _QXZ("copy_modify_date") . " = $copy_modify_date | " . _QXZ("copy_modify_date_end") . " = $copy_modify_date_end | " . _QXZ("copy_modify_date_op") . " = $copy_modify_date_op | " . _QXZ("copy_security_phrase") . " = $copy_security_phrase | " . _QXZ("copy_from_list") . " = $copy_from_list | " . _QXZ("copy_to_list") . " = $copy_to_list | " . _QXZ("copy_status") . " = $copy_status | " . _QXZ("copy_count_op") . " = $copy_count_op | " . _QXZ("copy_count_num") . " = $copy_count_num</p>";
	}

	# filter out anything bad
	$enable_copy_status = preg_replace('/[^a-zA-Z]/', '', $enable_copy_status);
	$enable_copy_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_copy_country_code);
	$enable_copy_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_copy_vendor_lead_code);
	$enable_copy_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_copy_source_id);
	$enable_copy_owner = preg_replace('/[^a-zA-Z]/', '', $enable_copy_owner);
	$enable_copy_state = preg_replace('/[^a-zA-Z]/', '', $enable_copy_state);
	$enable_copy_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_copy_entry_date);
	$enable_copy_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_copy_modify_date);
	$enable_copy_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_copy_security_phrase);
	$enable_copy_count = preg_replace('/[^a-zA-Z]/', '', $enable_copy_count);
	$copy_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $copy_country_code);
	$copy_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_vendor_lead_code);
	$copy_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_source_id);
	$copy_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_owner);
	$copy_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $copy_state);
	$copy_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_entry_date);
	$copy_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_entry_date_end);
	$copy_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $copy_entry_date_op);
	$copy_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_modify_date);
	$copy_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $copy_modify_date_end);
	$copy_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $copy_modify_date_op);
	$copy_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $copy_security_phrase);
	$copy_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $copy_status);
	$copy_from_list = preg_replace('/[^0-9\|]/', '', $copy_from_list);
	$copy_to_list = preg_replace('/[^0-9]/', '', $copy_to_list);
	$copy_count_num = preg_replace('/[^0-9]/', '', $copy_count_num);
	$copy_count_op = preg_replace('/[^<>=]/', '', $copy_count_op);

	# count operator
	$copy_count_op_phrase = "";
	if ($copy_count_op == "<") {
		$copy_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_count_op == "<=") {
		$copy_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_count_op == ">") {
		$copy_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_count_op == ">=") {
		$copy_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the copy_entry_date operation phrase
	$copy_entry_date_op_phrase = "";
	$copy_entry_operator_a = '>=';
	$copy_entry_operator_b = '<=';
	if ($copy_entry_date_op == "<") {
		$copy_entry_operator_a = '>=';
		$copy_entry_operator_b = '<';
		$copy_entry_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_entry_date_op == "<=") {
		$copy_entry_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_entry_date_op == ">") {
		$copy_entry_operator_a = '>';
		$copy_entry_operator_b = '<';
		$copy_entry_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_entry_date_op == ">=") {
		$copy_entry_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($copy_entry_date_op == "range") {
		$copy_entry_date_op_phrase = _QXZ("range") . " ";
	} elseif ($copy_entry_date_op == "=") {
		$copy_entry_date_op_phrase = _QXZ("equal to") . " ";
	}

	# build the copy_modify_date operation phrase
	$copy_modify_date_op_phrase = "";
	$copy_modify_operator_a = '>=';
	$copy_modify_operator_b = '<=';
	if ($copy_modify_date_op == "<") {
		$copy_modify_operator_a = '>=';
		$copy_modify_operator_b = '<';
		$copy_modify_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($copy_modify_date_op == "<=") {
		$copy_modify_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($copy_modify_date_op == ">") {
		$copy_modify_operator_a = '>';
		$copy_modify_operator_b = '<';
		$copy_modify_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($copy_modify_date_op == ">=") {
		$copy_modify_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($copy_modify_date_op == "range") {
		$copy_modify_date_op_phrase = _QXZ("range") . " ";
	} elseif ($copy_modify_date_op == "=") {
		$copy_modify_date_op_phrase = _QXZ("equal to") . " ";
	}

	if (strlen($copy_entry_date) == 10) {
		$copy_entry_date .= " 00:00:00";
	}
	if (strlen($copy_entry_date_end) == 10) {
		$copy_entry_date_end .= " 23:59:59";
	}
	if (strlen($copy_modify_date) == 10) {
		$copy_modify_date .= " 00:00:00";
	}
	if (strlen($copy_modify_date_end) == 10) {
		$copy_modify_date_end .= " 23:59:59";
	}


	if ($DB) {
		echo "<p>" . _QXZ("enable_copy_status") . " = $enable_copy_status | " . _QXZ("enable_copy_country_code") . " = $enable_copy_country_code | " . _QXZ("enable_copy_vendor_lead_code") . " = $enable_copy_vendor_lead_code | " . _QXZ("enable_copy_source_id") . " = $enable_copy_source_id | " . _QXZ("enable_copy_owner") . " = $enable_copy_owner | " . _QXZ("enable_copy_state") . " = $enable_copy_state | " . _QXZ("enable_copy_entry_date") . " = $enable_copy_entry_date | " . _QXZ("enable_copy_modify_date") . " = $enable_copy_modify_date | " . _QXZ("enable_copy_security_phrase") . " = $enable_copy_security_phrase | " . _QXZ("enable_copy_count") . " = $enable_copy_count | " . _QXZ("copy_country_code") . " = $copy_country_code | " . _QXZ("copy_vendor_lead_code") . " = $copy_vendor_lead_code | " . _QXZ("copy_source_id") . " = $copy_source_id | " . _QXZ("copy_owner") . " = $copy_owner | " . _QXZ("copy_state") . " = $copy_state | " . _QXZ("copy_entry_date") . " = $copy_entry_date | " . _QXZ("copy_entry_date_end") . " = $copy_entry_date_end | " . _QXZ("copy_entry_date_op") . " = $copy_entry_date_op | " . _QXZ("copy_modify_date") . " = $copy_modify_date | " . _QXZ("copy_modify_date_end") . " = $copy_modify_date_end | " . _QXZ("copy_modify_date_op") . " = $copy_modify_date_op | " . _QXZ("copy_security_phrase") . " = $copy_security_phrase | " . _QXZ("copy_from_list") . " = $copy_from_list | " . _QXZ("copy_to_list") . " = $copy_to_list | " . _QXZ("copy_status") . " = $copy_status | " . _QXZ("copy_count_op") . " = $copy_count_op | " . _QXZ("copy_count_num") . " = $copy_count_num</p>";
	}


	# make sure the required fields are set
	if ($copy_from_list == '') {
		missing_required_field('From List');
	}
	if ($copy_to_list == '') {
		missing_required_field('To List');
	}

	# build the sql query's where phrase and the copy phrase
	$sql_where = "";
	$copy_parm = "";
	if (($enable_copy_status == "enabled") && ($copy_status != '')) {
		if ($copy_status == '---BLANK---') {
			$copy_status = '';
		}
		$sql_where = $sql_where . " and status like '$copy_status' ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $copy_status<br />";
		if ($copy_status == '') {
			$copy_status = '---BLANK---';
		}
	} elseif ($enable_copy_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_copy_country_code == "enabled") && ($copy_country_code != '')) {
		if ($copy_country_code == '---BLANK---') {
			$copy_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$copy_country_code\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $copy_country_code<br />";
		if ($copy_country_code == '') {
			$copy_country_code = '---BLANK---';
		}
	} elseif ($enable_copy_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_copy_vendor_lead_code == "enabled") && ($copy_vendor_lead_code != '')) {
		if ($copy_vendor_lead_code == '---BLANK---') {
			$copy_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$copy_vendor_lead_code\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $copy_vendor_lead_code<br />";
		if ($copy_vendor_lead_code == '') {
			$copy_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_copy_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_copy_source_id == "enabled") && ($copy_source_id != '')) {
		if ($copy_source_id == '---BLANK---') {
			$copy_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$copy_source_id\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $copy_source_id<br />";
		if ($copy_source_id == '') {
			$copy_source_id = '---BLANK---';
		}
	} elseif ($enable_copy_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_copy_owner == "enabled") && ($copy_owner != '')) {
		if ($copy_owner == '---BLANK---') {
			$copy_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$copy_owner\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $copy_owner<br />";
		if ($copy_owner == '') {
			$copy_owner = '---BLANK---';
		}
	} elseif ($enable_copy_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_copy_state == "enabled") && ($copy_state != '')) {
		if ($copy_state == '---BLANK---') {
			$copy_state = '';
		}
		$sql_where = $sql_where . " and state like \"$copy_state\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $copy_state<br />";
		if ($copy_state == '') {
			$copy_state = '---BLANK---';
		}
	} elseif ($enable_copy_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_copy_security_phrase == "enabled") && ($copy_security_phrase != '')) {
		if ($copy_security_phrase == '---BLANK---') {
			$copy_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$copy_security_phrase\" ";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $copy_security_phrase<br />";
		if ($copy_security_phrase == '') {
			$copy_security_phrase = '---BLANK---';
		}
	} elseif ($enable_copy_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_copy_entry_date == "enabled") && ($copy_entry_date != '')) {
		if ($copy_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $copy_entry_operator_a '$copy_entry_date' and entry_date $copy_entry_operator_b '$copy_entry_date_end' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was on") . " $copy_entry_date - $copy_entry_date_end<br />";
		}
	} elseif ($enable_copy_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_copy_modify_date == "enabled") && ($copy_modify_date != '')) {
		if ($copy_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $copy_modify_operator_a '$copy_modify_date' and modify_date $copy_modify_operator_b '$copy_modify_date_end' ";
			$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was on") . " $copy_modify_date - $copy_modify_date_end<br />";
		}
	} elseif ($enable_copy_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_copy_count == "enabled") && ($copy_count_op != '') && ($copy_count_num != '')) {
		if ($copy_count_op == '---BLANK---') {
			$copy_count_op = '';
		}
		if ($copy_count_num == '---BLANK---') {
			$copy_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $copy_count_op $copy_count_num";
		$copy_parm = $copy_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $copy_count_op_phrase $copy_count_num<br />";
		if ($copy_count_op == '') {
			$copy_count_op = '---BLANK---';
		}
		if ($copy_count_num == '') {
			$copy_count_num = '---BLANK---';
		}
	} elseif ($enable_copy_count == "enabled") {
		blank_field('Copy Count', true);
	}

	$copy_from_list_array = explode("|", $copy_from_list);
	// $copy_lead_stmt = "UPDATE vicidial_list SET list_id = '$copy_to_list' WHERE list_id IN('" . implode("', '", $copy_from_list_array) . "') $sql_where";
	// if ($DB) {
	// testEcho($copy_lead_stmt, '$copy_lead_stmt_______');
	// }
	// $copy_lead_rslt = mysql_to_mysqli($copy_lead_stmt, $link);
	// $copy_lead_count = mysqli_affected_rows($link);


	$show_vici_list_columns = "SHOW COLUMNS FROM vicidial_list";
	$vici_list_res = mysql_to_mysqli($show_vici_list_columns, $link);
	$column_rows = array();
	while ($row = mysqli_fetch_array($vici_list_res)) {
		if ($row['Field'] != 'list_id' && $row['Field'] != 'lead_id') {
			array_push($column_rows, $row['Field']);
		}
	}

	// all columns in vicidial_list except list_id and lead_id
	$column_str = implode(', ', $column_rows);

	// replace the value of the list_id column with the new list_id
	$list_id_in_stmt = "CASE WHEN list_id IS NOT NULL THEN '$copy_to_list' else '$copy_to_list' END AS list_id";

	// fetch the data from the vicidial_list table and use it for copy/insert statement
	$fetch_lead_stmt = "SELECT $column_str, $list_id_in_stmt FROM vicidial_list WHERE list_id IN('" . implode("', '", $copy_from_list_array) . "') $sql_where";

	// the insert statement
	$copy_lead_stmt = "INSERT INTO vicidial_list ($column_str, list_id) ($fetch_lead_stmt);";
	if ($DB) {
		echo "|$copy_lead_stmt|\n";
	}
	$copy_lead_rslt = mysql_to_mysqli($copy_lead_stmt, $link);
	$copy_lead_count = mysqli_affected_rows($link);

	testEcho($column_str, '$column_str____');
	testEcho($fetch_lead_stmt, '$fetch_lead_stmt___');
	testEcho($copy_lead_stmt, '$copy_lead_stmt______');


	$copy_sentence = "<B>$copy_lead_count</B> " . _QXZ("leads have been copied from list") . " $copy_from_list " . _QXZ("to") . " $copy_to_list " . _QXZ("with the following parameters") . ":<br /><br />$copy_parm <br />";

	$SQL_log = "$copy_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$copy_from_list', event_code='ADMIN Copy LEADS', event_sql=\"$SQL_log\", event_notes=\"$copy_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$copy_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
######################## copy function process end ########################




# move confirmation page
if ($move_submit == _QXZ("move")) {
	# get the variables
	$enable_move_status = "";
	$enable_move_country_code = "";
	$enable_move_vendor_lead_code = "";
	$enable_move_source_id = "";
	$enable_move_owner = "";
	$enable_move_state = "";
	$enable_move_entry_date = "";
	$enable_move_modify_date = "";
	$enable_move_security_phrase = "";
	$enable_move_count = "";
	$move_country_code = "";
	$move_vendor_lead_code = "";
	$move_source_id = "";
	$move_owner = "";
	$move_state = "";
	$move_entry_date = "";
	$move_entry_date_end = "";
	$move_entry_date_op = "";
	$move_modify_date = "";
	$move_modify_date_end = "";
	$move_modify_date_op = "";
	$move_security_phrase = "";
	$move_from_list = "";
	$move_to_list = "";
	$move_status = "";
	$move_count_op = "";
	$move_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_move_status"])) {
		$enable_move_status = $_GET["enable_move_status"];
	} elseif (isset($_POST["enable_move_status"])) {
		$enable_move_status = $_POST["enable_move_status"];
	}
	if (isset($_GET["enable_move_country_code"])) {
		$enable_move_country_code = $_GET["enable_move_country_code"];
	} elseif (isset($_POST["enable_move_country_code"])) {
		$enable_move_country_code = $_POST["enable_move_country_code"];
	}
	if (isset($_GET["enable_move_vendor_lead_code"])) {
		$enable_move_vendor_lead_code = $_GET["enable_move_vendor_lead_code"];
	} elseif (isset($_POST["enable_move_vendor_lead_code"])) {
		$enable_move_vendor_lead_code = $_POST["enable_move_vendor_lead_code"];
	}
	if (isset($_GET["enable_move_source_id"])) {
		$enable_move_source_id = $_GET["enable_move_source_id"];
	} elseif (isset($_POST["enable_move_source_id"])) {
		$enable_move_source_id = $_POST["enable_move_source_id"];
	}
	if (isset($_GET["enable_move_owner"])) {
		$enable_move_owner = $_GET["enable_move_owner"];
	} elseif (isset($_POST["enable_move_owner"])) {
		$enable_move_owner = $_POST["enable_move_owner"];
	}
	if (isset($_GET["enable_move_state"])) {
		$enable_move_state = $_GET["enable_move_state"];
	} elseif (isset($_POST["enable_move_state"])) {
		$enable_move_state = $_POST["enable_move_state"];
	}
	if (isset($_GET["enable_move_entry_date"])) {
		$enable_move_entry_date = $_GET["enable_move_entry_date"];
	} elseif (isset($_POST["enable_move_entry_date"])) {
		$enable_move_entry_date = $_POST["enable_move_entry_date"];
	}
	if (isset($_GET["enable_move_modify_date"])) {
		$enable_move_modify_date = $_GET["enable_move_modify_date"];
	} elseif (isset($_POST["enable_move_modify_date"])) {
		$enable_move_modify_date = $_POST["enable_move_modify_date"];
	}
	if (isset($_GET["enable_move_security_phrase"])) {
		$enable_move_security_phrase = $_GET["enable_move_security_phrase"];
	} elseif (isset($_POST["enable_move_security_phrase"])) {
		$enable_move_security_phrase = $_POST["enable_move_security_phrase"];
	}
	if (isset($_GET["enable_move_count"])) {
		$enable_move_count = $_GET["enable_move_count"];
	} elseif (isset($_POST["enable_move_count"])) {
		$enable_move_count = $_POST["enable_move_count"];
	}
	if (isset($_GET["move_country_code"])) {
		$move_country_code = $_GET["move_country_code"];
	} elseif (isset($_POST["move_country_code"])) {
		$move_country_code = $_POST["move_country_code"];
	}
	if (isset($_GET["move_vendor_lead_code"])) {
		$move_vendor_lead_code = $_GET["move_vendor_lead_code"];
	} elseif (isset($_POST["move_vendor_lead_code"])) {
		$move_vendor_lead_code = $_POST["move_vendor_lead_code"];
	}
	if (isset($_GET["move_source_id"])) {
		$move_source_id = $_GET["move_source_id"];
	} elseif (isset($_POST["move_source_id"])) {
		$move_source_id = $_POST["move_source_id"];
	}
	if (isset($_GET["move_owner"])) {
		$move_owner = $_GET["move_owner"];
	} elseif (isset($_POST["move_owner"])) {
		$move_owner = $_POST["move_owner"];
	}
	if (isset($_GET["move_state"])) {
		$move_state = $_GET["move_state"];
	} elseif (isset($_POST["move_state"])) {
		$move_state = $_POST["move_state"];
	}
	if (isset($_GET["move_entry_date"])) {
		$move_entry_date = $_GET["move_entry_date"];
	} elseif (isset($_POST["move_entry_date"])) {
		$move_entry_date = $_POST["move_entry_date"];
	}
	if (isset($_GET["move_entry_date_end"])) {
		$move_entry_date_end = $_GET["move_entry_date_end"];
	} elseif (isset($_POST["move_entry_date_end"])) {
		$move_entry_date_end = $_POST["move_entry_date_end"];
	}
	if (isset($_GET["move_entry_date_op"])) {
		$move_entry_date_op = $_GET["move_entry_date_op"];
	} elseif (isset($_POST["move_entry_date_op"])) {
		$move_entry_date_op = $_POST["move_entry_date_op"];
	}
	if (isset($_GET["move_modify_date"])) {
		$move_modify_date = $_GET["move_modify_date"];
	} elseif (isset($_POST["move_modify_date"])) {
		$move_modify_date = $_POST["move_modify_date"];
	}
	if (isset($_GET["move_modify_date_end"])) {
		$move_modify_date_end = $_GET["move_modify_date_end"];
	} elseif (isset($_POST["move_modify_date_end"])) {
		$move_modify_date_end = $_POST["move_modify_date_end"];
	}
	if (isset($_GET["move_modify_date_op"])) {
		$move_modify_date_op = $_GET["move_modify_date_op"];
	} elseif (isset($_POST["move_modify_date_op"])) {
		$move_modify_date_op = $_POST["move_modify_date_op"];
	}
	if (isset($_GET["move_security_phrase"])) {
		$move_security_phrase = $_GET["move_security_phrase"];
	} elseif (isset($_POST["move_security_phrase"])) {
		$move_security_phrase = $_POST["move_security_phrase"];
	}
	if (isset($_GET["move_from_list"])) {
		$move_from_list = $_GET["move_from_list"];
	} elseif (isset($_POST["move_from_list"])) {
		$move_from_list = $_POST["move_from_list"];
	}
	if (isset($_GET["move_to_list"])) {
		$move_to_list = $_GET["move_to_list"];
	} elseif (isset($_POST["move_to_list"])) {
		$move_to_list = $_POST["move_to_list"];
	}
	if (isset($_GET["move_status"])) {
		$move_status = $_GET["move_status"];
	} elseif (isset($_POST["move_status"])) {
		$move_status = $_POST["move_status"];
	}
	if (isset($_GET["move_count_op"])) {
		$move_count_op = $_GET["move_count_op"];
	} elseif (isset($_POST["move_count_op"])) {
		$move_count_op = $_POST["move_count_op"];
	}
	if (isset($_GET["move_count_num"])) {
		$move_count_num = $_GET["move_count_num"];
	} elseif (isset($_POST["move_count_num"])) {
		$move_count_num = $_POST["move_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_move_status") . " = $enable_move_status | " . _QXZ("enable_move_country_code") . " = $enable_move_country_code | " . _QXZ("enable_move_vendor_lead_code") . " = $enable_move_vendor_lead_code | " . _QXZ("enable_move_source_id") . " = $enable_move_source_id | " . _QXZ("enable_move_owner") . " = $enable_move_owner | " . _QXZ("enable_move_state") . " = $enable_move_state | " . _QXZ("enable_move_entry_date") . " = $enable_move_entry_date | " . _QXZ("enable_move_modify_date") . " = $enable_move_modify_date | " . _QXZ("enable_move_security_phrase") . " = $enable_move_security_phrase | " . _QXZ("enable_move_count") . " = $enable_move_count | " . _QXZ("move_country_code") . " = $move_country_code | " . _QXZ("move_vendor_lead_code") . " = $move_vendor_lead_code | " . _QXZ("move_source_id") . " = $move_source_id | " . _QXZ("move_owner") . " = $move_owner | " . _QXZ("move_state") . " = $move_state | " . _QXZ("move_entry_date") . " = $move_entry_date | " . _QXZ("move_entry_date_end") . " = $move_entry_date_end | " . _QXZ("move_entry_date_op") . " = $move_entry_date_op | " . _QXZ("move_modify_date") . " = $move_modify_date | " . _QXZ("move_modify_date_end") . " = $move_modify_date_end | " . _QXZ("move_modify_date_op") . " = $move_modify_date_op | " . _QXZ("move_security_phrase") . " = $move_security_phrase | " . _QXZ("move_from_list") . " = $move_from_list | " . _QXZ("move_to_list") . " = $move_to_list | " . _QXZ("move_status") . " = $move_status | " . _QXZ("move_count_op") . " = $move_count_op | " . _QXZ("move_count_num") . " = $move_count_num</p>";
	}

	# filter out anything bad
	$enable_move_status = preg_replace('/[^a-zA-Z]/', '', $enable_move_status);
	$enable_move_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_move_country_code);
	$enable_move_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_move_vendor_lead_code);
	$enable_move_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_move_source_id);
	$enable_move_owner = preg_replace('/[^a-zA-Z]/', '', $enable_move_owner);
	$enable_move_state = preg_replace('/[^a-zA-Z]/', '', $enable_move_state);
	$enable_move_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_move_entry_date);
	$enable_move_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_move_modify_date);
	$enable_move_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_move_security_phrase);
	$enable_move_count = preg_replace('/[^a-zA-Z]/', '', $enable_move_count);
	$move_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $move_country_code);
	$move_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_vendor_lead_code);
	$move_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_source_id);
	$move_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_owner);
	$move_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $move_state);
	$move_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_entry_date);
	$move_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_entry_date_end);
	$move_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $move_entry_date_op);
	$move_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_modify_date);
	$move_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_modify_date_end);
	$move_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $move_modify_date_op);
	$move_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_security_phrase);
	$move_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $move_status);
	$move_from_list = preg_replace('/[^0-9\|]/', '', $move_from_list);
	$move_to_list = preg_replace('/[^0-9]/', '', $move_to_list);
	$move_count_num = preg_replace('/[^0-9]/', '', $move_count_num);
	$move_count_op = preg_replace('/[^<>=]/', '', $move_count_op);

	# build the count operation phrase
	$move_count_op_phrase = "";
	if ($move_count_op == "<") {
		$move_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_count_op == "<=") {
		$move_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_count_op == ">") {
		$move_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_count_op == ">=") {
		$move_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the move_entry_date operation phrase
	$move_entry_date_op_phrase = "";
	$move_entry_operator_a = '>=';
	$move_entry_operator_b = '<=';
	if ($move_entry_date_op == "<") {
		$move_entry_operator_a = '>=';
		$move_entry_operator_b = '<';
		$move_entry_date_end = $move_entry_date;
		$move_entry_date = '0000-00-00 00:00:00';
		$move_entry_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_entry_date_op == "<=") {
		$move_entry_date_end = $move_entry_date;
		$move_entry_date = '0000-00-00 00:00:00';
		$move_entry_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_entry_date_op == ">") {
		$move_entry_operator_a = '>';
		$move_entry_operator_b = '<';
		$move_entry_date_end = '2100-00-00 00:00:00';
		$move_entry_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_entry_date_op == ">=") {
		$move_entry_date_end = '2100-00-00 00:00:00';
		$move_entry_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($move_entry_date_op == "range") {
		$move_entry_date_op_phrase = _QXZ("range") . " ";
	} elseif ($move_entry_date_op == "=") {
		$move_entry_date_end = $move_entry_date;
		$move_entry_date_op_phrase = _QXZ("equal to") . " ";
	}

	# build the move_modify_date operation phrase
	$move_modify_date_op_phrase = "";
	$move_modify_operator_a = '>=';
	$move_modify_operator_b = '<=';
	if ($move_modify_date_op == "<") {
		$move_modify_operator_a = '>=';
		$move_modify_operator_b = '<';
		$move_entry_date_end = $move_entry_date;
		$move_entry_date = '0000-00-00 00:00:00';
		$move_modify_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_modify_date_op == "<=") {
		$move_entry_date_end = $move_entry_date;
		$move_entry_date = '0000-00-00 00:00:00';
		$move_modify_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_modify_date_op == ">") {
		$move_modify_operator_a = '>';
		$move_modify_operator_b = '<';
		$move_entry_date_end = '2100-00-00 00:00:00';
		$move_modify_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_modify_date_op == ">=") {
		$move_entry_date_end = '2100-00-00 00:00:00';
		$move_modify_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($move_modify_date_op == "range") {
		$move_modify_date_op_phrase = _QXZ("range") . " ";
	} elseif ($move_modify_date_op == "=") {
		$move_entry_date_end = $move_entry_date;
		$move_modify_date_op_phrase = _QXZ("equal to") . " ";
	}

	if (strlen($move_entry_date) == 10) {
		$move_entry_date .= " 00:00:00";
	}
	if (strlen($move_entry_date_end) == 10) {
		$move_entry_date_end .= " 23:59:59";
	}
	if (strlen($move_modify_date) == 10) {
		$move_modify_date .= " 00:00:00";
	}
	if (strlen($move_modify_date_end) == 10) {
		$move_modify_date_end .= " 23:59:59";
	}


	if ($DB) {
		echo "<p>" . _QXZ("enable_move_status") . " = $enable_move_status | " . _QXZ("enable_move_country_code") . " = $enable_move_country_code | " . _QXZ("enable_move_vendor_lead_code") . " = $enable_move_vendor_lead_code | " . _QXZ("enable_move_source_id") . " = $enable_move_source_id | " . _QXZ("enable_move_owner") . " = $enable_move_owner | " . _QXZ("enable_move_state") . " = $enable_move_state | " . _QXZ("enable_move_entry_date") . " = $enable_move_entry_date | " . _QXZ("enable_move_modify_date") . " = $enable_move_modify_date | " . _QXZ("enable_move_security_phrase") . " = $enable_move_security_phrase | " . _QXZ("enable_move_count") . " = $enable_move_count | " . _QXZ("move_country_code") . " = $move_country_code | " . _QXZ("move_vendor_lead_code") . " = $move_vendor_lead_code | " . _QXZ("move_source_id") . " = $move_source_id | " . _QXZ("move_owner") . " = $move_owner | " . _QXZ("move_state") . " = $move_state | " . _QXZ("move_entry_date") . " = $move_entry_date | " . _QXZ("move_entry_date_end") . " = $move_entry_date_end | " . _QXZ("move_entry_date_op") . " = $move_entry_date_op | " . _QXZ("move_modify_date") . " = $move_modify_date | " . _QXZ("move_modify_date_end") . " = $move_modify_date_end | " . _QXZ("move_modify_date_op") . " = $move_modify_date_op | " . _QXZ("move_security_phrase") . " = $move_security_phrase | " . _QXZ("move_from_list") . " = $move_from_list | " . _QXZ("move_to_list") . " = $move_to_list | " . _QXZ("move_status") . " = $move_status | " . _QXZ("move_count_op") . " = $move_count_op | " . _QXZ("move_count_num") . " = $move_count_num</p>";
	}

	# make sure the required fields are set
	if ($move_from_list == '') {
		missing_required_field('From List');
	}
	if ($move_to_list == '') {
		missing_required_field('To List');
	}


	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$move_parm = "";
	if (($enable_move_status == "enabled") && ($move_status != '')) {
		if ($move_status == '---BLANK---') {
			$move_status = '';
		}
		$sql_where = $sql_where . " and status like '$move_status' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $move_status<br />";
		if ($move_status == '') {
			$move_status = '---BLANK---';
		}
	} elseif ($enable_move_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_move_country_code == "enabled") && ($move_country_code != '')) {
		if ($move_country_code == '---BLANK---') {
			$move_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$move_country_code\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $move_country_code<br />";
		if ($move_country_code == '') {
			$move_country_code = '---BLANK---';
		}
	} elseif ($enable_move_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_move_vendor_lead_code == "enabled") && ($move_vendor_lead_code != '')) {
		if ($move_vendor_lead_code == '---BLANK---') {
			$move_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$move_vendor_lead_code\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $move_vendor_lead_code<br />";
		if ($move_vendor_lead_code == '') {
			$move_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_move_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_move_source_id == "enabled") && ($move_source_id != '')) {
		if ($move_source_id == '---BLANK---') {
			$move_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$move_source_id\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $move_source_id<br />";
		if ($move_source_id == '') {
			$move_source_id = '---BLANK---';
		}
	} elseif ($enable_move_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_move_owner == "enabled") && ($move_owner != '')) {
		if ($move_owner == '---BLANK---') {
			$move_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$move_owner\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $move_owner<br />";
		if ($move_owner == '') {
			$move_owner = '---BLANK---';
		}
	} elseif ($enable_move_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_move_state == "enabled") && ($move_state != '')) {
		if ($move_state == '---BLANK---') {
			$move_state = '';
		}
		$sql_where = $sql_where . " and state like \"$move_state\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $move_state<br />";
		if ($move_state == '') {
			$move_state = '---BLANK---';
		}
	} elseif ($enable_move_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_move_security_phrase == "enabled") && ($move_security_phrase != '')) {
		if ($move_security_phrase == '---BLANK---') {
			$move_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$move_security_phrase\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $move_security_phrase<br />";
		if ($move_security_phrase == '') {
			$move_security_phrase = '---BLANK---';
		}
	} elseif ($enable_move_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_move_entry_date == "enabled") && ($move_entry_date != '')) {
		if ($move_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $move_entry_operator_a '$move_entry_date' and entry_date $move_entry_operator_b '$move_entry_date_end' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was on") . " $move_entry_date - $move_entry_date_end<br />";
		}
	} elseif ($enable_move_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_move_modify_date == "enabled") && ($move_modify_date != '')) {
		if ($move_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $move_modify_operator_a '$move_modify_date' and modify_date $move_modify_operator_b '$move_modify_date_end' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was on") . " $move_modify_date - $move_modify_date_end<br />";
		}
	} elseif ($enable_move_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_move_count == "enabled") && ($move_count_op != '') && ($move_count_num != '')) {
		if ($move_count_op == '---BLANK---') {
			$move_count_op = '';
		}
		if ($move_count_num == '---BLANK---') {
			$move_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $move_count_op $move_count_num";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $move_count_op_phrase $move_count_num<br />";
		if ($move_count_op == '') {
			$move_count_op = '---BLANK---';
		}
		if ($move_count_num == '') {
			$move_count_num = '---BLANK---';
		}
	} elseif ($enable_move_count == "enabled") {
		blank_field('Move Count', true);
	}

	# get the number of leads this action will move
	$move_lead_count = 0;
	$move_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id IN('" . implode("','", $move_from_list) . "') $sql_where";
	if ($DB) {
		echo "|$move_lead_count_stmt|\n";
	}
	$move_lead_count_rslt = mysql_to_mysqli($move_lead_count_stmt, $link);
	$move_lead_count_row = mysqli_fetch_row($move_lead_count_rslt);
	$move_lead_count = $move_lead_count_row[0];

	# get the number of leads in the list this action will move to
	$to_list_lead_count = 0;
	$to_list_lead_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$move_to_list'";
	if ($DB) {
		echo "|$to_list_lead_stmt|\n";
	}
	$to_list_lead_rslt = mysql_to_mysqli($to_list_lead_stmt, $link);
	$to_list_lead_row = mysqli_fetch_row($to_list_lead_rslt);
	$to_list_lead_count = $to_list_lead_row[0];

	# check to see if we will exceed list_lead_limit in the move to list
	if ($to_list_lead_count + $move_lead_count > $list_lead_limit) {
		echo "<html>\n";
		echo "<head>\n";
		echo "<!-- VERSION: $version     BUILD: $build -->\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<p>" . _QXZ("Sorry. This operation will cause list") . " $move_to_list " . _QXZ("to exceed") . " $list_lead_limit " . _QXZ("leads which is not allowed") . ".</p>\n";
		echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
		echo "</body>\n</html>\n";
	} else {
		echo "<p>" . _QXZ("You are about to move") . " <B>$move_lead_count</B> " . _QXZ("leads from list") . " " . implode(",", $move_from_list) . " " . _QXZ("to") . " $move_to_list " . _QXZ("with the following parameters") . ":<br /><br />$move_parm <br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
		echo "<center><form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=enable_move_status value='$enable_move_status'>\n";
		echo "<input type=hidden name=enable_move_country_code value='$enable_move_country_code'>\n";
		echo "<input type=hidden name=enable_move_vendor_lead_code value='$enable_move_vendor_lead_code'>\n";
		echo "<input type=hidden name=enable_move_source_id value='$enable_move_source_id'>\n";
		echo "<input type=hidden name=enable_move_owner value='$enable_move_owner'>\n";
		echo "<input type=hidden name=enable_move_state value='$enable_move_state'>\n";
		echo "<input type=hidden name=enable_move_entry_date value='$enable_move_entry_date'>\n";
		echo "<input type=hidden name=enable_move_modify_date value='$enable_move_modify_date'>\n";
		echo "<input type=hidden name=enable_move_security_phrase value='$enable_move_security_phrase'>\n";
		echo "<input type=hidden name=enable_move_count value='$enable_move_count'>\n";
		echo "<input type=hidden name=move_country_code value=\"$move_country_code\">\n";
		echo "<input type=hidden name=move_vendor_lead_code value=\"$move_vendor_lead_code\">\n";
		echo "<input type=hidden name=move_source_id value=\"$move_source_id\">\n";
		echo "<input type=hidden name=move_owner value=\"$move_owner\">\n";
		echo "<input type=hidden name=move_state value=\"$move_state\">\n";
		echo "<input type=hidden name=move_entry_date value=\"$move_entry_date\">\n";
		echo "<input type=hidden name=move_entry_date_end value=\"$move_entry_date_end\">\n";
		echo "<input type=hidden name=move_entry_date_op value=\"$move_entry_date_op\">\n";
		echo "<input type=hidden name=move_modify_date value=\"$move_modify_date\">\n";
		echo "<input type=hidden name=move_modify_date_end value=\"$move_modify_date_end\">\n";
		echo "<input type=hidden name=move_modify_date_op value=\"$move_modify_date_op\">\n";
		echo "<input type=hidden name=move_security_phrase value=\"$move_security_phrase\">\n";
		echo "<input type=hidden name=move_from_list value='" . implode("|", $move_from_list) . "'>\n";
		echo "<input type=hidden name=move_to_list value=\"$move_to_list\">\n";
		echo "<input type=hidden name=move_status value=\"$move_status\">\n";
		echo "<input type=hidden name=move_count_op value=\"$move_count_op\">\n";
		echo "<input type=hidden name=move_count_num value=\"$move_count_num\">\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_move value='" . _QXZ("confirm") . "'>\n";
		echo "</form></center>\n";
		echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
		echo "</body>\n</html>\n";
	}
}

# actually do the move
if ($confirm_move == _QXZ("confirm")) {
	# get the variables
	$enable_move_status = "";
	$enable_move_country_code = "";
	$enable_move_vendor_lead_code = "";
	$enable_move_source_id = "";
	$enable_move_owner = "";
	$enable_move_state = "";
	$enable_move_entry_date = "";
	$enable_move_modify_date = "";
	$enable_move_security_phrase = "";
	$enable_move_count = "";
	$move_country_code = "";
	$move_vendor_lead_code = "";
	$move_source_id = "";
	$move_owner = "";
	$move_state = "";
	$move_entry_date = "";
	$move_entry_date_end = "";
	$move_entry_date_op = "";
	$move_modify_date = "";
	$move_modify_date_end = "";
	$move_modify_date_op = "";
	$move_security_phrase = "";
	$move_from_list = "";
	$move_to_list = "";
	$move_status = "";
	$move_count_op = "";
	$move_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_move_status"])) {
		$enable_move_status = $_GET["enable_move_status"];
	} elseif (isset($_POST["enable_move_status"])) {
		$enable_move_status = $_POST["enable_move_status"];
	}
	if (isset($_GET["enable_move_country_code"])) {
		$enable_move_country_code = $_GET["enable_move_country_code"];
	} elseif (isset($_POST["enable_move_country_code"])) {
		$enable_move_country_code = $_POST["enable_move_country_code"];
	}
	if (isset($_GET["enable_move_vendor_lead_code"])) {
		$enable_move_vendor_lead_code = $_GET["enable_move_vendor_lead_code"];
	} elseif (isset($_POST["enable_move_vendor_lead_code"])) {
		$enable_move_vendor_lead_code = $_POST["enable_move_vendor_lead_code"];
	}
	if (isset($_GET["enable_move_source_id"])) {
		$enable_move_source_id = $_GET["enable_move_source_id"];
	} elseif (isset($_POST["enable_move_source_id"])) {
		$enable_move_source_id = $_POST["enable_move_source_id"];
	}
	if (isset($_GET["enable_move_owner"])) {
		$enable_move_owner = $_GET["enable_move_owner"];
	} elseif (isset($_POST["enable_move_owner"])) {
		$enable_move_owner = $_POST["enable_move_owner"];
	}
	if (isset($_GET["enable_move_state"])) {
		$enable_move_state = $_GET["enable_move_state"];
	} elseif (isset($_POST["enable_move_state"])) {
		$enable_move_state = $_POST["enable_move_state"];
	}
	if (isset($_GET["enable_move_entry_date"])) {
		$enable_move_entry_date = $_GET["enable_move_entry_date"];
	} elseif (isset($_POST["enable_move_entry_date"])) {
		$enable_move_entry_date = $_POST["enable_move_entry_date"];
	}
	if (isset($_GET["enable_move_modify_date"])) {
		$enable_move_modify_date = $_GET["enable_move_modify_date"];
	} elseif (isset($_POST["enable_move_modify_date"])) {
		$enable_move_modify_date = $_POST["enable_move_modify_date"];
	}
	if (isset($_GET["enable_move_security_phrase"])) {
		$enable_move_security_phrase = $_GET["enable_move_security_phrase"];
	} elseif (isset($_POST["enable_move_security_phrase"])) {
		$enable_move_security_phrase = $_POST["enable_move_security_phrase"];
	}
	if (isset($_GET["enable_move_count"])) {
		$enable_move_count = $_GET["enable_move_count"];
	} elseif (isset($_POST["enable_move_count"])) {
		$enable_move_count = $_POST["enable_move_count"];
	}
	if (isset($_GET["move_country_code"])) {
		$move_country_code = $_GET["move_country_code"];
	} elseif (isset($_POST["move_country_code"])) {
		$move_country_code = $_POST["move_country_code"];
	}
	if (isset($_GET["move_vendor_lead_code"])) {
		$move_vendor_lead_code = $_GET["move_vendor_lead_code"];
	} elseif (isset($_POST["move_vendor_lead_code"])) {
		$move_vendor_lead_code = $_POST["move_vendor_lead_code"];
	}
	if (isset($_GET["move_source_id"])) {
		$move_source_id = $_GET["move_source_id"];
	} elseif (isset($_POST["move_source_id"])) {
		$move_source_id = $_POST["move_source_id"];
	}
	if (isset($_GET["move_owner"])) {
		$move_owner = $_GET["move_owner"];
	} elseif (isset($_POST["move_owner"])) {
		$move_owner = $_POST["move_owner"];
	}
	if (isset($_GET["move_state"])) {
		$move_state = $_GET["move_state"];
	} elseif (isset($_POST["move_state"])) {
		$move_state = $_POST["move_state"];
	}
	if (isset($_GET["move_entry_date"])) {
		$move_entry_date = $_GET["move_entry_date"];
	} elseif (isset($_POST["move_entry_date"])) {
		$move_entry_date = $_POST["move_entry_date"];
	}
	if (isset($_GET["move_entry_date_end"])) {
		$move_entry_date_end = $_GET["move_entry_date_end"];
	} elseif (isset($_POST["move_entry_date_end"])) {
		$move_entry_date_end = $_POST["move_entry_date_end"];
	}
	if (isset($_GET["move_entry_date_op"])) {
		$move_entry_date_op = $_GET["move_entry_date_op"];
	} elseif (isset($_POST["move_entry_date_op"])) {
		$move_entry_date_op = $_POST["move_entry_date_op"];
	}
	if (isset($_GET["move_modify_date"])) {
		$move_modify_date = $_GET["move_modify_date"];
	} elseif (isset($_POST["move_modify_date"])) {
		$move_modify_date = $_POST["move_modify_date"];
	}
	if (isset($_GET["move_modify_date_end"])) {
		$move_modify_date_end = $_GET["move_modify_date_end"];
	} elseif (isset($_POST["move_modify_date_end"])) {
		$move_modify_date_end = $_POST["move_modify_date_end"];
	}
	if (isset($_GET["move_modify_date_op"])) {
		$move_modify_date_op = $_GET["move_modify_date_op"];
	} elseif (isset($_POST["move_modify_date_op"])) {
		$move_modify_date_op = $_POST["move_modify_date_op"];
	}
	if (isset($_GET["move_security_phrase"])) {
		$move_security_phrase = $_GET["move_security_phrase"];
	} elseif (isset($_POST["move_security_phrase"])) {
		$move_security_phrase = $_POST["move_security_phrase"];
	}
	if (isset($_GET["move_from_list"])) {
		$move_from_list = $_GET["move_from_list"];
	} elseif (isset($_POST["move_from_list"])) {
		$move_from_list = $_POST["move_from_list"];
	}
	if (isset($_GET["move_to_list"])) {
		$move_to_list = $_GET["move_to_list"];
	} elseif (isset($_POST["move_to_list"])) {
		$move_to_list = $_POST["move_to_list"];
	}
	if (isset($_GET["move_status"])) {
		$move_status = $_GET["move_status"];
	} elseif (isset($_POST["move_status"])) {
		$move_status = $_POST["move_status"];
	}
	if (isset($_GET["move_count_op"])) {
		$move_count_op = $_GET["move_count_op"];
	} elseif (isset($_POST["move_count_op"])) {
		$move_count_op = $_POST["move_count_op"];
	}
	if (isset($_GET["move_count_num"])) {
		$move_count_num = $_GET["move_count_num"];
	} elseif (isset($_POST["move_count_num"])) {
		$move_count_num = $_POST["move_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_move_status") . " = $enable_move_status | " . _QXZ("enable_move_country_code") . " = $enable_move_country_code | " . _QXZ("enable_move_vendor_lead_code") . " = $enable_move_vendor_lead_code | " . _QXZ("enable_move_source_id") . " = $enable_move_source_id | " . _QXZ("enable_move_owner") . " = $enable_move_owner | " . _QXZ("enable_move_state") . " = $enable_move_state | " . _QXZ("enable_move_entry_date") . " = $enable_move_entry_date | " . _QXZ("enable_move_modify_date") . " = $enable_move_modify_date | " . _QXZ("enable_move_security_phrase") . " = $enable_move_security_phrase | " . _QXZ("enable_move_count") . " = $enable_move_count | " . _QXZ("move_country_code") . " = $move_country_code | " . _QXZ("move_vendor_lead_code") . " = $move_vendor_lead_code | " . _QXZ("move_source_id") . " = $move_source_id | " . _QXZ("move_owner") . " = $move_owner | " . _QXZ("move_state") . " = $move_state | " . _QXZ("move_entry_date") . " = $move_entry_date | " . _QXZ("move_entry_date_end") . " = $move_entry_date_end | " . _QXZ("move_entry_date_op") . " = $move_entry_date_op | " . _QXZ("move_modify_date") . " = $move_modify_date | " . _QXZ("move_modify_date_end") . " = $move_modify_date_end | " . _QXZ("move_modify_date_op") . " = $move_modify_date_op | " . _QXZ("move_security_phrase") . " = $move_security_phrase | " . _QXZ("move_from_list") . " = $move_from_list | " . _QXZ("move_to_list") . " = $move_to_list | " . _QXZ("move_status") . " = $move_status | " . _QXZ("move_count_op") . " = $move_count_op | " . _QXZ("move_count_num") . " = $move_count_num</p>";
	}

	# filter out anything bad
	$enable_move_status = preg_replace('/[^a-zA-Z]/', '', $enable_move_status);
	$enable_move_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_move_country_code);
	$enable_move_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_move_vendor_lead_code);
	$enable_move_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_move_source_id);
	$enable_move_owner = preg_replace('/[^a-zA-Z]/', '', $enable_move_owner);
	$enable_move_state = preg_replace('/[^a-zA-Z]/', '', $enable_move_state);
	$enable_move_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_move_entry_date);
	$enable_move_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_move_modify_date);
	$enable_move_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_move_security_phrase);
	$enable_move_count = preg_replace('/[^a-zA-Z]/', '', $enable_move_count);
	$move_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $move_country_code);
	$move_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_vendor_lead_code);
	$move_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_source_id);
	$move_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_owner);
	$move_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $move_state);
	$move_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_entry_date);
	$move_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_entry_date_end);
	$move_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $move_entry_date_op);
	$move_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_modify_date);
	$move_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $move_modify_date_end);
	$move_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $move_modify_date_op);
	$move_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $move_security_phrase);
	$move_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $move_status);
	$move_from_list = preg_replace('/[^0-9\|]/', '', $move_from_list);
	$move_to_list = preg_replace('/[^0-9]/', '', $move_to_list);
	$move_count_num = preg_replace('/[^0-9]/', '', $move_count_num);
	$move_count_op = preg_replace('/[^<>=]/', '', $move_count_op);

	# count operator
	$move_count_op_phrase = "";
	if ($move_count_op == "<") {
		$move_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_count_op == "<=") {
		$move_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_count_op == ">") {
		$move_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_count_op == ">=") {
		$move_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the move_entry_date operation phrase
	$move_entry_date_op_phrase = "";
	$move_entry_operator_a = '>=';
	$move_entry_operator_b = '<=';
	if ($move_entry_date_op == "<") {
		$move_entry_operator_a = '>=';
		$move_entry_operator_b = '<';
		$move_entry_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_entry_date_op == "<=") {
		$move_entry_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_entry_date_op == ">") {
		$move_entry_operator_a = '>';
		$move_entry_operator_b = '<';
		$move_entry_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_entry_date_op == ">=") {
		$move_entry_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($move_entry_date_op == "range") {
		$move_entry_date_op_phrase = _QXZ("range") . " ";
	} elseif ($move_entry_date_op == "=") {
		$move_entry_date_op_phrase = _QXZ("equal to") . " ";
	}

	# build the move_modify_date operation phrase
	$move_modify_date_op_phrase = "";
	$move_modify_operator_a = '>=';
	$move_modify_operator_b = '<=';
	if ($move_modify_date_op == "<") {
		$move_modify_operator_a = '>=';
		$move_modify_operator_b = '<';
		$move_modify_date_op_phrase = _QXZ("less than") . " ";
	} elseif ($move_modify_date_op == "<=") {
		$move_modify_date_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($move_modify_date_op == ">") {
		$move_modify_operator_a = '>';
		$move_modify_operator_b = '<';
		$move_modify_date_op_phrase = _QXZ("greater than") . " ";
	} elseif ($move_modify_date_op == ">=") {
		$move_modify_date_op_phrase = _QXZ("greater than or equal to") . " ";
	} elseif ($move_modify_date_op == "range") {
		$move_modify_date_op_phrase = _QXZ("range") . " ";
	} elseif ($move_modify_date_op == "=") {
		$move_modify_date_op_phrase = _QXZ("equal to") . " ";
	}

	if (strlen($move_entry_date) == 10) {
		$move_entry_date .= " 00:00:00";
	}
	if (strlen($move_entry_date_end) == 10) {
		$move_entry_date_end .= " 23:59:59";
	}
	if (strlen($move_modify_date) == 10) {
		$move_modify_date .= " 00:00:00";
	}
	if (strlen($move_modify_date_end) == 10) {
		$move_modify_date_end .= " 23:59:59";
	}


	if ($DB) {
		echo "<p>" . _QXZ("enable_move_status") . " = $enable_move_status | " . _QXZ("enable_move_country_code") . " = $enable_move_country_code | " . _QXZ("enable_move_vendor_lead_code") . " = $enable_move_vendor_lead_code | " . _QXZ("enable_move_source_id") . " = $enable_move_source_id | " . _QXZ("enable_move_owner") . " = $enable_move_owner | " . _QXZ("enable_move_state") . " = $enable_move_state | " . _QXZ("enable_move_entry_date") . " = $enable_move_entry_date | " . _QXZ("enable_move_modify_date") . " = $enable_move_modify_date | " . _QXZ("enable_move_security_phrase") . " = $enable_move_security_phrase | " . _QXZ("enable_move_count") . " = $enable_move_count | " . _QXZ("move_country_code") . " = $move_country_code | " . _QXZ("move_vendor_lead_code") . " = $move_vendor_lead_code | " . _QXZ("move_source_id") . " = $move_source_id | " . _QXZ("move_owner") . " = $move_owner | " . _QXZ("move_state") . " = $move_state | " . _QXZ("move_entry_date") . " = $move_entry_date | " . _QXZ("move_entry_date_end") . " = $move_entry_date_end | " . _QXZ("move_entry_date_op") . " = $move_entry_date_op | " . _QXZ("move_modify_date") . " = $move_modify_date | " . _QXZ("move_modify_date_end") . " = $move_modify_date_end | " . _QXZ("move_modify_date_op") . " = $move_modify_date_op | " . _QXZ("move_security_phrase") . " = $move_security_phrase | " . _QXZ("move_from_list") . " = $move_from_list | " . _QXZ("move_to_list") . " = $move_to_list | " . _QXZ("move_status") . " = $move_status | " . _QXZ("move_count_op") . " = $move_count_op | " . _QXZ("move_count_num") . " = $move_count_num</p>";
	}


	# make sure the required fields are set
	if ($move_from_list == '') {
		missing_required_field('From List');
	}
	if ($move_to_list == '') {
		missing_required_field('To List');
	}

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$move_parm = "";
	if (($enable_move_status == "enabled") && ($move_status != '')) {
		if ($move_status == '---BLANK---') {
			$move_status = '';
		}
		$sql_where = $sql_where . " and status like '$move_status' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $move_status<br />";
		if ($move_status == '') {
			$move_status = '---BLANK---';
		}
	} elseif ($enable_move_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_move_country_code == "enabled") && ($move_country_code != '')) {
		if ($move_country_code == '---BLANK---') {
			$move_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$move_country_code\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $move_country_code<br />";
		if ($move_country_code == '') {
			$move_country_code = '---BLANK---';
		}
	} elseif ($enable_move_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_move_vendor_lead_code == "enabled") && ($move_vendor_lead_code != '')) {
		if ($move_vendor_lead_code == '---BLANK---') {
			$move_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$move_vendor_lead_code\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $move_vendor_lead_code<br />";
		if ($move_vendor_lead_code == '') {
			$move_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_move_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_move_source_id == "enabled") && ($move_source_id != '')) {
		if ($move_source_id == '---BLANK---') {
			$move_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$move_source_id\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $move_source_id<br />";
		if ($move_source_id == '') {
			$move_source_id = '---BLANK---';
		}
	} elseif ($enable_move_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_move_owner == "enabled") && ($move_owner != '')) {
		if ($move_owner == '---BLANK---') {
			$move_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$move_owner\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $move_owner<br />";
		if ($move_owner == '') {
			$move_owner = '---BLANK---';
		}
	} elseif ($enable_move_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_move_state == "enabled") && ($move_state != '')) {
		if ($move_state == '---BLANK---') {
			$move_state = '';
		}
		$sql_where = $sql_where . " and state like \"$move_state\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $move_state<br />";
		if ($move_state == '') {
			$move_state = '---BLANK---';
		}
	} elseif ($enable_move_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_move_security_phrase == "enabled") && ($move_security_phrase != '')) {
		if ($move_security_phrase == '---BLANK---') {
			$move_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$move_security_phrase\" ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $move_security_phrase<br />";
		if ($move_security_phrase == '') {
			$move_security_phrase = '---BLANK---';
		}
	} elseif ($enable_move_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_move_entry_date == "enabled") && ($move_entry_date != '')) {
		if ($move_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $move_entry_operator_a '$move_entry_date' and entry_date $move_entry_operator_b '$move_entry_date_end' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was on") . " $move_entry_date - $move_entry_date_end<br />";
		}
	} elseif ($enable_move_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_move_modify_date == "enabled") && ($move_modify_date != '')) {
		if ($move_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $move_modify_operator_a '$move_modify_date' and modify_date $move_modify_operator_b '$move_modify_date_end' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was on") . " $move_modify_date - $move_modify_date_end<br />";
		}
	} elseif ($enable_move_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_move_count == "enabled") && ($move_count_op != '') && ($move_count_num != '')) {
		if ($move_count_op == '---BLANK---') {
			$move_count_op = '';
		}
		if ($move_count_num == '---BLANK---') {
			$move_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $move_count_op $move_count_num";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $move_count_op_phrase $move_count_num<br />";
		if ($move_count_op == '') {
			$move_count_op = '---BLANK---';
		}
		if ($move_count_num == '') {
			$move_count_num = '---BLANK---';
		}
	} elseif ($enable_move_count == "enabled") {
		blank_field('Move Count', true);
	}

	$move_from_list_array = explode("|", $move_from_list);
	$move_lead_stmt = "UPDATE vicidial_list SET list_id = '$move_to_list' WHERE list_id IN('" . implode("', '", $move_from_list_array) . "') $sql_where";
	if ($DB) {
		echo "|$move_lead_stmt|\n";
	}
	$move_lead_rslt = mysql_to_mysqli($move_lead_stmt, $link);
	$move_lead_count = mysqli_affected_rows($link);

	$move_sentence = "<B>$move_lead_count</B> " . _QXZ("leads have been moved from list") . " $move_from_list " . _QXZ("to") . " $move_to_list " . _QXZ("with the following parameters") . ":<br /><br />$move_parm <br />";

	$SQL_log = "$move_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$move_from_list', event_code='ADMIN MOVE LEADS', event_sql=\"$SQL_log\", event_notes=\"$move_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$move_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
##### END move process #####



##### BEGIN update confirmation page #####
if ($update_submit == _QXZ("update")) {
	# get the variables
	$enable_update_from_status = "";
	$enable_update_country_code = "";
	$enable_update_vendor_lead_code = "";
	$enable_update_source_id = "";
	$enable_update_owner = "";
	$enable_update_state = "";
	$enable_update_entry_date = "";
	$enable_update_modify_date = "";
	$enable_update_security_phrase = "";
	$enable_update_count = "";
	$update_country_code = "";
	$update_vendor_lead_code = "";
	$update_source_id = "";
	$update_owner = "";
	$update_state = "";
	$update_entry_date = "";
	$update_entry_date_end = "";
	$update_entry_date_op = "";
	$update_modify_date = "";
	$update_modify_date_end = "";
	$update_modify_date_op = "";
	$update_security_phrase = "";
	$update_list = "";
	$update_to_status = "";
	$update_from_status = "";
	$update_count_op = "";
	$update_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_update_from_status"])) {
		$enable_update_from_status = $_GET["enable_update_from_status"];
	} elseif (isset($_POST["enable_update_from_status"])) {
		$enable_update_from_status = $_POST["enable_update_from_status"];
	}
	if (isset($_GET["enable_update_country_code"])) {
		$enable_update_country_code = $_GET["enable_update_country_code"];
	} elseif (isset($_POST["enable_update_country_code"])) {
		$enable_update_country_code = $_POST["enable_update_country_code"];
	}
	if (isset($_GET["enable_update_vendor_lead_code"])) {
		$enable_update_vendor_lead_code = $_GET["enable_update_vendor_lead_code"];
	} elseif (isset($_POST["enable_update_vendor_lead_code"])) {
		$enable_update_vendor_lead_code = $_POST["enable_update_vendor_lead_code"];
	}
	if (isset($_GET["enable_update_source_id"])) {
		$enable_update_source_id = $_GET["enable_update_source_id"];
	} elseif (isset($_POST["enable_update_source_id"])) {
		$enable_update_source_id = $_POST["enable_update_source_id"];
	}
	if (isset($_GET["enable_update_owner"])) {
		$enable_update_owner = $_GET["enable_update_owner"];
	} elseif (isset($_POST["enable_update_owner"])) {
		$enable_update_owner = $_POST["enable_update_owner"];
	}
	if (isset($_GET["enable_update_state"])) {
		$enable_update_state = $_GET["enable_update_state"];
	} elseif (isset($_POST["enable_update_state"])) {
		$enable_update_state = $_POST["enable_update_state"];
	}
	if (isset($_GET["enable_update_entry_date"])) {
		$enable_update_entry_date = $_GET["enable_update_entry_date"];
	} elseif (isset($_POST["enable_update_entry_date"])) {
		$enable_update_entry_date = $_POST["enable_update_entry_date"];
	}
	if (isset($_GET["enable_update_modify_date"])) {
		$enable_update_modify_date = $_GET["enable_update_modify_date"];
	} elseif (isset($_POST["enable_update_modify_date"])) {
		$enable_update_modify_date = $_POST["enable_update_modify_date"];
	}
	if (isset($_GET["enable_update_security_phrase"])) {
		$enable_update_security_phrase = $_GET["enable_update_security_phrase"];
	} elseif (isset($_POST["enable_update_security_phrase"])) {
		$enable_update_security_phrase = $_POST["enable_update_security_phrase"];
	}
	if (isset($_GET["enable_update_count"])) {
		$enable_update_count = $_GET["enable_update_count"];
	} elseif (isset($_POST["enable_update_count"])) {
		$enable_update_count = $_POST["enable_update_count"];
	}
	if (isset($_GET["update_country_code"])) {
		$update_country_code = $_GET["update_country_code"];
	} elseif (isset($_POST["update_country_code"])) {
		$update_country_code = $_POST["update_country_code"];
	}
	if (isset($_GET["update_vendor_lead_code"])) {
		$update_vendor_lead_code = $_GET["update_vendor_lead_code"];
	} elseif (isset($_POST["update_vendor_lead_code"])) {
		$update_vendor_lead_code = $_POST["update_vendor_lead_code"];
	}
	if (isset($_GET["update_source_id"])) {
		$update_source_id = $_GET["update_source_id"];
	} elseif (isset($_POST["update_source_id"])) {
		$update_source_id = $_POST["update_source_id"];
	}
	if (isset($_GET["update_owner"])) {
		$update_owner = $_GET["update_owner"];
	} elseif (isset($_POST["update_owner"])) {
		$update_owner = $_POST["update_owner"];
	}
	if (isset($_GET["update_state"])) {
		$update_state = $_GET["update_state"];
	} elseif (isset($_POST["update_state"])) {
		$update_state = $_POST["update_state"];
	}
	if (isset($_GET["update_entry_date"])) {
		$update_entry_date = $_GET["update_entry_date"];
	} elseif (isset($_POST["update_entry_date"])) {
		$update_entry_date = $_POST["update_entry_date"];
	}
	if (isset($_GET["update_entry_date_end"])) {
		$update_entry_date_end = $_GET["update_entry_date_end"];
	} elseif (isset($_POST["update_entry_date_end"])) {
		$update_entry_date_end = $_POST["update_entry_date_end"];
	}
	if (isset($_GET["update_entry_date_op"])) {
		$update_entry_date_op = $_GET["update_entry_date_op"];
	} elseif (isset($_POST["update_entry_date_op"])) {
		$update_entry_date_op = $_POST["update_entry_date_op"];
	}
	if (isset($_GET["update_modify_date"])) {
		$update_modify_date = $_GET["update_modify_date"];
	} elseif (isset($_POST["update_modify_date"])) {
		$update_modify_date = $_POST["update_modify_date"];
	}
	if (isset($_GET["update_modify_date_end"])) {
		$update_modify_date_end = $_GET["update_modify_date_end"];
	} elseif (isset($_POST["update_modify_date_end"])) {
		$update_modify_date_end = $_POST["update_modify_date_end"];
	}
	if (isset($_GET["update_modify_date_op"])) {
		$update_modify_date_op = $_GET["update_modify_date_op"];
	} elseif (isset($_POST["update_modify_date_op"])) {
		$update_modify_date_op = $_POST["update_modify_date_op"];
	}
	if (isset($_GET["update_security_phrase"])) {
		$update_security_phrase = $_GET["update_security_phrase"];
	} elseif (isset($_POST["update_security_phrase"])) {
		$update_security_phrase = $_POST["update_security_phrase"];
	}
	if (isset($_GET["update_list"])) {
		$update_list = $_GET["update_list"];
	} elseif (isset($_POST["update_list"])) {
		$update_list = $_POST["update_list"];
	}
	if (isset($_GET["update_from_status"])) {
		$update_from_status = $_GET["update_from_status"];
	} elseif (isset($_POST["update_from_status"])) {
		$update_from_status = $_POST["update_from_status"];
	}
	if (isset($_GET["update_to_status"])) {
		$update_to_status = $_GET["update_to_status"];
	} elseif (isset($_POST["update_to_status"])) {
		$update_to_status = $_POST["update_to_status"];
	}
	if (isset($_GET["update_count_op"])) {
		$update_count_op = $_GET["update_count_op"];
	} elseif (isset($_POST["update_count_op"])) {
		$update_count_op = $_POST["update_count_op"];
	}
	if (isset($_GET["update_count_num"])) {
		$update_count_num = $_GET["update_count_num"];
	} elseif (isset($_POST["update_count_num"])) {
		$update_count_num = $_POST["update_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_update_from_status") . " = $enable_update_from_status | " . _QXZ("enable_update_country_code") . " = $enable_update_country_code | " . _QXZ("enable_update_vendor_lead_code") . " = $enable_update_vendor_lead_code | " . _QXZ("enable_update_source_id") . " = $enable_update_source_id | " . _QXZ("enable_update_owner") . " = $enable_update_owner | " . _QXZ("enable_update_state") . " = $enable_update_state | " . _QXZ("enable_update_entry_date") . " = $enable_update_entry_date | " . _QXZ("enable_update_modify_date") . " = $enable_update_modify_date | " . _QXZ("enable_update_security_phrase") . " = $enable_update_security_phrase | " . _QXZ("enable_update_count") . " = $enable_update_count | " . _QXZ("update_country_code") . " = $update_country_code | " . _QXZ("update_vendor_lead_code") . " = $update_vendor_lead_code | " . _QXZ("update_source_id") . " = $update_source_id | " . _QXZ("update_owner") . " = $update_owner | " . _QXZ("update_state") . " = $update_state | " . _QXZ("update_entry_date") . " = $update_entry_date | " . _QXZ("update_entry_date_end") . " = $update_entry_date_end | " . _QXZ("update_entry_date_op") . " = $update_entry_date_op | " . _QXZ("update_modify_date") . " = $update_modify_date | " . _QXZ("update_modify_date_end") . " = $update_modify_date_end | " . _QXZ("update_modify_date_op") . " = $update_modify_date_op | " . _QXZ("update_security_phrase") . " = $update_security_phrase | " . _QXZ("update_list") . " = $update_list | " . _QXZ("update_to_status") . " = $ update_to_status | " . _QXZ("update_from_status") . " = $update_from_status | " . _QXZ("update_count_op") . " = $update_count_op | " . _QXZ("update_count_num") . " = $update_count_num</p>";
	}

	# filter out anything bad
	$enable_update_from_status = preg_replace('/[^a-zA-Z]/', '', $enable_update_from_status);
	$enable_update_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_update_country_code);
	$enable_update_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_update_vendor_lead_code);
	$enable_update_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_update_source_id);
	$enable_update_owner = preg_replace('/[^a-zA-Z]/', '', $enable_update_owner);
	$enable_update_state = preg_replace('/[^a-zA-Z]/', '', $enable_update_state);
	$enable_update_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_update_entry_date);
	$enable_update_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_update_modify_date);
	$enable_update_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_update_security_phrase);
	$enable_update_count = preg_replace('/[^a-zA-Z]/', '', $enable_update_count);
	$update_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $update_country_code);
	$update_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_vendor_lead_code);
	$update_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_source_id);
	$update_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_owner);
	$update_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_state);
	$update_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_entry_date);
	$update_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_entry_date_end);
	$update_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $update_entry_date_op);
	$update_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_modify_date);
	$update_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_modify_date_end);
	$update_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $update_modify_date_op);
	$update_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_security_phrase);
	$update_to_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_to_status);
	$update_from_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_from_status);
	$update_list = preg_replace('/[^0-9\|]/', '', $update_list);
	$update_count_num = preg_replace('/[^0-9]/', '', $update_count_num);
	$update_count_op = preg_replace('/[^<>=]/', '', $update_count_op);

	$update_count_op_phrase = "";
	if ($update_count_op == "<") {
		$update_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($update_count_op == "<=") {
		$update_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($update_count_op == ">") {
		$update_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($update_count_op == ">=") {
		$update_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the update_entry_date operation phrase
	$update_entry_date_op_phrase = "";
	$update_entry_operator_a = '>=';
	$update_entry_operator_b = '<=';
	if ($update_entry_date_op == "<") {
		$update_entry_operator_a = '>=';
		$update_entry_operator_b = '<';
		$update_entry_date_end = $update_entry_date;
		$update_entry_date = '0000-00-00 00:00:00';
		$update_entry_date_op_phrase = _QXZ("less than") . " $update_entry_date_end";
	} elseif ($update_entry_date_op == "<=") {
		$update_entry_date_end = $update_entry_date;
		$update_entry_date = '0000-00-00 00:00:00';
		$update_entry_date_op_phrase = _QXZ("less than or equal to") . " $update_entry_date_end";
	} elseif ($update_entry_date_op == ">") {
		$update_entry_operator_a = '>';
		$update_entry_operator_b = '<';
		$update_entry_date_end = '2100-00-00 00:00:00';
		$update_entry_date_op_phrase = _QXZ("greater than") . " $update_entry_date";
	} elseif ($update_entry_date_op == ">=") {
		$update_entry_date_end = '2100-00-00 00:00:00';
		$update_entry_date_op_phrase = _QXZ("greater than or equal to") . " $update_entry_date";
	} elseif ($update_entry_date_op == "range") {
		$update_entry_date_op_phrase = _QXZ("range") . " $update_entry_date - $update_entry_date_end";
	} elseif ($update_entry_date_op == "=") {
		$update_entry_date_end = $update_entry_date;
		$update_entry_date_op_phrase = _QXZ("equal to") . " $update_entry_date";
	}

	# build the update_modify_date operation phrase
	$update_modify_date_op_phrase = "";
	$update_modify_operator_a = '>=';
	$update_modify_operator_b = '<=';
	if ($update_modify_date_op == "<") {
		$update_modify_operator_a = '>=';
		$update_modify_operator_b = '<';
		$update_modify_date_end = $update_modify_date;
		$update_modify_date = '0000-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("less than") . " $update_modify_date_end";
	} elseif ($update_modify_date_op == "<=") {
		$update_modify_date_end = $update_modify_date;
		$update_modify_date = '0000-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("less than or equal to") . " $update_modify_date_end";
	} elseif ($update_modify_date_op == ">") {
		$update_modify_operator_a = '>';
		$update_modify_operator_b = '<';
		$update_modify_date_end = '2100-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("greater than") . " $update_modify_date";
	} elseif ($update_modify_date_op == ">=") {
		$update_modify_date_end = '2100-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("greater than or equal to") . " $update_modify_date";
	} elseif ($update_modify_date_op == "range") {
		$update_modify_date_op_phrase = _QXZ("range") . " $update_modify_date - $update_modify_date_end";
	} elseif ($update_modify_date_op == "=") {
		$update_modify_date_end = $update_modify_date;
		$update_modify_date_op_phrase = _QXZ("equal to") . " $update_modify_date";
	}

	if (strlen($update_entry_date) == 10) {
		$update_entry_date .= " 00:00:00";
	}
	if (strlen($update_entry_date_end) == 10) {
		$update_entry_date_end .= " 23:59:59";
	}
	if (strlen($update_modify_date) == 10) {
		$update_modify_date .= " 00:00:00";
	}
	if (strlen($update_modify_date_end) == 10) {
		$update_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_update_from_status") . " = $enable_update_from_status | " . _QXZ("enable_update_country_code") . " = $enable_update_country_code | " . _QXZ("enable_update_vendor_lead_code") . " = $enable_update_vendor_lead_code | " . _QXZ("enable_update_source_id") . " = $enable_update_source_id | " . _QXZ("enable_update_owner") . " = $enable_update_owner | " . _QXZ("enable_update_state") . " = $enable_update_state | " . _QXZ("enable_update_entry_date") . " = $enable_update_entry_date | " . _QXZ("enable_update_modify_date") . " = $enable_update_modify_date | " . _QXZ("enable_update_security_phrase") . " = $enable_update_security_phrase | " . _QXZ("enable_update_count") . " = $enable_update_count | " . _QXZ("update_country_code") . " = $update_country_code | " . _QXZ("update_vendor_lead_code") . " = $update_vendor_lead_code | " . _QXZ("update_source_id") . " = $update_source_id | " . _QXZ("update_owner") . " = $update_owner | " . _QXZ("update_state") . " = $update_state | " . _QXZ("update_entry_date") . " = $update_entry_date | " . _QXZ("update_entry_date_end") . " = $update_entry_date_end | " . _QXZ("update_entry_date_op") . " = $update_entry_date_op | " . _QXZ("update_modify_date") . " = $update_modify_date | " . _QXZ("update_modify_date_end") . " = $update_modify_date_end | " . _QXZ("update_modify_date_op") . " = $update_modify_date_op | " . _QXZ("update_security_phrase") . " = $update_security_phrase | " . _QXZ("update_list") . " = $update_list | " . _QXZ("update_to_status") . " = $ update_to_status | " . _QXZ("update_from_status") . " = $update_from_status | " . _QXZ("update_count_op") . " = $update_count_op | " . _QXZ("update_count_num") . " = $update_count_num</p>";
	}

	# make sure the required fields are set
	if ($update_to_status == '') {
		missing_required_field('To Status');
	}
	if ($update_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$update_parm = "";
	if (($enable_update_from_status == "enabled") && ($update_from_status != '')) {
		if ($update_from_status == '---BLANK---') {
			$update_from_status = '';
		}
		$sql_where = $sql_where . " and status like '$update_from_status' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $update_from_status<br />";
		if ($update_from_status == '') {
			$update_from_status = '---BLANK---';
		}
	} elseif ($enable_update_from_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_update_country_code == "enabled") && ($update_country_code != '')) {
		if ($update_country_code == '---BLANK---') {
			$update_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$update_country_code\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $update_country_code<br />";
		if ($update_country_code == '') {
			$update_country_code = '---BLANK---';
		}
	} elseif ($enable_update_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_update_vendor_lead_code == "enabled") && ($update_vendor_lead_code != '')) {
		if ($update_vendor_lead_code == '---BLANK---') {
			$update_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$update_vendor_lead_code\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $update_vendor_lead_code<br />";
		if ($update_vendor_lead_code == '') {
			$update_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_update_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_update_source_id == "enabled") && ($update_source_id != '')) {
		if ($update_source_id == '---BLANK---') {
			$update_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$update_source_id\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $update_source_id<br />";
		if ($update_source_id == '') {
			$update_source_id = '---BLANK---';
		}
	} elseif ($enable_update_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_update_owner == "enabled") && ($update_owner != '')) {
		if ($update_owner == '---BLANK---') {
			$update_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$update_owner\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $update_owner<br />";
		if ($update_owner == '') {
			$update_owner = '---BLANK---';
		}
	} elseif ($enable_update_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_update_state == "enabled") && ($update_state != '')) {
		if ($update_state == '---BLANK---') {
			$update_state = '';
		}
		$sql_where = $sql_where . " and state like \"$update_state\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $update_state<br />";
		if ($update_state == '') {
			$update_state = '---BLANK---';
		}
	} elseif ($enable_update_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_update_security_phrase == "enabled") && ($update_security_phrase != '')) {
		if ($update_security_phrase == '---BLANK---') {
			$update_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$update_security_phrase\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $update_security_phrase<br />";
		if ($update_security_phrase == '') {
			$update_security_phrase = '---BLANK---';
		}
	} elseif ($enable_update_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_update_entry_date == "enabled") && ($update_entry_date != '')) {
		if ($update_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $update_entry_operator_a '$update_entry_date' and entry_date $update_entry_operator_b '$update_entry_date_end' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $update_entry_date_op_phrase<br />";
		}
	} elseif ($enable_update_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_update_modify_date == "enabled") && ($update_modify_date != '')) {
		if ($update_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $update_modify_operator_a '$update_modify_date' and modify_date $update_modify_operator_b '$update_modify_date_end' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $update_modify_date_op_phrase<br />";
		}
	} elseif ($enable_update_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_update_count == "enabled") && ($update_count_op != '') && ($update_count_num != '')) {
		if ($update_count_op == '---BLANK---') {
			$update_count_op = '';
		}
		if ($update_count_num == '---BLANK---') {
			$update_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $update_count_op $update_count_num";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $update_count_op_phrase $update_count_num<br />";
		if ($update_count_op == '') {
			$update_count_op = '---BLANK---';
		}
		if ($update_count_num == '') {
			$update_count_num = '---BLANK---';
		}
	} elseif ($enable_update_count == "enabled") {
		blank_field('Move Count');
	}

	# get the number of leads this action will move
	$update_lead_count = 0;
	$update_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id IN('" . implode("','", $update_list) . "') $sql_where";
	if ($DB) {
		echo "|$update_lead_count_stmt|\n";
	}
	$update_lead_count_rslt = mysql_to_mysqli($update_lead_count_stmt, $link);
	$update_lead_count_row = mysqli_fetch_row($update_lead_count_rslt);
	$update_lead_count = $update_lead_count_row[0];

	echo "<p>" . _QXZ("You are about to update") . " <B>$update_lead_count</B> " . _QXZ("leads in list") . " " . implode(",", $update_list) . " " . _QXZ("to the status") . " $update_to_status " . _QXZ("with the following parameters") . ":<br /><br />$update_parm<br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_update_from_status value='$enable_update_from_status'>\n";
	echo "<input type=hidden name=enable_update_country_code value='$enable_update_country_code'>\n";
	echo "<input type=hidden name=enable_update_vendor_lead_code value='$enable_update_vendor_lead_code'>\n";
	echo "<input type=hidden name=enable_update_source_id value='$enable_update_source_id'>\n";
	echo "<input type=hidden name=enable_update_owner value='$enable_update_owner'>\n";
	echo "<input type=hidden name=enable_update_state value='$enable_update_state'>\n";
	echo "<input type=hidden name=enable_update_entry_date value='$enable_update_entry_date'>\n";
	echo "<input type=hidden name=enable_update_modify_date value='$enable_update_modify_date'>\n";
	echo "<input type=hidden name=enable_update_security_phrase value='$enable_update_security_phrase'>\n";
	echo "<input type=hidden name=enable_update_count value='$enable_update_count'>\n";
	echo "<input type=hidden name=update_country_code value=\"$update_country_code\">\n";
	echo "<input type=hidden name=update_vendor_lead_code value=\"$update_vendor_lead_code\">\n";
	echo "<input type=hidden name=update_source_id value=\"$update_source_id\">\n";
	echo "<input type=hidden name=update_owner value=\"$update_owner\">\n";
	echo "<input type=hidden name=update_state value=\"$update_state\">\n";
	echo "<input type=hidden name=update_entry_date value=\"$update_entry_date\">\n";
	echo "<input type=hidden name=update_entry_date_end value=\"$update_entry_date_end\">\n";
	echo "<input type=hidden name=update_entry_date_op value=\"$update_entry_date_op\">\n";
	echo "<input type=hidden name=update_modify_date value=\"$update_modify_date\">\n";
	echo "<input type=hidden name=update_modify_date_end value=\"$update_modify_date_end\">\n";
	echo "<input type=hidden name=update_modify_date_op value=\"$update_modify_date_op\">\n";
	echo "<input type=hidden name=update_security_phrase value=\"$update_security_phrase\">\n";
	echo "<input type=hidden name=update_list value='" . implode("|", $update_list) . "'>\n";
	echo "<input type=hidden name=update_to_status value=\"$update_to_status\">\n";
	echo "<input type=hidden name=update_from_status value=\"$update_from_status\">\n";
	echo "<input type=hidden name=update_count_op value=\"$update_count_op\">\n";
	echo "<input type=hidden name=update_count_num value=\"$update_count_num\">\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_update value='" . _QXZ("confirm") . "'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	echo "</body>\n</html>\n";
}

# actually do the update
if ($confirm_update == _QXZ("confirm")) {
	# get the variables
	$enable_update_from_status = "";
	$enable_update_country_code = "";
	$enable_update_vendor_lead_code = "";
	$enable_update_source_id = "";
	$enable_update_owner = "";
	$enable_update_state = "";
	$enable_update_entry_date = "";
	$enable_update_modify_date = "";
	$enable_update_security_phrase = "";
	$enable_update_count = "";
	$update_country_code = "";
	$update_vendor_lead_code = "";
	$update_source_id = "";
	$update_owner = "";
	$update_state = "";
	$update_entry_date = "";
	$update_entry_date_end = "";
	$update_entry_date_op = "";
	$update_modify_date = "";
	$update_modify_date_end = "";
	$update_modify_date_op = "";
	$update_security_phrase = "";
	$update_list = "";
	$update_to_status = "";
	$update_from_status = "";
	$update_count_op = "";
	$update_count_num = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_update_from_status"])) {
		$enable_update_from_status = $_GET["enable_update_from_status"];
	} elseif (isset($_POST["enable_update_from_status"])) {
		$enable_update_from_status = $_POST["enable_update_from_status"];
	}
	if (isset($_GET["enable_update_country_code"])) {
		$enable_update_country_code = $_GET["enable_update_country_code"];
	} elseif (isset($_POST["enable_update_country_code"])) {
		$enable_update_country_code = $_POST["enable_update_country_code"];
	}
	if (isset($_GET["enable_update_vendor_lead_code"])) {
		$enable_update_vendor_lead_code = $_GET["enable_update_vendor_lead_code"];
	} elseif (isset($_POST["enable_update_vendor_lead_code"])) {
		$enable_update_vendor_lead_code = $_POST["enable_update_vendor_lead_code"];
	}
	if (isset($_GET["enable_update_source_id"])) {
		$enable_update_source_id = $_GET["enable_update_source_id"];
	} elseif (isset($_POST["enable_update_source_id"])) {
		$enable_update_source_id = $_POST["enable_update_source_id"];
	}
	if (isset($_GET["enable_update_owner"])) {
		$enable_update_owner = $_GET["enable_update_owner"];
	} elseif (isset($_POST["enable_update_owner"])) {
		$enable_update_owner = $_POST["enable_update_owner"];
	}
	if (isset($_GET["enable_update_state"])) {
		$enable_update_state = $_GET["enable_update_state"];
	} elseif (isset($_POST["enable_update_state"])) {
		$enable_update_state = $_POST["enable_update_state"];
	}
	if (isset($_GET["enable_update_entry_date"])) {
		$enable_update_entry_date = $_GET["enable_update_entry_date"];
	} elseif (isset($_POST["enable_update_entry_date"])) {
		$enable_update_entry_date = $_POST["enable_update_entry_date"];
	}
	if (isset($_GET["enable_update_modify_date"])) {
		$enable_update_modify_date = $_GET["enable_update_modify_date"];
	} elseif (isset($_POST["enable_update_modify_date"])) {
		$enable_update_modify_date = $_POST["enable_update_modify_date"];
	}
	if (isset($_GET["enable_update_security_phrase"])) {
		$enable_update_security_phrase = $_GET["enable_update_security_phrase"];
	} elseif (isset($_POST["enable_update_security_phrase"])) {
		$enable_update_security_phrase = $_POST["enable_update_security_phrase"];
	}
	if (isset($_GET["enable_update_count"])) {
		$enable_update_count = $_GET["enable_update_count"];
	} elseif (isset($_POST["enable_update_count"])) {
		$enable_update_count = $_POST["enable_update_count"];
	}
	if (isset($_GET["update_country_code"])) {
		$update_country_code = $_GET["update_country_code"];
	} elseif (isset($_POST["update_country_code"])) {
		$update_country_code = $_POST["update_country_code"];
	}
	if (isset($_GET["update_vendor_lead_code"])) {
		$update_vendor_lead_code = $_GET["update_vendor_lead_code"];
	} elseif (isset($_POST["update_vendor_lead_code"])) {
		$update_vendor_lead_code = $_POST["update_vendor_lead_code"];
	}
	if (isset($_GET["update_source_id"])) {
		$update_source_id = $_GET["update_source_id"];
	} elseif (isset($_POST["update_source_id"])) {
		$update_source_id = $_POST["update_source_id"];
	}
	if (isset($_GET["update_owner"])) {
		$update_owner = $_GET["update_owner"];
	} elseif (isset($_POST["update_owner"])) {
		$update_owner = $_POST["update_owner"];
	}
	if (isset($_GET["update_state"])) {
		$update_state = $_GET["update_state"];
	} elseif (isset($_POST["update_state"])) {
		$update_state = $_POST["update_state"];
	}
	if (isset($_GET["update_entry_date"])) {
		$update_entry_date = $_GET["update_entry_date"];
	} elseif (isset($_POST["update_entry_date"])) {
		$update_entry_date = $_POST["update_entry_date"];
	}
	if (isset($_GET["update_entry_date_end"])) {
		$update_entry_date_end = $_GET["update_entry_date_end"];
	} elseif (isset($_POST["update_entry_date_end"])) {
		$update_entry_date_end = $_POST["update_entry_date_end"];
	}
	if (isset($_GET["update_entry_date_op"])) {
		$update_entry_date_op = $_GET["update_entry_date_op"];
	} elseif (isset($_POST["update_entry_date_op"])) {
		$update_entry_date_op = $_POST["update_entry_date_op"];
	}
	if (isset($_GET["update_modify_date"])) {
		$update_modify_date = $_GET["update_modify_date"];
	} elseif (isset($_POST["update_modify_date"])) {
		$update_modify_date = $_POST["update_modify_date"];
	}
	if (isset($_GET["update_modify_date_end"])) {
		$update_modify_date_end = $_GET["update_modify_date_end"];
	} elseif (isset($_POST["update_modify_date_end"])) {
		$update_modify_date_end = $_POST["update_modify_date_end"];
	}
	if (isset($_GET["update_modify_date_op"])) {
		$update_modify_date_op = $_GET["update_modify_date_op"];
	} elseif (isset($_POST["update_modify_date_op"])) {
		$update_modify_date_op = $_POST["update_modify_date_op"];
	}
	if (isset($_GET["update_security_phrase"])) {
		$update_security_phrase = $_GET["update_security_phrase"];
	} elseif (isset($_POST["update_security_phrase"])) {
		$update_security_phrase = $_POST["update_security_phrase"];
	}
	if (isset($_GET["update_list"])) {
		$update_list = $_GET["update_list"];
	} elseif (isset($_POST["update_list"])) {
		$update_list = $_POST["update_list"];
	}
	if (isset($_GET["update_from_status"])) {
		$update_from_status = $_GET["update_from_status"];
	} elseif (isset($_POST["update_from_status"])) {
		$update_from_status = $_POST["update_from_status"];
	}
	if (isset($_GET["update_to_status"])) {
		$update_to_status = $_GET["update_to_status"];
	} elseif (isset($_POST["update_to_status"])) {
		$update_to_status = $_POST["update_to_status"];
	}
	if (isset($_GET["update_count_op"])) {
		$update_count_op = $_GET["update_count_op"];
	} elseif (isset($_POST["update_count_op"])) {
		$update_count_op = $_POST["update_count_op"];
	}
	if (isset($_GET["update_count_num"])) {
		$update_count_num = $_GET["update_count_num"];
	} elseif (isset($_POST["update_count_num"])) {
		$update_count_num = $_POST["update_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_update_from_status") . " = $enable_update_from_status | " . _QXZ("enable_update_country_code") . " = $enable_update_country_code | " . _QXZ("enable_update_vendor_lead_code") . " = $enable_update_vendor_lead_code | " . _QXZ("enable_update_source_id") . " = $enable_update_source_id | " . _QXZ("enable_update_owner") . " = $enable_update_owner | " . _QXZ("enable_update_state") . " = $enable_update_state | " . _QXZ("enable_update_entry_date") . " = $enable_update_entry_date | " . _QXZ("enable_update_modify_date") . " = $enable_update_modify_date | " . _QXZ("enable_update_security_phrase") . " = $enable_update_security_phrase | " . _QXZ("enable_update_count") . " = $enable_update_count | " . _QXZ("update_country_code") . " = $update_country_code | " . _QXZ("update_vendor_lead_code") . " = $update_vendor_lead_code | " . _QXZ("update_source_id") . " = $update_source_id | " . _QXZ("update_owner") . " = $update_owner | " . _QXZ("update_state") . " = $update_state | " . _QXZ("update_entry_date") . " = $update_entry_date | " . _QXZ("update_entry_date_end") . " = $update_entry_date_end | " . _QXZ("update_entry_date_op") . " = $update_entry_date_op | " . _QXZ("update_modify_date") . " = $update_modify_date | " . _QXZ("update_modify_date_end") . " = $update_modify_date_end | " . _QXZ("update_modify_date_op") . " = $update_modify_date_op | " . _QXZ("update_security_phrase") . " = $update_security_phrase | " . _QXZ("update_list") . " = $update_list | " . _QXZ("update_to_status") . " = $ update_to_status | " . _QXZ("update_from_status") . " = $update_from_status | " . _QXZ("update_count_op") . " = $update_count_op | " . _QXZ("update_count_num") . " = $update_count_num</p>";
	}

	# filter out anything bad
	$enable_update_from_status = preg_replace('/[^a-zA-Z]/', '', $enable_update_from_status);
	$enable_update_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_update_country_code);
	$enable_update_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_update_vendor_lead_code);
	$enable_update_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_update_source_id);
	$enable_update_owner = preg_replace('/[^a-zA-Z]/', '', $enable_update_owner);
	$enable_update_state = preg_replace('/[^a-zA-Z]/', '', $enable_update_state);
	$enable_update_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_update_entry_date);
	$enable_update_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_update_modify_date);
	$enable_update_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_update_security_phrase);
	$enable_update_count = preg_replace('/[^a-zA-Z]/', '', $enable_update_count);
	$update_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $update_country_code);
	$update_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_vendor_lead_code);
	$update_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_source_id);
	$update_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_owner);
	$update_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_state);
	$update_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_entry_date);
	$update_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_entry_date_end);
	$update_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $update_entry_date_op);
	$update_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_modify_date);
	$update_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $update_modify_date_end);
	$update_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $update_modify_date_op);
	$update_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $update_security_phrase);
	$update_to_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_to_status);
	$update_from_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $update_from_status);
	$update_list = preg_replace('/[^0-9\|]/', '', $update_list);
	$update_count_num = preg_replace('/[^0-9]/', '', $update_count_num);
	$update_count_op = preg_replace('/[^<>=]/', '', $update_count_op);

	$update_count_op_phrase = "";
	if ($update_count_op == "<") {
		$update_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($update_count_op == "<=") {
		$update_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($update_count_op == ">") {
		$update_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($update_count_op == ">=") {
		$update_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the update_entry_date operation phrase
	$update_entry_date_op_phrase = "";
	$update_entry_operator_a = '>=';
	$update_entry_operator_b = '<=';
	if ($update_entry_date_op == "<") {
		$update_entry_operator_a = '>=';
		$update_entry_operator_b = '<';
		$update_entry_date_op_phrase = _QXZ("less than") . " $update_entry_date_end";
	} elseif ($update_entry_date_op == "<=") {
		$update_entry_date_op_phrase = _QXZ("less than or equal to") . " $update_entry_date_end";
	} elseif ($update_entry_date_op == ">") {
		$update_entry_operator_a = '>';
		$update_entry_operator_b = '<';
		$update_entry_date_op_phrase = _QXZ("greater than") . " $update_entry_date";
	} elseif ($update_entry_date_op == ">=") {
		$update_entry_date_op_phrase = _QXZ("greater than or equal to") . " $update_entry_date";
	} elseif ($update_entry_date_op == "range") {
		$update_entry_date_op_phrase = _QXZ("range") . " $update_entry_date - $update_entry_date_end";
	} elseif ($update_entry_date_op == "=") {
		$update_entry_date_op_phrase = _QXZ("equal to") . " $update_entry_date_end";
	}

	# build the update_modify_date operation phrase
	$update_modify_date_op_phrase = "";
	$update_modify_operator_a = '>=';
	$update_modify_operator_b = '<=';
	if ($update_modify_date_op == "<") {
		$update_modify_operator_a = '>=';
		$update_modify_operator_b = '<';
		$update_modify_date_end = $update_modify_date;
		$update_modify_date = '0000-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("less than") . " $update_modify_date_end";
	} elseif ($update_modify_date_op == "<=") {
		$update_modify_date_end = $update_modify_date;
		$update_modify_date = '0000-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("less than or equal to") . " $update_modify_date_end";
	} elseif ($update_modify_date_op == ">") {
		$update_modify_operator_a = '>';
		$update_modify_operator_b = '<';
		$update_modify_date_end = '2100-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("greater than") . " $update_modify_date";
	} elseif ($update_modify_date_op == ">=") {
		$update_modify_date_end = '2100-00-00 00:00:00';
		$update_modify_date_op_phrase = _QXZ("greater than or equal to") . " $update_modify_date";
	} elseif ($update_modify_date_op == "range") {
		$update_modify_date_op_phrase = _QXZ("range") . " $update_modify_date - $update_modify_date_end";
	} elseif ($update_modify_date_op == "=") {
		$update_modify_date_end = $update_modify_date;
		$update_modify_date_op_phrase = _QXZ("equal to") . " $update_modify_date";
	}

	if (strlen($update_entry_date) == 10) {
		$update_entry_date .= " 00:00:00";
	}
	if (strlen($update_entry_date_end) == 10) {
		$update_entry_date_end .= " 23:59:59";
	}
	if (strlen($update_modify_date) == 10) {
		$update_modify_date .= " 00:00:00";
	}
	if (strlen($update_modify_date_end) == 10) {
		$update_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_update_from_status") . " = $enable_update_from_status | " . _QXZ("enable_update_country_code") . " = $enable_update_country_code | " . _QXZ("enable_update_vendor_lead_code") . " = $enable_update_vendor_lead_code | " . _QXZ("enable_update_source_id") . " = $enable_update_source_id | " . _QXZ("enable_update_owner") . " = $enable_update_owner | " . _QXZ("enable_update_state") . " = $enable_update_state | " . _QXZ("enable_update_entry_date") . " = $enable_update_entry_date | " . _QXZ("enable_update_modify_date") . " = $enable_update_modify_date | " . _QXZ("enable_update_security_phrase") . " = $enable_update_security_phrase | " . _QXZ("enable_update_count") . " = $enable_update_count | " . _QXZ("update_country_code") . " = $update_country_code | " . _QXZ("update_vendor_lead_code") . " = $update_vendor_lead_code | " . _QXZ("update_source_id") . " = $update_source_id | " . _QXZ("update_owner") . " = $update_owner | " . _QXZ("update_entry_date") . " = $update_entry_date | " . _QXZ("update_entry_date_end") . " = $update_entry_date_end | " . _QXZ("update_entry_date_op") . " = $update_entry_date_op | " . _QXZ("update_modify_date") . " = $update_modify_date | " . _QXZ("update_modify_date_end") . " = $update_modify_date_end | " . _QXZ("update_modify_date_op") . " = $update_modify_date_op | " . _QXZ("update_security_phrase") . " = $update_security_phrase | " . _QXZ("update_list") . " = $update_list | " . _QXZ("update_to_status") . " = $ update_to_status | " . _QXZ("update_from_status") . " = $update_from_status | " . _QXZ("update_count_op") . " = $update_count_op | " . _QXZ("update_count_num") . " = $update_count_num</p>";
	}

	# make sure the required fields are set
	if ($update_to_status == '') {
		missing_required_field('To Status');
	}
	if ($update_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$update_parm = "";
	if (($enable_update_from_status == "enabled") && ($update_from_status != '')) {
		if ($update_from_status == '---BLANK---') {
			$update_from_status = '';
		}
		$sql_where = $sql_where . " and status like '$update_from_status' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $update_from_status<br />";
		if ($update_from_status == '') {
			$update_from_status = '---BLANK---';
		}
	} elseif ($enable_update_from_status == "enabled") {
		blank_field('Status', true);
	}
	if (($enable_update_country_code == "enabled") && ($update_country_code != '')) {
		if ($update_country_code == '---BLANK---') {
			$update_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$update_country_code\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $update_country_code<br />";
		if ($update_country_code == '') {
			$update_country_code = '---BLANK---';
		}
	} elseif ($enable_update_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_update_vendor_lead_code == "enabled") && ($update_vendor_lead_code != '')) {
		if ($update_vendor_lead_code == '---BLANK---') {
			$update_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$update_vendor_lead_code\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $update_vendor_lead_code<br />";
		if ($update_vendor_lead_code == '') {
			$update_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_update_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_update_source_id == "enabled") && ($update_source_id != '')) {
		if ($update_source_id == '---BLANK---') {
			$update_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$update_source_id\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id is like") . " $update_source_id<br />";
		if ($update_source_id == '') {
			$update_source_id = '---BLANK---';
		}
	} elseif ($enable_update_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_update_owner == "enabled") && ($update_owner != '')) {
		if ($update_owner == '---BLANK---') {
			$update_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$update_owner\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $update_owner<br />";
		if ($update_owner == '') {
			$update_owner = '---BLANK---';
		}
	} elseif ($enable_update_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_update_state == "enabled") && ($update_state != '')) {
		if ($update_state == '---BLANK---') {
			$update_state = '';
		}
		$sql_where = $sql_where . " and state like \"$update_state\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $update_state<br />";
		if ($update_state == '') {
			$update_state = '---BLANK---';
		}
	} elseif ($enable_update_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_update_security_phrase == "enabled") && ($update_security_phrase != '')) {
		if ($update_security_phrase == '---BLANK---') {
			$update_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$update_security_phrase\" ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $update_security_phrase<br />";
		if ($update_security_phrase == '') {
			$update_security_phrase = '---BLANK---';
		}
	} elseif ($enable_update_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_update_entry_date == "enabled") && ($update_entry_date != '')) {
		if ($update_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $update_entry_operator_a '$update_entry_date' and entry_date $update_entry_operator_b '$update_entry_date_end' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $update_entry_date_op_phrase<br />";
		}
	} elseif ($enable_update_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_update_modify_date == "enabled") && ($update_modify_date != '')) {
		if ($update_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $update_modify_operator_a '$update_modify_date' and modify_date $update_modify_operator_b '$update_modify_date_end' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $update_modify_date_op_phrase<br />";
		}
	} elseif ($enable_update_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_update_count == "enabled") && ($update_count_op != '') && ($update_count_num != '')) {
		if ($update_count_op == '---BLANK---') {
			$update_count_op = '';
		}
		if ($update_count_num == '---BLANK---') {
			$update_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $update_count_op $update_count_num";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $update_count_op_phrase $update_count_num<br />";
		if ($update_count_op == '') {
			$update_count_op = '---BLANK---';
		}
		if ($update_count_num == '') {
			$update_count_num = '---BLANK---';
		}
	} elseif ($enable_update_count == "enabled") {
		blank_field('Move Count', true);
	}

	$update_list_array = explode("|", $update_list);
	$update_lead_stmt = "UPDATE vicidial_list SET status = '$update_to_status' WHERE list_id IN('" . implode("', '", $update_list_array) . "') $sql_where";
	if ($DB) {
		echo "|$update_lead_stmt|\n";
	}
	$update_lead_rslt = mysql_to_mysqli($update_lead_stmt, $link);
	$update_lead_count = mysqli_affected_rows($link);

	$update_sentence = "<B>$update_lead_count</B> " . _QXZ("leads in list") . " $update_list " . _QXZ("had their status changed to") . " $update_to_status " . _QXZ("with the following parameters") . ":<br /><br />$update_parm";

	$SQL_log = "$update_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$update_list', event_code='ADMIN UPDATE LEADS', event_sql=\"$SQL_log\", event_notes=\"$update_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$update_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
##### END update process #####


##### BEGIN delete process #####
# delete confirmation page
if (($delete_submit == _QXZ("delete")) && ($delete_lists > 0)) {
	# get the variables
	$enable_delete_lead_id = "";
	$enable_delete_country_code = "";
	$enable_delete_vendor_lead_code = "";
	$enable_delete_source_id = "";
	$enable_delete_owner = "";
	$enable_delete_state = "";
	$enable_delete_entry_date = "";
	$enable_delete_modify_date = "";
	$enable_delete_security_phrase = "";
	$enable_delete_count = "";
	$delete_country_code = "";
	$delete_vendor_lead_code = "";
	$delete_source_id = "";
	$delete_owner = "";
	$delete_state = "";
	$delete_entry_date = "";
	$delete_entry_date_end = "";
	$delete_entry_date_op = "";
	$delete_modify_date = "";
	$delete_modify_date_end = "";
	$delete_modify_date_op = "";
	$delete_security_phrase = "";
	$delete_list = "";
	$delete_status = "";
	$delete_count_op = "";
	$delete_count_num = "";
	$delete_lead_id = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_delete_lead_id"])) {
		$enable_delete_lead_id = $_GET["enable_delete_lead_id"];
	} elseif (isset($_POST["enable_delete_lead_id"])) {
		$enable_delete_lead_id = $_POST["enable_delete_lead_id"];
	}
	if (isset($_GET["enable_delete_country_code"])) {
		$enable_delete_country_code = $_GET["enable_delete_country_code"];
	} elseif (isset($_POST["enable_delete_country_code"])) {
		$enable_delete_country_code = $_POST["enable_delete_country_code"];
	}
	if (isset($_GET["enable_delete_vendor_lead_code"])) {
		$enable_delete_vendor_lead_code = $_GET["enable_delete_vendor_lead_code"];
	} elseif (isset($_POST["enable_delete_vendor_lead_code"])) {
		$enable_delete_vendor_lead_code = $_POST["enable_delete_vendor_lead_code"];
	}
	if (isset($_GET["enable_delete_source_id"])) {
		$enable_delete_source_id = $_GET["enable_delete_source_id"];
	} elseif (isset($_POST["enable_delete_source_id"])) {
		$enable_delete_source_id = $_POST["enable_delete_source_id"];
	}
	if (isset($_GET["enable_delete_owner"])) {
		$enable_delete_owner = $_GET["enable_delete_owner"];
	} elseif (isset($_POST["enable_delete_owner"])) {
		$enable_delete_owner = $_POST["enable_delete_owner"];
	}
	if (isset($_GET["enable_delete_state"])) {
		$enable_delete_state = $_GET["enable_delete_state"];
	} elseif (isset($_POST["enable_delete_state"])) {
		$enable_delete_state = $_POST["enable_delete_state"];
	}
	if (isset($_GET["enable_delete_entry_date"])) {
		$enable_delete_entry_date = $_GET["enable_delete_entry_date"];
	} elseif (isset($_POST["enable_delete_entry_date"])) {
		$enable_delete_entry_date = $_POST["enable_delete_entry_date"];
	}
	if (isset($_GET["enable_delete_modify_date"])) {
		$enable_delete_modify_date = $_GET["enable_delete_modify_date"];
	} elseif (isset($_POST["enable_delete_modify_date"])) {
		$enable_delete_modify_date = $_POST["enable_delete_modify_date"];
	}
	if (isset($_GET["enable_delete_security_phrase"])) {
		$enable_delete_security_phrase = $_GET["enable_delete_security_phrase"];
	} elseif (isset($_POST["enable_delete_security_phrase"])) {
		$enable_delete_security_phrase = $_POST["enable_delete_security_phrase"];
	}
	if (isset($_GET["enable_delete_count"])) {
		$enable_delete_count = $_GET["enable_delete_count"];
	} elseif (isset($_POST["enable_delete_count"])) {
		$enable_delete_count = $_POST["enable_delete_count"];
	}
	if (isset($_GET["delete_country_code"])) {
		$delete_country_code = $_GET["delete_country_code"];
	} elseif (isset($_POST["delete_country_code"])) {
		$delete_country_code = $_POST["delete_country_code"];
	}
	if (isset($_GET["delete_vendor_lead_code"])) {
		$delete_vendor_lead_code = $_GET["delete_vendor_lead_code"];
	} elseif (isset($_POST["delete_vendor_lead_code"])) {
		$delete_vendor_lead_code = $_POST["delete_vendor_lead_code"];
	}
	if (isset($_GET["delete_source_id"])) {
		$delete_source_id = $_GET["delete_source_id"];
	} elseif (isset($_POST["delete_source_id"])) {
		$delete_source_id = $_POST["delete_source_id"];
	}
	if (isset($_GET["delete_owner"])) {
		$delete_owner = $_GET["delete_owner"];
	} elseif (isset($_POST["delete_owner"])) {
		$delete_owner = $_POST["delete_owner"];
	}
	if (isset($_GET["delete_state"])) {
		$delete_state = $_GET["delete_state"];
	} elseif (isset($_POST["delete_state"])) {
		$delete_state = $_POST["delete_state"];
	}
	if (isset($_GET["delete_entry_date"])) {
		$delete_entry_date = $_GET["delete_entry_date"];
	} elseif (isset($_POST["delete_entry_date"])) {
		$delete_entry_date = $_POST["delete_entry_date"];
	}
	if (isset($_GET["delete_entry_date_end"])) {
		$delete_entry_date_end = $_GET["delete_entry_date_end"];
	} elseif (isset($_POST["delete_entry_date_end"])) {
		$delete_entry_date_end = $_POST["delete_entry_date_end"];
	}
	if (isset($_GET["delete_entry_date_op"])) {
		$delete_entry_date_op = $_GET["delete_entry_date_op"];
	} elseif (isset($_POST["delete_entry_date_op"])) {
		$delete_entry_date_op = $_POST["delete_entry_date_op"];
	}
	if (isset($_GET["delete_modify_date"])) {
		$delete_modify_date = $_GET["delete_modify_date"];
	} elseif (isset($_POST["delete_modify_date"])) {
		$delete_modify_date = $_POST["delete_modify_date"];
	}
	if (isset($_GET["delete_modify_date_end"])) {
		$delete_modify_date_end = $_GET["delete_modify_date_end"];
	} elseif (isset($_POST["delete_modify_date_end"])) {
		$delete_modify_date_end = $_POST["delete_modify_date_end"];
	}
	if (isset($_GET["delete_modify_date_op"])) {
		$delete_modify_date_op = $_GET["delete_modify_date_op"];
	} elseif (isset($_POST["delete_modify_date_op"])) {
		$delete_modify_date_op = $_POST["delete_modify_date_op"];
	}
	if (isset($_GET["delete_security_phrase"])) {
		$delete_security_phrase = $_GET["delete_security_phrase"];
	} elseif (isset($_POST["delete_security_phrase"])) {
		$delete_security_phrase = $_POST["delete_security_phrase"];
	}
	if (isset($_GET["delete_list"])) {
		$delete_list = $_GET["delete_list"];
	} elseif (isset($_POST["delete_list"])) {
		$delete_list = $_POST["delete_list"];
	}
	if (isset($_GET["delete_status"])) {
		$delete_status = $_GET["delete_status"];
	} elseif (isset($_POST["delete_status"])) {
		$delete_status = $_POST["delete_status"];
	}
	if (isset($_GET["delete_lead_id"])) {
		$delete_lead_id = $_GET["delete_lead_id"];
	} elseif (isset($_POST["delete_lead_id"])) {
		$delete_lead_id = $_POST["delete_lead_id"];
	}
	if (isset($_GET["delete_count_op"])) {
		$delete_count_op = $_GET["delete_count_op"];
	} elseif (isset($_POST["delete_count_op"])) {
		$delete_count_op = $_POST["delete_count_op"];
	}
	if (isset($_GET["delete_count_num"])) {
		$delete_count_num = $_GET["delete_count_num"];
	} elseif (isset($_POST["delete_count_num"])) {
		$delete_count_num = $_POST["delete_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_delete_country_code") . " = $enable_delete_country_code | " . _QXZ("enable_delete_vendor_lead_code") . " = $enable_delete_vendor_lead_code | " . _QXZ("enable_delete_source_id") . " = $enable_delete_source_id | " . _QXZ("enable_delete_owner") . " = $enable_delete_owner | " . _QXZ("enable_delete_state") . " = $enable_delete_state | " . _QXZ("enable_delete_entry_date") . " = $enable_delete_entry_date | " . _QXZ("enable_delete_modify_date") . " = $enable_delete_modify_date | " . _QXZ("enable_delete_security_phrase") . " = $enable_delete_security_phrase | " . _QXZ("enable_delete_count") . " = $enable_delete_count | " . _QXZ("delete_country_code") . " = $delete_country_code | " . _QXZ("delete_vendor_lead_code") . " = $delete_vendor_lead_code | " . _QXZ("delete_source_id") . " = $delete_source_id | " . _QXZ("delete_owner") . " = $delete_owner | " . _QXZ("delete_state") . " = $delete_state | " . _QXZ("delete_entry_date") . " = $delete_entry_date | " . _QXZ("delete_entry_date_end") . " = $delete_entry_date_end | " . _QXZ("delete_entry_date_op") . " = $delete_entry_date_op | " . _QXZ("delete_modify_date") . " = $delete_modify_date | " . _QXZ("delete_modify_date_end") . " = $delete_modify_date_end | " . _QXZ("delete_modify_date_op") . " = $delete_modify_date_op | " . _QXZ("delete_security_phrase") . " = $delete_security_phrase | " . _QXZ("delete_list") . " = $delete_list | " . _QXZ("delete_status") . " = $delete_status | " . _QXZ("delete_count_op") . " = $delete_count_op | " . _QXZ("delete_count_num") . " = $delete_count_num | " . _QXZ("delete_lead_id") . " = $delete_lead_id</p>";
	}

	# filter out anything bad
	$enable_delete_lead_id = preg_replace('/[^a-zA-Z]/', '', $enable_delete_lead_id);
	$enable_delete_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_delete_country_code);
	$enable_delete_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_delete_vendor_lead_code);
	$enable_delete_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_delete_source_id);
	$enable_delete_owner = preg_replace('/[^a-zA-Z]/', '', $enable_delete_owner);
	$enable_delete_state = preg_replace('/[^a-zA-Z]/', '', $enable_delete_state);
	$enable_delete_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_delete_entry_date);
	$enable_delete_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_delete_modify_date);
	$enable_delete_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_delete_security_phrase);
	$enable_delete_count = preg_replace('/[^a-zA-Z]/', '', $enable_delete_count);
	$delete_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $delete_country_code);
	$delete_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_vendor_lead_code);
	$delete_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_source_id);
	$delete_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_owner);
	$delete_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $delete_state);
	$delete_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_entry_date);
	$delete_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_entry_date_end);
	$delete_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $delete_entry_date_op);
	$delete_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_modify_date);
	$delete_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_modify_date_end);
	$delete_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $delete_modify_date_op);
	$delete_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_security_phrase);
	$delete_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $delete_status);
	$delete_lead_id = preg_replace('/[^0-9]/', '', $delete_lead_id);
	$delete_list = preg_replace('/[^0-9\|]/', '', $delete_list);
	$delete_count_num = preg_replace('/[^0-9]/', '', $delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/', '', $delete_count_op);

	$delete_count_op_phrase = "";
	if ($delete_count_op == "<") {
		$delete_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($delete_count_op == "<=") {
		$delete_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($delete_count_op == ">") {
		$delete_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($delete_count_op == ">=") {
		$delete_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the delete_entry_date operation phrase
	$delete_entry_date_op_phrase = "";
	$delete_entry_operator_a = '>=';
	$delete_entry_operator_b = '<=';
	if ($delete_entry_date_op == "<") {
		$delete_entry_operator_a = '>=';
		$delete_entry_operator_b = '<';
		$delete_entry_date_end = $delete_entry_date;
		$delete_entry_date = '0000-00-00 00:00:00';
		$delete_entry_date_op_phrase = _QXZ("less than") . " $delete_entry_date_end";
	} elseif ($delete_entry_date_op == "<=") {
		$delete_entry_date_end = $delete_entry_date;
		$delete_entry_date = '0000-00-00 00:00:00';
		$delete_entry_date_op_phrase = _QXZ("less than or equal to") . " $delete_entry_date_end";
	} elseif ($delete_entry_date_op == ">") {
		$delete_entry_operator_a = '>';
		$delete_entry_operator_b = '<';
		$delete_entry_date_end = '2100-00-00 00:00:00';
		$delete_entry_date_op_phrase = _QXZ("greater than") . " $delete_entry_date";
	} elseif ($delete_entry_date_op == ">=") {
		$delete_entry_date_end = '2100-00-00 00:00:00';
		$delete_entry_date_op_phrase = _QXZ("greater than or equal to") . " $delete_entry_date";
	} elseif ($delete_entry_date_op == "range") {
		$delete_entry_date_op_phrase = _QXZ("range") . " $delete_entry_date - $delete_entry_date_end";
	} elseif ($delete_entry_date_op == "=") {
		$delete_entry_date_end = $delete_entry_date;
		$delete_entry_date_op_phrase = _QXZ("equal to") . " $delete_entry_date";
	}

	# build the delete_modify_date operation phrase
	$delete_modify_date_op_phrase = "";
	$delete_modify_operator_a = '>=';
	$delete_modify_operator_b = '<=';
	if ($delete_modify_date_op == "<") {
		$delete_modify_operator_a = '>=';
		$delete_modify_operator_b = '<';
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date = '0000-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("less than") . " $delete_modify_date_end";
	} elseif ($delete_modify_date_op == "<=") {
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date = '0000-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("less than or equal to") . " $delete_modify_date_end";
	} elseif ($delete_modify_date_op == ">") {
		$delete_modify_operator_a = '>';
		$delete_modify_operator_b = '<';
		$delete_modify_date_end = '2100-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("greater than") . " $delete_modify_date";
	} elseif ($delete_modify_date_op == ">=") {
		$delete_modify_date_end = '2100-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("greater than or equal to") . " $delete_modify_date";
	} elseif ($delete_modify_date_op == "range") {
		$delete_modify_date_op_phrase = _QXZ("range") . " $delete_modify_date - $delete_modify_date_end";
	} elseif ($delete_modify_date_op == "=") {
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date_op_phrase = _QXZ("equal to") . " $delete_modify_date";
	}

	if (strlen($delete_entry_date) == 10) {
		$delete_entry_date .= " 00:00:00";
	}
	if (strlen($delete_entry_date_end) == 10) {
		$delete_entry_date_end .= " 23:59:59";
	}
	if (strlen($delete_modify_date) == 10) {
		$delete_modify_date .= " 00:00:00";
	}
	if (strlen($delete_modify_date_end) == 10) {
		$delete_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_delete_country_code") . " = $enable_delete_country_code | " . _QXZ("enable_delete_vendor_lead_code") . " = $enable_delete_vendor_lead_code | " . _QXZ("enable_delete_source_id") . " = $enable_delete_source_id | " . _QXZ("enable_delete_owner") . " = $enable_delete_owner | " . _QXZ("enable_delete_state") . " = $enable_delete_state | " . _QXZ("enable_delete_entry_date") . " = $enable_delete_entry_date | " . _QXZ("enable_delete_modify_date") . " = $enable_delete_modify_date | " . _QXZ("enable_delete_security_phrase") . " = $enable_delete_security_phrase | " . _QXZ("enable_delete_count") . " = $enable_delete_count | " . _QXZ("delete_country_code") . " = $delete_country_code | " . _QXZ("delete_vendor_lead_code") . " = $delete_vendor_lead_code | " . _QXZ("delete_source_id") . " = $delete_source_id | " . _QXZ("delete_owner") . " = $delete_owner | " . _QXZ("delete_state") . " = $delete_state | " . _QXZ("delete_entry_date") . " = $delete_entry_date | " . _QXZ("delete_entry_date_end") . " = $delete_entry_date_end | " . _QXZ("delete_entry_date_op") . " = $delete_entry_date_op | " . _QXZ("delete_modify_date") . " = $delete_modify_date | " . _QXZ("delete_modify_date_end") . " = $delete_modify_date_end | " . _QXZ("delete_modify_date_op") . " = $delete_modify_date_op | " . _QXZ("delete_security_phrase") . " = $delete_security_phrase | " . _QXZ("delete_list") . " = $delete_list | " . _QXZ("delete_status") . " = $delete_status | " . _QXZ("delete_count_op") . " = $delete_count_op | " . _QXZ("delete_count_num") . " = $delete_count_num | " . _QXZ("delete_lead_id") . " = $delete_lead_id</p>";
	}

	# make sure the required fields are set
	if ($delete_status == '') {
		missing_required_field('Status');
	}
	if ($delete_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the delete phrase
	$sql_where = "";
	if ($delete_status != '---ALL---') {
		$sql_where = $sql_where . " and status like '$delete_status' ";
	}
	$delete_parm = "";
	$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $delete_status<br />";
	if (($enable_delete_lead_id == "enabled") && ($delete_lead_id != '')) {
		if ($delete_lead_id == '---BLANK---') {
			$delete_lead_id = '';
		}
		$sql_where = $sql_where . " and lead_id like '$delete_lead_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("lead ID is like") . " $delete_lead_id<br />";
		if ($delete_lead_id == '') {
			$delete_lead_id = '---BLANK---';
		}
	} elseif ($enable_delete_lead_id == "enabled") {
		blank_field('Lead ID', true);
	}
	if (($enable_delete_country_code == "enabled") && ($delete_country_code != '')) {
		if ($delete_country_code == '---BLANK---') {
			$delete_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$delete_country_code\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $delete_country_code<br />";
		if ($delete_country_code == '') {
			$delete_country_code = '---BLANK---';
		}
	} elseif ($enable_delete_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_delete_vendor_lead_code == "enabled") && ($delete_vendor_lead_code != '')) {
		if ($delete_vendor_lead_code == '---BLANK---') {
			$delete_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$delete_vendor_lead_code\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $delete_vendor_lead_code<br />";
		if ($delete_vendor_lead_code == '') {
			$delete_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_delete_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_delete_source_id == "enabled") && ($delete_source_id != '')) {
		if ($delete_source_id == '---BLANK---') {
			$delete_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$delete_source_id\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id code is like") . " $delete_source_id<br />";
		if ($delete_source_id == '') {
			$delete_source_id = '---BLANK---';
		}
	} elseif ($enable_delete_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_delete_security_phrase == "enabled") && ($delete_security_phrase != '')) {
		if ($delete_security_phrase == '---BLANK---') {
			$delete_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$delete_security_phrase\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $delete_security_phrase<br />";
		if ($delete_security_phrase == '') {
			$delete_security_phrase = '---BLANK---';
		}
	} elseif ($enable_delete_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_delete_owner == "enabled") && ($delete_owner != '')) {
		if ($delete_owner == '---BLANK---') {
			$delete_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$delete_owner\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $delete_owner<br />";
		if ($delete_owner == '') {
			$delete_owner = '---BLANK---';
		}
	} elseif ($enable_delete_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_delete_state == "enabled") && ($delete_state != '')) {
		if ($delete_state == '---BLANK---') {
			$delete_state = '';
		}
		$sql_where = $sql_where . " and state like \"$delete_state\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $delete_state<br />";
		if ($delete_state == '') {
			$delete_state = '---BLANK---';
		}
	} elseif ($enable_delete_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_delete_entry_date == "enabled") && ($delete_entry_date != '')) {
		if ($delete_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $delete_entry_operator_a '$delete_entry_date' and entry_date $delete_entry_operator_b '$delete_entry_date_end' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $delete_entry_date_op_phrase<br />";
		}
	} elseif ($enable_delete_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_delete_modify_date == "enabled") && ($delete_modify_date != '')) {
		if ($delete_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $delete_modify_operator_a '$delete_modify_date' and modify_date $delete_modify_operator_b '$delete_modify_date_end' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $delete_modify_date_op_phrase<br />";
		}
	} elseif ($enable_delete_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_delete_count == "enabled") && ($delete_count_op != '') && ($delete_count_num != '')) {
		if ($delete_count_op == '---BLANK---') {
			$delete_count_op = '';
		}
		if ($delete_count_num == '---BLANK---') {
			$delete_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $delete_count_op $delete_count_num";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $delete_count_op_phrase $delete_count_num<br />";
		if ($delete_count_op == '') {
			$delete_count_op = '---BLANK---';
		}
		if ($delete_count_num == '') {
			$delete_count_num = '---BLANK---';
		}
	} elseif ($enable_delete_count == "enabled") {
		blank_field('Move Count', true);
	}

	# get the number of leads this action will move
	$delete_lead_count = 0;
	$delete_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id IN('" . implode("','", $delete_list) . "') $sql_where";
	if ($DB) {
		echo "|$delete_lead_count_stmt|\n";
	}
	$delete_lead_count_rslt = mysql_to_mysqli($delete_lead_count_stmt, $link);
	$delete_lead_count_row = mysqli_fetch_row($delete_lead_count_rslt);
	$delete_lead_count = $delete_lead_count_row[0];

	echo "<p>" . _QXZ("You are about to delete") . " <B>$delete_lead_count</B> " . _QXZ("leads in list") . " " . implode(",", $delete_list) . " " . _QXZ("with the following parameters") . ":<br /><br />$delete_parm<br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_delete_lead_id value='$enable_delete_lead_id'>\n";
	echo "<input type=hidden name=enable_delete_country_code value='$enable_delete_country_code'>\n";
	echo "<input type=hidden name=enable_delete_vendor_lead_code value='$enable_delete_vendor_lead_code'>\n";
	echo "<input type=hidden name=enable_delete_source_id value='$enable_delete_source_id'>\n";
	echo "<input type=hidden name=enable_delete_owner value='$enable_delete_owner'>\n";
	echo "<input type=hidden name=enable_delete_state value='$enable_delete_state'>\n";
	echo "<input type=hidden name=enable_delete_entry_date value='$enable_delete_entry_date'>\n";
	echo "<input type=hidden name=enable_delete_modify_date value='$enable_delete_modify_date'>\n";
	echo "<input type=hidden name=enable_delete_security_phrase value='$enable_delete_security_phrase'>\n";
	echo "<input type=hidden name=enable_delete_count value='$enable_delete_count'>\n";
	echo "<input type=hidden name=delete_country_code value=\"$delete_country_code\">\n";
	echo "<input type=hidden name=delete_vendor_lead_code value=\"$delete_vendor_lead_code\">\n";
	echo "<input type=hidden name=delete_source_id value=\"$delete_source_id\">\n";
	echo "<input type=hidden name=delete_owner value=\"$delete_owner\">\n";
	echo "<input type=hidden name=delete_state value=\"$delete_state\">\n";
	echo "<input type=hidden name=delete_entry_date value=\"$delete_entry_date\">\n";
	echo "<input type=hidden name=delete_entry_date_end value=\"$delete_entry_date_end\">\n";
	echo "<input type=hidden name=delete_entry_date_op value=\"$delete_entry_date_op\">\n";
	echo "<input type=hidden name=delete_modify_date value=\"$delete_modify_date\">\n";
	echo "<input type=hidden name=delete_modify_date_end value=\"$delete_modify_date_end\">\n";
	echo "<input type=hidden name=delete_modify_date_op value=\"$delete_modify_date_op\">\n";
	echo "<input type=hidden name=delete_security_phrase value=\"$delete_security_phrase\">\n";
	echo "<input type=hidden name=delete_list value='" . implode("|", $delete_list) . "'>\n";
	echo "<input type=hidden name=delete_status value=\"$delete_status\">\n";
	echo "<input type=hidden name=delete_count_op value=\"$delete_count_op\">\n";
	echo "<input type=hidden name=delete_count_num value=\"$delete_count_num\">\n";
	echo "<input type=hidden name=delete_lead_id value=\"$delete_lead_id\">\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_delete value='" . _QXZ("confirm") . "'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	echo "</body>\n</html>\n";
}

# actually do the delete
if (($confirm_delete == _QXZ("confirm")) && ($delete_lists > 0)) {
	# get the variables
	$enable_delete_lead_id = "";
	$enable_delete_country_code = "";
	$enable_delete_vendor_lead_code = "";
	$enable_delete_source_id = "";
	$enable_delete_owner = "";
	$enable_delete_state = "";
	$enable_delete_entry_date = "";
	$enable_delete_modify_date = "";
	$enable_delete_security_phrase = "";
	$enable_delete_count = "";
	$delete_country_code = "";
	$delete_vendor_lead_code = "";
	$delete_source_id = "";
	$delete_owner = "";
	$delete_state = "";
	$delete_entry_date = "";
	$delete_entry_date_end = "";
	$delete_entry_date_op = "";
	$delete_modify_date = "";
	$delete_modify_date_end = "";
	$delete_modify_date_op = "";
	$delete_security_phrase = "";
	$delete_list = "";
	$delete_status = "";
	$delete_count_op = "";
	$delete_count_num = "";
	$delete_lead_id = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_delete_lead_id"])) {
		$enable_delete_lead_id = $_GET["enable_delete_lead_id"];
	} elseif (isset($_POST["enable_delete_lead_id"])) {
		$enable_delete_lead_id = $_POST["enable_delete_lead_id"];
	}
	if (isset($_GET["enable_delete_country_code"])) {
		$enable_delete_country_code = $_GET["enable_delete_country_code"];
	} elseif (isset($_POST["enable_delete_country_code"])) {
		$enable_delete_country_code = $_POST["enable_delete_country_code"];
	}
	if (isset($_GET["enable_delete_vendor_lead_code"])) {
		$enable_delete_vendor_lead_code = $_GET["enable_delete_vendor_lead_code"];
	} elseif (isset($_POST["enable_delete_vendor_lead_code"])) {
		$enable_delete_vendor_lead_code = $_POST["enable_delete_vendor_lead_code"];
	}
	if (isset($_GET["enable_delete_source_id"])) {
		$enable_delete_source_id = $_GET["enable_delete_source_id"];
	} elseif (isset($_POST["enable_delete_source_id"])) {
		$enable_delete_source_id = $_POST["enable_delete_source_id"];
	}
	if (isset($_GET["enable_delete_owner"])) {
		$enable_delete_owner = $_GET["enable_delete_owner"];
	} elseif (isset($_POST["enable_delete_owner"])) {
		$enable_delete_owner = $_POST["enable_delete_owner"];
	}
	if (isset($_GET["enable_delete_state"])) {
		$enable_delete_state = $_GET["enable_delete_state"];
	} elseif (isset($_POST["enable_delete_state"])) {
		$enable_delete_state = $_POST["enable_delete_state"];
	}
	if (isset($_GET["enable_delete_entry_date"])) {
		$enable_delete_entry_date = $_GET["enable_delete_entry_date"];
	} elseif (isset($_POST["enable_delete_entry_date"])) {
		$enable_delete_entry_date = $_POST["enable_delete_entry_date"];
	}
	if (isset($_GET["enable_delete_modify_date"])) {
		$enable_delete_modify_date = $_GET["enable_delete_modify_date"];
	} elseif (isset($_POST["enable_delete_modify_date"])) {
		$enable_delete_modify_date = $_POST["enable_delete_modify_date"];
	}
	if (isset($_GET["enable_delete_security_phrase"])) {
		$enable_delete_security_phrase = $_GET["enable_delete_security_phrase"];
	} elseif (isset($_POST["enable_delete_security_phrase"])) {
		$enable_delete_security_phrase = $_POST["enable_delete_security_phrase"];
	}
	if (isset($_GET["enable_delete_count"])) {
		$enable_delete_count = $_GET["enable_delete_count"];
	} elseif (isset($_POST["enable_delete_count"])) {
		$enable_delete_count = $_POST["enable_delete_count"];
	}
	if (isset($_GET["delete_country_code"])) {
		$delete_country_code = $_GET["delete_country_code"];
	} elseif (isset($_POST["delete_country_code"])) {
		$delete_country_code = $_POST["delete_country_code"];
	}
	if (isset($_GET["delete_vendor_lead_code"])) {
		$delete_vendor_lead_code = $_GET["delete_vendor_lead_code"];
	} elseif (isset($_POST["delete_vendor_lead_code"])) {
		$delete_vendor_lead_code = $_POST["delete_vendor_lead_code"];
	}
	if (isset($_GET["delete_source_id"])) {
		$delete_source_id = $_GET["delete_source_id"];
	} elseif (isset($_POST["delete_source_id"])) {
		$delete_source_id = $_POST["delete_source_id"];
	}
	if (isset($_GET["delete_owner"])) {
		$delete_owner = $_GET["delete_owner"];
	} elseif (isset($_POST["delete_owner"])) {
		$delete_owner = $_POST["delete_owner"];
	}
	if (isset($_GET["delete_state"])) {
		$delete_state = $_GET["delete_state"];
	} elseif (isset($_POST["delete_state"])) {
		$delete_state = $_POST["delete_state"];
	}
	if (isset($_GET["delete_entry_date"])) {
		$delete_entry_date = $_GET["delete_entry_date"];
	} elseif (isset($_POST["delete_entry_date"])) {
		$delete_entry_date = $_POST["delete_entry_date"];
	}
	if (isset($_GET["delete_entry_date_end"])) {
		$delete_entry_date_end = $_GET["delete_entry_date_end"];
	} elseif (isset($_POST["delete_entry_date_end"])) {
		$delete_entry_date_end = $_POST["delete_entry_date_end"];
	}
	if (isset($_GET["delete_entry_date_op"])) {
		$delete_entry_date_op = $_GET["delete_entry_date_op"];
	} elseif (isset($_POST["delete_entry_date_op"])) {
		$delete_entry_date_op = $_POST["delete_entry_date_op"];
	}
	if (isset($_GET["delete_modify_date"])) {
		$delete_modify_date = $_GET["delete_modify_date"];
	} elseif (isset($_POST["delete_modify_date"])) {
		$delete_modify_date = $_POST["delete_modify_date"];
	}
	if (isset($_GET["delete_modify_date_end"])) {
		$delete_modify_date_end = $_GET["delete_modify_date_end"];
	} elseif (isset($_POST["delete_modify_date_end"])) {
		$delete_modify_date_end = $_POST["delete_modify_date_end"];
	}
	if (isset($_GET["delete_modify_date_op"])) {
		$delete_modify_date_op = $_GET["delete_modify_date_op"];
	} elseif (isset($_POST["delete_modify_date_op"])) {
		$delete_modify_date_op = $_POST["delete_modify_date_op"];
	}
	if (isset($_GET["delete_security_phrase"])) {
		$delete_security_phrase = $_GET["delete_security_phrase"];
	} elseif (isset($_POST["delete_security_phrase"])) {
		$delete_security_phrase = $_POST["delete_security_phrase"];
	}
	if (isset($_GET["delete_list"])) {
		$delete_list = $_GET["delete_list"];
	} elseif (isset($_POST["delete_list"])) {
		$delete_list = $_POST["delete_list"];
	}
	if (isset($_GET["delete_status"])) {
		$delete_status = $_GET["delete_status"];
	} elseif (isset($_POST["delete_status"])) {
		$delete_status = $_POST["delete_status"];
	}
	if (isset($_GET["delete_lead_id"])) {
		$delete_lead_id = $_GET["delete_lead_id"];
	} elseif (isset($_POST["delete_lead_id"])) {
		$delete_lead_id = $_POST["delete_lead_id"];
	}
	if (isset($_GET["delete_count_op"])) {
		$delete_count_op = $_GET["delete_count_op"];
	} elseif (isset($_POST["delete_count_op"])) {
		$delete_count_op = $_POST["delete_count_op"];
	}
	if (isset($_GET["delete_count_num"])) {
		$delete_count_num = $_GET["delete_count_num"];
	} elseif (isset($_POST["delete_count_num"])) {
		$delete_count_num = $_POST["delete_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_delete_country_code") . " = $enable_delete_country_code | " . _QXZ("enable_delete_vendor_lead_code") . " = $enable_delete_vendor_lead_code | " . _QXZ("enable_delete_source_id") . " = $enable_delete_source_id | " . _QXZ("enable_delete_owner") . " = $enable_delete_owner | " . _QXZ("enable_delete_state") . " = $enable_delete_state | " . _QXZ("enable_delete_entry_date") . " = $enable_delete_entry_date | " . _QXZ("enable_delete_modify_date") . " = $enable_delete_modify_date | " . _QXZ("enable_delete_security_phrase") . " = $enable_delete_security_phrase | " . _QXZ("enable_delete_count") . " = $enable_delete_count | " . _QXZ("delete_country_code") . " = $delete_country_code | " . _QXZ("delete_vendor_lead_code") . " = $delete_vendor_lead_code | " . _QXZ("delete_source_id") . " = $delete_source_id | " . _QXZ("delete_owner") . " = $delete_owner | " . _QXZ("delete_state") . " = $delete_state | " . _QXZ("delete_entry_date") . " = $delete_entry_date | " . _QXZ("delete_entry_date_end") . " = $delete_entry_date_end | " . _QXZ("delete_entry_date_op") . " = $delete_entry_date_op | " . _QXZ("delete_modify_date") . " = $delete_modify_date | " . _QXZ("delete_modify_date_end") . " = $delete_modify_date_end | " . _QXZ("delete_modify_date_op") . " = $delete_modify_date_op | " . _QXZ("delete_security_phrase") . " = $delete_security_phrase | " . _QXZ("delete_list") . " = $delete_list | " . _QXZ("delete_status") . " = $delete_status | " . _QXZ("delete_count_op") . " = $delete_count_op | " . _QXZ("delete_count_num") . " = $delete_count_num | " . _QXZ("delete_lead_id") . " = $delete_lead_id</p>";
	}

	# filter out anything bad
	$enable_delete_lead_id = preg_replace('/[^a-zA-Z]/', '', $enable_delete_lead_id);
	$enable_delete_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_delete_country_code);
	$enable_delete_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_delete_vendor_lead_code);
	$enable_delete_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_delete_source_id);
	$enable_delete_owner = preg_replace('/[^a-zA-Z]/', '', $enable_delete_owner);
	$enable_delete_state = preg_replace('/[^a-zA-Z]/', '', $enable_delete_state);
	$enable_delete_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_delete_entry_date);
	$enable_delete_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_delete_modify_date);
	$enable_delete_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_delete_security_phrase);
	$enable_delete_count = preg_replace('/[^a-zA-Z]/', '', $enable_delete_count);
	$delete_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $delete_country_code);
	$delete_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_vendor_lead_code);
	$delete_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_source_id);
	$delete_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_owner);
	$delete_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $delete_state);
	$delete_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_entry_date);
	$delete_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_entry_date_end);
	$delete_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $delete_entry_date_op);
	$delete_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_modify_date);
	$delete_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $delete_modify_date_end);
	$delete_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $delete_modify_date_op);
	$delete_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $delete_security_phrase);
	$delete_status = preg_replace('/[^-_%0-9a-zA-Z]/', '', $delete_status);
	$delete_lead_id = preg_replace('/[^0-9]/', '', $delete_lead_id);
	$delete_list = preg_replace('/[^0-9\|]/', '', $delete_list);
	$delete_count_num = preg_replace('/[^0-9]/', '', $delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/', '', $delete_count_op);

	$delete_count_op_phrase = "";
	if ($delete_count_op == "<") {
		$delete_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($delete_count_op == "<=") {
		$delete_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($delete_count_op == ">") {
		$delete_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($delete_count_op == ">=") {
		$delete_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the delete_entry_date operation phrase
	$delete_entry_date_op_phrase = "";
	$delete_entry_operator_a = '>=';
	$delete_entry_operator_b = '<=';
	if ($delete_entry_date_op == "<") {
		$delete_entry_operator_a = '>=';
		$delete_entry_operator_b = '<';
		$delete_entry_date_op_phrase = _QXZ("less than") . " $delete_entry_date_end";
	} elseif ($delete_entry_date_op == "<=") {
		$delete_entry_date_op_phrase = _QXZ("less than or equal to") . " $delete_entry_date_end";
	} elseif ($delete_entry_date_op == ">") {
		$delete_entry_operator_a = '>';
		$delete_entry_operator_b = '<';
		$delete_entry_date_op_phrase = _QXZ("greater than") . " $delete_entry_date";
	} elseif ($delete_entry_date_op == ">=") {
		$delete_entry_date_op_phrase = _QXZ("greater than or equal to") . " $delete_entry_date";
	} elseif ($delete_entry_date_op == "range") {
		$delete_entry_date_op_phrase = _QXZ("range") . " $delete_entry_date - $delete_entry_date_end";
	} elseif ($delete_entry_date_op == "=") {
		$delete_entry_date_op_phrase = _QXZ("equal to") . " $delete_entry_date_end";
	}

	# build the delete_modify_date operation phrase
	$delete_modify_date_op_phrase = "";
	$delete_modify_operator_a = '>=';
	$delete_modify_operator_b = '<=';
	if ($delete_modify_date_op == "<") {
		$delete_modify_operator_a = '>=';
		$delete_modify_operator_b = '<';
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date = '0000-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("less than") . " $delete_modify_date_end";
	} elseif ($delete_modify_date_op == "<=") {
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date = '0000-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("less than or equal to") . " $delete_modify_date_end";
	} elseif ($delete_modify_date_op == ">") {
		$delete_modify_operator_a = '>';
		$delete_modify_operator_b = '<';
		$delete_modify_date_end = '2100-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("greater than") . " $delete_modify_date";
	} elseif ($delete_modify_date_op == ">=") {
		$delete_modify_date_end = '2100-00-00 00:00:00';
		$delete_modify_date_op_phrase = _QXZ("greater than or equal to") . " $delete_modify_date";
	} elseif ($delete_modify_date_op == "range") {
		$delete_modify_date_op_phrase = _QXZ("range") . " $delete_modify_date - $delete_modify_date_end";
	} elseif ($delete_modify_date_op == "=") {
		$delete_modify_date_end = $delete_modify_date;
		$delete_modify_date_op_phrase = _QXZ("equal to") . " $delete_modify_date";
	}

	if (strlen($delete_entry_date) == 10) {
		$delete_entry_date .= " 00:00:00";
	}
	if (strlen($delete_entry_date_end) == 10) {
		$delete_entry_date_end .= " 23:59:59";
	}
	if (strlen($delete_modify_date) == 10) {
		$delete_modify_date .= " 00:00:00";
	}
	if (strlen($delete_modify_date_end) == 10) {
		$delete_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_delete_country_code") . " = $enable_delete_country_code | " . _QXZ("enable_delete_vendor_lead_code") . " = $enable_delete_vendor_lead_code | " . _QXZ("enable_delete_source_id") . " = $enable_delete_source_id | " . _QXZ("enable_delete_owner") . " = $enable_delete_owner | " . _QXZ("enable_delete_state") . " = $enable_delete_state | " . _QXZ("enable_delete_entry_date") . " = $enable_delete_entry_date | " . _QXZ("enable_delete_modify_date") . " = $enable_delete_modify_date | " . _QXZ("enable_delete_security_phrase") . " = $enable_delete_security_phrase | " . _QXZ("enable_delete_count") . " = $enable_delete_count | " . _QXZ("delete_country_code") . " = $delete_country_code | " . _QXZ("delete_vendor_lead_code") . " = $delete_vendor_lead_code | " . _QXZ("delete_source_id") . " = $delete_source_id | " . _QXZ("delete_owner") . " = $delete_owner | " . _QXZ("delete_state") . " = $delete_state | " . _QXZ("delete_entry_date") . " = $delete_entry_date | " . _QXZ("delete_entry_date_end") . " = $delete_entry_date_end | " . _QXZ("delete_entry_date_op") . " = $delete_entry_date_op | " . _QXZ("delete_modify_date") . " = $delete_modify_date | " . _QXZ("delete_modify_date_end") . " = $delete_modify_date_end | " . _QXZ("delete_modify_date_op") . " = $delete_modify_date_op | " . _QXZ("delete_security_phrase") . " = $delete_security_phrase | " . _QXZ("delete_list") . " = $delete_list | " . _QXZ("delete_status") . " = $delete_status | " . _QXZ("delete_count_op") . " = $delete_count_op | " . _QXZ("delete_count_num") . " = $delete_count_num | " . _QXZ("delete_lead_id") . " = $delete_lead_id</p>";
	}

	# make sure the required fields are set
	if ($delete_status == '') {
		missing_required_field('Status');
	}
	if ($delete_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the delete phrase
	$sql_where = "";
	if ($delete_status != '---ALL---') {
		$sql_where = $sql_where . " and status like '$delete_status' ";
	}
	$delete_parm = "";
	$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " $delete_status<br />";
	if (($enable_delete_lead_id == "enabled") && ($delete_lead_id != '')) {
		if ($delete_lead_id == '---BLANK---') {
			$delete_lead_id = '';
		}
		$sql_where = $sql_where . " and lead_id like '$delete_lead_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("lead ID is like") . " $delete_lead_id<br />";
		if ($delete_lead_id == '') {
			$delete_lead_id = '---BLANK---';
		}
	} elseif ($enable_delete_lead_id == "enabled") {
		blank_field('Lead ID', true);
	}
	if (($enable_delete_country_code == "enabled") && ($delete_country_code != '')) {
		if ($delete_country_code == '---BLANK---') {
			$delete_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$delete_country_code\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $delete_country_code<br />";
		if ($delete_country_code == '') {
			$delete_country_code = '---BLANK---';
		}
	} elseif ($enable_delete_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_delete_vendor_lead_code == "enabled") && ($delete_vendor_lead_code != '')) {
		if ($delete_vendor_lead_code == '---BLANK---') {
			$delete_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$delete_vendor_lead_code\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $delete_vendor_lead_code<br />";
		if ($delete_vendor_lead_code == '') {
			$delete_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_delete_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_delete_source_id == "enabled") && ($delete_source_id != '')) {
		if ($delete_source_id == '---BLANK---') {
			$delete_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$delete_source_id\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id code is like") . " $delete_source_id<br />";
		if ($delete_source_id == '') {
			$delete_source_id = '---BLANK---';
		}
	} elseif ($enable_delete_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_delete_security_phrase == "enabled") && ($delete_security_phrase != '')) {
		if ($delete_security_phrase == '---BLANK---') {
			$delete_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$delete_security_phrase\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $delete_security_phrase<br />";
		if ($delete_security_phrase == '') {
			$delete_security_phrase = '---BLANK---';
		}
	} elseif ($enable_delete_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_delete_owner == "enabled") && ($delete_owner != '')) {
		if ($delete_owner == '---BLANK---') {
			$delete_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$delete_owner\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $delete_owner<br />";
		if ($delete_owner == '') {
			$delete_owner = '---BLANK---';
		}
	} elseif ($enable_delete_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_delete_state == "enabled") && ($delete_state != '')) {
		if ($delete_state == '---BLANK---') {
			$delete_state = '';
		}
		$sql_where = $sql_where . " and state like \"$delete_state\" ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $delete_state<br />";
		if ($delete_state == '') {
			$delete_state = '---BLANK---';
		}
	} elseif ($enable_delete_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_delete_entry_date == "enabled") && ($delete_entry_date != '')) {
		if ($delete_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $delete_entry_operator_a '$delete_entry_date' and entry_date $delete_entry_operator_b '$delete_entry_date_end' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $delete_entry_date_op_phrase<br />";
		}
	} elseif ($enable_delete_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_delete_modify_date == "enabled") && ($delete_modify_date != '')) {
		if ($delete_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $delete_modify_operator_a '$delete_modify_date' and modify_date $delete_modify_operator_b '$delete_modify_date_end' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $delete_modify_date_op_phrase<br />";
		}
	} elseif ($enable_delete_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_delete_count == "enabled") && ($delete_count_op != '') && ($delete_count_num != '')) {
		if ($delete_count_op == '---BLANK---') {
			$delete_count_op = '';
		}
		if ($delete_count_num == '---BLANK---') {
			$delete_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $delete_count_op $delete_count_num";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $delete_count_op_phrase $delete_count_num<br />";
		if ($delete_count_op == '') {
			$delete_count_op = '---BLANK---';
		}
		if ($delete_count_num == '') {
			$delete_count_num = '---BLANK---';
		}
	} elseif ($enable_delete_count == "enabled") {
		blank_field('Move Count', true);
	}

	$delete_list_array = explode("|", $delete_list);
	$delete_lead_stmt = "DELETE FROM vicidial_list WHERE list_id IN('" . implode("', '", $delete_list_array) . "') $sql_where";
	if ($DB) {
		echo "|$delete_lead_stmt|\n";
	}
	$delete_lead_rslt = mysql_to_mysqli($delete_lead_stmt, $link);
	$delete_lead_count = mysqli_affected_rows($link);

	$delete_sentence = "<B>$delete_lead_count</B> leads delete from list $delete_list with the following parameters:<br /><br />$delete_parm<br />";

	$SQL_log = "$delete_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$delete_list', event_code='ADMIN DELETE LEADS', event_sql=\"$SQL_log\", event_notes=\"$delete_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$delete_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
##### END delete process #####


##### BEGIN reset called count process #####
# reset called count confirmation page
if (($reset_called_count_submit == _QXZ("reset called count")) && ($modify_leads > 0)) {
	# get the variables
	$enable_reset_called_count_lead_id = "";
	$enable_reset_called_count_country_code = "";
	$enable_reset_called_count_vendor_lead_code = "";
	$enable_reset_called_count_source_id = "";
	$enable_reset_called_count_owner = "";
	$enable_reset_called_count_state = "";
	$enable_reset_called_count_entry_date = "";
	$enable_reset_called_count_modify_date = "";
	$enable_reset_called_count_security_phrase = "";
	$enable_reset_called_count_count = "";
	$reset_called_count_country_code = "";
	$reset_called_count_vendor_lead_code = "";
	$reset_called_count_source_id = "";
	$reset_called_count_owner = "";
	$reset_called_count_state = "";
	$reset_called_count_entry_date = "";
	$reset_called_count_entry_date_end = "";
	$reset_called_count_entry_date_op = "";
	$reset_called_count_modify_date = "";
	$reset_called_count_modify_date_end = "";
	$reset_called_count_modify_date_op = "";
	$reset_called_count_security_phrase = "";
	$reset_called_count_list = "";
	$reset_called_count_status = "";
	$reset_called_count_count_op = "";
	$reset_called_count_count_num = "";
	$reset_called_count_lead_id = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_reset_called_count_lead_id"])) {
		$enable_reset_called_count_lead_id = $_GET["enable_reset_called_count_lead_id"];
	} elseif (isset($_POST["enable_reset_called_count_lead_id"])) {
		$enable_reset_called_count_lead_id = $_POST["enable_reset_called_count_lead_id"];
	}
	if (isset($_GET["enable_reset_called_count_country_code"])) {
		$enable_reset_called_count_country_code = $_GET["enable_reset_called_count_country_code"];
	} elseif (isset($_POST["enable_reset_called_count_country_code"])) {
		$enable_reset_called_count_country_code = $_POST["enable_reset_called_count_country_code"];
	}
	if (isset($_GET["enable_reset_called_count_vendor_lead_code"])) {
		$enable_reset_called_count_vendor_lead_code = $_GET["enable_reset_called_count_vendor_lead_code"];
	} elseif (isset($_POST["enable_reset_called_count_vendor_lead_code"])) {
		$enable_reset_called_count_vendor_lead_code = $_POST["enable_reset_called_count_vendor_lead_code"];
	}
	if (isset($_GET["enable_reset_called_count_source_id"])) {
		$enable_reset_called_count_source_id = $_GET["enable_reset_called_count_source_id"];
	} elseif (isset($_POST["enable_reset_called_count_source_id"])) {
		$enable_reset_called_count_source_id = $_POST["enable_reset_called_count_source_id"];
	}
	if (isset($_GET["enable_reset_called_count_owner"])) {
		$enable_reset_called_count_owner = $_GET["enable_reset_called_count_owner"];
	} elseif (isset($_POST["enable_reset_called_count_owner"])) {
		$enable_reset_called_count_owner = $_POST["enable_reset_called_count_owner"];
	}
	if (isset($_GET["enable_reset_called_count_state"])) {
		$enable_reset_called_count_state = $_GET["enable_reset_called_count_state"];
	} elseif (isset($_POST["enable_reset_called_count_state"])) {
		$enable_reset_called_count_state = $_POST["enable_reset_called_count_state"];
	}
	if (isset($_GET["enable_reset_called_count_entry_date"])) {
		$enable_reset_called_count_entry_date = $_GET["enable_reset_called_count_entry_date"];
	} elseif (isset($_POST["enable_reset_called_count_entry_date"])) {
		$enable_reset_called_count_entry_date = $_POST["enable_reset_called_count_entry_date"];
	}
	if (isset($_GET["enable_reset_called_count_modify_date"])) {
		$enable_reset_called_count_modify_date = $_GET["enable_reset_called_count_modify_date"];
	} elseif (isset($_POST["enable_reset_called_count_modify_date"])) {
		$enable_reset_called_count_modify_date = $_POST["enable_reset_called_count_modify_date"];
	}
	if (isset($_GET["enable_reset_called_count_security_phrase"])) {
		$enable_reset_called_count_security_phrase = $_GET["enable_reset_called_count_security_phrase"];
	} elseif (isset($_POST["enable_reset_called_count_security_phrase"])) {
		$enable_reset_called_count_security_phrase = $_POST["enable_reset_called_count_security_phrase"];
	}
	if (isset($_GET["enable_reset_called_count_count"])) {
		$enable_reset_called_count_count = $_GET["enable_reset_called_count_count"];
	} elseif (isset($_POST["enable_reset_called_count_count"])) {
		$enable_reset_called_count_count = $_POST["enable_reset_called_count_count"];
	}
	if (isset($_GET["reset_called_count_country_code"])) {
		$reset_called_count_country_code = $_GET["reset_called_count_country_code"];
	} elseif (isset($_POST["reset_called_count_country_code"])) {
		$reset_called_count_country_code = $_POST["reset_called_count_country_code"];
	}
	if (isset($_GET["reset_called_count_vendor_lead_code"])) {
		$reset_called_count_vendor_lead_code = $_GET["reset_called_count_vendor_lead_code"];
	} elseif (isset($_POST["reset_called_count_vendor_lead_code"])) {
		$reset_called_count_vendor_lead_code = $_POST["reset_called_count_vendor_lead_code"];
	}
	if (isset($_GET["reset_called_count_source_id"])) {
		$reset_called_count_source_id = $_GET["reset_called_count_source_id"];
	} elseif (isset($_POST["reset_called_count_source_id"])) {
		$reset_called_count_source_id = $_POST["reset_called_count_source_id"];
	}
	if (isset($_GET["reset_called_count_owner"])) {
		$reset_called_count_owner = $_GET["reset_called_count_owner"];
	} elseif (isset($_POST["reset_called_count_owner"])) {
		$reset_called_count_owner = $_POST["reset_called_count_owner"];
	}
	if (isset($_GET["reset_called_count_state"])) {
		$reset_called_count_state = $_GET["reset_called_count_state"];
	} elseif (isset($_POST["reset_called_count_state"])) {
		$reset_called_count_state = $_POST["reset_called_count_state"];
	}
	if (isset($_GET["reset_called_count_entry_date"])) {
		$reset_called_count_entry_date = $_GET["reset_called_count_entry_date"];
	} elseif (isset($_POST["reset_called_count_entry_date"])) {
		$reset_called_count_entry_date = $_POST["reset_called_count_entry_date"];
	}
	if (isset($_GET["reset_called_count_entry_date_end"])) {
		$reset_called_count_entry_date_end = $_GET["reset_called_count_entry_date_end"];
	} elseif (isset($_POST["reset_called_count_entry_date_end"])) {
		$reset_called_count_entry_date_end = $_POST["reset_called_count_entry_date_end"];
	}
	if (isset($_GET["reset_called_count_entry_date_op"])) {
		$reset_called_count_entry_date_op = $_GET["reset_called_count_entry_date_op"];
	} elseif (isset($_POST["reset_called_count_entry_date_op"])) {
		$reset_called_count_entry_date_op = $_POST["reset_called_count_entry_date_op"];
	}
	if (isset($_GET["reset_called_count_modify_date"])) {
		$reset_called_count_modify_date = $_GET["reset_called_count_modify_date"];
	} elseif (isset($_POST["reset_called_count_modify_date"])) {
		$reset_called_count_modify_date = $_POST["reset_called_count_modify_date"];
	}
	if (isset($_GET["reset_called_count_modify_date_end"])) {
		$reset_called_count_modify_date_end = $_GET["reset_called_count_modify_date_end"];
	} elseif (isset($_POST["reset_called_count_modify_date_end"])) {
		$reset_called_count_modify_date_end = $_POST["reset_called_count_modify_date_end"];
	}
	if (isset($_GET["reset_called_count_modify_date_op"])) {
		$reset_called_count_modify_date_op = $_GET["reset_called_count_modify_date_op"];
	} elseif (isset($_POST["reset_called_count_modify_date_op"])) {
		$reset_called_count_modify_date_op = $_POST["reset_called_count_modify_date_op"];
	}
	if (isset($_GET["reset_called_count_security_phrase"])) {
		$reset_called_count_security_phrase = $_GET["reset_called_count_security_phrase"];
	} elseif (isset($_POST["reset_called_count_security_phrase"])) {
		$reset_called_count_security_phrase = $_POST["reset_called_count_security_phrase"];
	}
	if (isset($_GET["reset_called_count_list"])) {
		$reset_called_count_list = $_GET["reset_called_count_list"];
	} elseif (isset($_POST["reset_called_count_list"])) {
		$reset_called_count_list = $_POST["reset_called_count_list"];
	}
	if (isset($_GET["reset_called_count_status"])) {
		$reset_called_count_status = $_GET["reset_called_count_status"];
	} elseif (isset($_POST["reset_called_count_status"])) {
		$reset_called_count_status = $_POST["reset_called_count_status"];
	}
	if (isset($_GET["reset_called_count_lead_id"])) {
		$reset_called_count_lead_id = $_GET["reset_called_count_lead_id"];
	} elseif (isset($_POST["reset_called_count_lead_id"])) {
		$reset_called_count_lead_id = $_POST["reset_called_count_lead_id"];
	}
	if (isset($_GET["reset_called_count_count_op"])) {
		$reset_called_count_count_op = $_GET["reset_called_count_count_op"];
	} elseif (isset($_POST["reset_called_count_count_op"])) {
		$reset_called_count_count_op = $_POST["reset_called_count_count_op"];
	}
	if (isset($_GET["reset_called_count_count_num"])) {
		$reset_called_count_count_num = $_GET["reset_called_count_count_num"];
	} elseif (isset($_POST["reset_called_count_count_num"])) {
		$reset_called_count_count_num = $_POST["reset_called_count_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_reset_called_count_country_code") . " = $enable_reset_called_count_country_code | " . _QXZ("enable_reset_called_count_vendor_lead_code") . " = $enable_reset_called_count_vendor_lead_code | " . _QXZ("enable_reset_called_count_source_id") . " = $enable_reset_called_count_source_id | " . _QXZ("enable_reset_called_count_owner") . " = $enable_reset_called_count_owner | " . _QXZ("enable_reset_called_count_state") . " = $enable_reset_called_count_state | " . _QXZ("enable_reset_called_count_entry_date") . " = $enable_reset_called_count_entry_date | " . _QXZ("enable_reset_called_count_modify_date") . " = $enable_reset_called_count_modify_date | " . _QXZ("enable_reset_called_count_security_phrase") . " = $enable_reset_called_count_security_phrase | " . _QXZ("enable_reset_called_count_count") . " = $enable_reset_called_count_count | " . _QXZ("reset_called_count_country_code") . " = $reset_called_count_country_code | " . _QXZ("reset_called_count_vendor_lead_code") . " = $reset_called_count_vendor_lead_code | " . _QXZ("reset_called_count_source_id") . " = $reset_called_count_source_id | " . _QXZ("reset_called_count_owner") . " = $reset_called_count_owner | " . _QXZ("reset_called_count_state") . " = $reset_called_count_state | " . _QXZ("reset_called_count_entry_date") . " = $reset_called_count_entry_date | " . _QXZ("reset_called_count_entry_date_end") . " = $reset_called_count_entry_date_end | " . _QXZ("reset_called_count_entry_date_op") . " = $reset_called_count_entry_date_op | " . _QXZ("reset_called_count_modify_date") . " = $reset_called_count_modify_date | " . _QXZ("reset_called_count_modify_date_end") . " = $reset_called_count_modify_date_end | " . _QXZ("reset_called_count_modify_date_op") . " = $reset_called_count_modify_date_op | " . _QXZ("reset_called_count_security_phrase") . " = $reset_called_count_security_phrase | " . _QXZ("reset_called_count_list") . " = $reset_called_count_list | " . _QXZ("reset_called_count_status") . " = $reset_called_count_status | " . _QXZ("reset_called_count_count_op") . " = $reset_called_count_count_op | " . _QXZ("reset_called_count_count_num") . " = $reset_called_count_count_num | " . _QXZ("reset_called_count_lead_id") . " = $reset_called_count_lead_id</p>";
	}

	# filter out anything bad
	$enable_reset_called_count_lead_id = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_lead_id);
	$enable_reset_called_count_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_country_code);
	$enable_reset_called_count_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_vendor_lead_code);
	$enable_reset_called_count_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_source_id);
	$enable_reset_called_count_owner = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_owner);
	$enable_reset_called_count_state = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_state);
	$enable_reset_called_count_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_entry_date);
	$enable_reset_called_count_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_modify_date);
	$enable_reset_called_count_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_security_phrase);
	$enable_reset_called_count_count = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_count);
	$reset_called_count_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $reset_called_count_country_code);
	$reset_called_count_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_vendor_lead_code);
	$reset_called_count_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_source_id);
	$reset_called_count_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_owner);
	$reset_called_count_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $reset_called_count_state);
	$reset_called_count_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_entry_date);
	$reset_called_count_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_entry_date_end);
	$reset_called_count_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $reset_called_count_entry_date_op);
	$reset_called_count_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_modify_date);
	$reset_called_count_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_modify_date_end);
	$reset_called_count_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $reset_called_count_modify_date_op);
	$reset_called_count_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_security_phrase);
	$reset_called_count_status = preg_replace('/[^-_%0-9a-zA-Z\|]/', '', $reset_called_count_status);
	$reset_called_count_lead_id = preg_replace('/[^0-9]/', '', $reset_called_count_lead_id);
	$reset_called_count_list = preg_replace('/[^0-9\|]/', '', $reset_called_count_list);
	$reset_called_count_count_num = preg_replace('/[^0-9]/', '', $reset_called_count_count_num);
	$reset_called_count_count_op = preg_replace('/[^<>=]/', '', $reset_called_count_count_op);

	$reset_called_count_count_op_phrase = "";
	if ($reset_called_count_count_op == "<") {
		$reset_called_count_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($reset_called_count_count_op == "<=") {
		$reset_called_count_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($reset_called_count_count_op == ">") {
		$reset_called_count_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($reset_called_count_count_op == ">=") {
		$reset_called_count_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the reset_called_count_entry_date operation phrase
	$reset_called_count_entry_date_op_phrase = "";
	$reset_called_count_entry_operator_a = '>=';
	$reset_called_count_entry_operator_b = '<=';
	if ($reset_called_count_entry_date_op == "<") {
		$reset_called_count_entry_operator_a = '>=';
		$reset_called_count_entry_operator_b = '<';
		$reset_called_count_entry_date_end = $reset_called_count_entry_date;
		$reset_called_count_entry_date = '0000-00-00 00:00:00';
		$reset_called_count_entry_date_op_phrase = _QXZ("less than") . " $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == "<=") {
		$reset_called_count_entry_date_end = $reset_called_count_entry_date;
		$reset_called_count_entry_date = '0000-00-00 00:00:00';
		$reset_called_count_entry_date_op_phrase = _QXZ("less than or equal to") . " $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == ">") {
		$reset_called_count_entry_operator_a = '>';
		$reset_called_count_entry_operator_b = '<';
		$reset_called_count_entry_date_end = '2100-00-00 00:00:00';
		$reset_called_count_entry_date_op_phrase = _QXZ("greater than") . " $reset_called_count_entry_date";
	} elseif ($reset_called_count_entry_date_op == ">=") {
		$reset_called_count_entry_date_end = '2100-00-00 00:00:00';
		$reset_called_count_entry_date_op_phrase = _QXZ("greater than or equal to") . " $reset_called_count_entry_date";
	} elseif ($reset_called_count_entry_date_op == "range") {
		$reset_called_count_entry_date_op_phrase = _QXZ("range") . " $reset_called_count_entry_date - $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == "=") {
		$reset_called_count_entry_date_end = $reset_called_count_entry_date;
		$reset_called_count_entry_date_op_phrase = _QXZ("equal to") . " $reset_called_count_entry_date";
	}

	# build the reset_called_count_modify_date operation phrase
	$reset_called_count_modify_date_op_phrase = "";
	$reset_called_count_modify_operator_a = '>=';
	$reset_called_count_modify_operator_b = '<=';
	if ($reset_called_count_modify_date_op == "<") {
		$reset_called_count_modify_operator_a = '>=';
		$reset_called_count_modify_operator_b = '<';
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date = '0000-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("less than") . " $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == "<=") {
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date = '0000-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("less than or equal to") . " $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == ">") {
		$reset_called_count_modify_operator_a = '>';
		$reset_called_count_modify_operator_b = '<';
		$reset_called_count_modify_date_end = '2100-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("greater than") . " $reset_called_count_modify_date";
	} elseif ($reset_called_count_modify_date_op == ">=") {
		$reset_called_count_modify_date_end = '2100-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("greater than or equal to") . " $reset_called_count_modify_date";
	} elseif ($reset_called_count_modify_date_op == "range") {
		$reset_called_count_modify_date_op_phrase = _QXZ("range") . " $reset_called_count_modify_date - $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == "=") {
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date_op_phrase = _QXZ("equal to") . " $reset_called_count_modify_date";
	}

	if (strlen($reset_called_count_entry_date) == 10) {
		$reset_called_count_entry_date .= " 00:00:00";
	}
	if (strlen($reset_called_count_entry_date_end) == 10) {
		$reset_called_count_entry_date_end .= " 23:59:59";
	}
	if (strlen($reset_called_count_modify_date) == 10) {
		$reset_called_count_modify_date .= " 00:00:00";
	}
	if (strlen($reset_called_count_modify_date_end) == 10) {
		$reset_called_count_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_reset_called_count_country_code") . " = $enable_reset_called_count_country_code | " . _QXZ("enable_reset_called_count_vendor_lead_code") . " = $enable_reset_called_count_vendor_lead_code | " . _QXZ("enable_reset_called_count_source_id") . " = $enable_reset_called_count_source_id | " . _QXZ("enable_reset_called_count_owner") . " = $enable_reset_called_count_owner | " . _QXZ("enable_reset_called_count_state") . " = $enable_reset_called_count_state | " . _QXZ("enable_reset_called_count_entry_date") . " = $enable_reset_called_count_entry_date | " . _QXZ("enable_reset_called_count_modify_date") . " = $enable_reset_called_count_modify_date | " . _QXZ("enable_reset_called_count_security_phrase") . " = $enable_reset_called_count_security_phrase | " . _QXZ("enable_reset_called_count_count") . " = $enable_reset_called_count_count | " . _QXZ("reset_called_count_country_code") . " = $reset_called_count_country_code | " . _QXZ("reset_called_count_vendor_lead_code") . " = $reset_called_count_vendor_lead_code | " . _QXZ("reset_called_count_source_id") . " = $reset_called_count_source_id | " . _QXZ("reset_called_count_owner") . " = $reset_called_count_owner | " . _QXZ("reset_called_count_state") . " = $reset_called_count_state | " . _QXZ("reset_called_count_entry_date") . " = $reset_called_count_entry_date | " . _QXZ("reset_called_count_entry_date_end") . " = $reset_called_count_entry_date_end | " . _QXZ("reset_called_count_entry_date_op") . " = $reset_called_count_entry_date_op | " . _QXZ("reset_called_count_modify_date") . " = $reset_called_count_modify_date | " . _QXZ("reset_called_count_modify_date_end") . " = $reset_called_count_modify_date_end | " . _QXZ("reset_called_count_modify_date_op") . " = $reset_called_count_modify_date_op | " . _QXZ("reset_called_count_security_phrase") . " = $reset_called_count_security_phrase | " . _QXZ("reset_called_count_list") . " = $reset_called_count_list | " . _QXZ("reset_called_count_status") . " = $reset_called_count_status | " . _QXZ("reset_called_count_count_op") . " = $reset_called_count_count_op | " . _QXZ("reset_called_count_count_num") . " = $reset_called_count_count_num | " . _QXZ("reset_called_count_lead_id") . " = $reset_called_count_lead_id</p>";
	}

	# make sure the required fields are set
	if ($reset_called_count_status == '') {
		missing_required_field('Status');
	}
	if ($reset_called_count_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the reset called count phrase
	$sql_where = "";
	if ($reset_called_count_status != '---ALL---') {
		$sql_where = $sql_where . " and status IN('" . implode("', '", $reset_called_count_status) . "') ";
	}
	$reset_called_count_parm = "";
	$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " " . implode(" ", $reset_called_count_status) . " <br />";
	if (($enable_reset_called_count_lead_id == "enabled") && ($reset_called_count_lead_id != '')) {
		if ($reset_called_count_lead_id == '---BLANK---') {
			$reset_called_count_lead_id = '';
		}
		$sql_where = $sql_where . " and lead_id like '$reset_called_count_lead_id' ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("lead ID is like") . " $reset_called_count_lead_id<br />";
		if ($reset_called_count_lead_id == '') {
			$reset_called_count_lead_id = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_lead_id == "enabled") {
		blank_field('Lead ID', true);
	}
	if (($enable_reset_called_count_country_code == "enabled") && ($reset_called_count_country_code != '')) {
		if ($reset_called_count_country_code == '---BLANK---') {
			$reset_called_count_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$reset_called_count_country_code\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $reset_called_count_country_code<br />";
		if ($reset_called_count_country_code == '') {
			$reset_called_count_country_code = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_reset_called_count_vendor_lead_code == "enabled") && ($reset_called_count_vendor_lead_code != '')) {
		if ($reset_called_count_vendor_lead_code == '---BLANK---') {
			$reset_called_count_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$reset_called_count_vendor_lead_code\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $reset_called_count_vendor_lead_code<br />";
		if ($reset_called_count_vendor_lead_code == '') {
			$reset_called_count_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_reset_called_count_source_id == "enabled") && ($reset_called_count_source_id != '')) {
		if ($reset_called_count_source_id == '---BLANK---') {
			$reset_called_count_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$reset_called_count_source_id\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id code is like") . " $reset_called_count_source_id<br />";
		if ($reset_called_count_source_id == '') {
			$reset_called_count_source_id = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_reset_called_count_security_phrase == "enabled") && ($reset_called_count_security_phrase != '')) {
		if ($reset_called_count_security_phrase == '---BLANK---') {
			$reset_called_count_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$reset_called_count_security_phrase\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $reset_called_count_security_phrase<br />";
		if ($reset_called_count_security_phrase == '') {
			$reset_called_count_security_phrase = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_reset_called_count_owner == "enabled") && ($reset_called_count_owner != '')) {
		if ($reset_called_count_owner == '---BLANK---') {
			$reset_called_count_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$reset_called_count_owner\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $reset_called_count_owner<br />";
		if ($reset_called_count_owner == '') {
			$reset_called_count_owner = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_reset_called_count_state == "enabled") && ($reset_called_count_state != '')) {
		if ($reset_called_count_state == '---BLANK---') {
			$reset_called_count_state = '';
		}
		$sql_where = $sql_where . " and state like \"$reset_called_count_state\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $reset_called_count_state<br />";
		if ($reset_called_count_state == '') {
			$reset_called_count_state = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_reset_called_count_entry_date == "enabled") && ($reset_called_count_entry_date != '')) {
		if ($reset_called_count_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $reset_called_count_entry_operator_a '$reset_called_count_entry_date' and entry_date $reset_called_count_entry_operator_b '$reset_called_count_entry_date_end' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $reset_called_count_entry_date_op_phrase<br />";
		}
	} elseif ($enable_reset_called_count_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_reset_called_count_modify_date == "enabled") && ($reset_called_count_modify_date != '')) {
		if ($reset_called_count_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $reset_called_count_modify_operator_a '$reset_called_count_modify_date' and modify_date $reset_called_count_modify_operator_b '$reset_called_count_modify_date_end' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $reset_called_count_modify_date_op_phrase<br />";
		}
	} elseif ($enable_reset_called_count_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_reset_called_count_count == "enabled") && ($reset_called_count_count_op != '') && ($reset_called_count_count_num != '')) {
		if ($reset_called_count_count_op == '---BLANK---') {
			$reset_called_count_count_op = '';
		}
		if ($reset_called_count_count_num == '---BLANK---') {
			$reset_called_count_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $reset_called_count_count_op $reset_called_count_count_num";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $reset_called_count_count_op_phrase $reset_called_count_count_num<br />";
		if ($reset_called_count_count_op == '') {
			$reset_called_count_count_op = '---BLANK---';
		}
		if ($reset_called_count_count_num == '') {
			$reset_called_count_count_num = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_count == "enabled") {
		blank_field('Move Count', true);
	}

	# get the number of leads this action will move
	$reset_called_count_lead_count = 0;
	$reset_called_count_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id IN('" . implode("','", $reset_called_count_list) . "') $sql_where";
	if ($DB) {
		echo "|$reset_called_count_lead_count_stmt|\n";
	}
	$reset_called_count_lead_count_rslt = mysql_to_mysqli($reset_called_count_lead_count_stmt, $link);
	$reset_called_count_lead_count_row = mysqli_fetch_row($reset_called_count_lead_count_rslt);
	$reset_called_count_lead_count = $reset_called_count_lead_count_row[0];

	echo "<p>" . _QXZ("You are about to reset to a called_count of zero -0-") . ", <B>$reset_called_count_lead_count</B> " . _QXZ("leads in list") . " " . implode(",", $reset_called_count_list) . " " . _QXZ("with the following parameters") . ":<br /><br />$reset_called_count_parm<br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_reset_called_count_lead_id value='$enable_reset_called_count_lead_id'>\n";
	echo "<input type=hidden name=enable_reset_called_count_country_code value='$enable_reset_called_count_country_code'>\n";
	echo "<input type=hidden name=enable_reset_called_count_vendor_lead_code value='$enable_reset_called_count_vendor_lead_code'>\n";
	echo "<input type=hidden name=enable_reset_called_count_source_id value='$enable_reset_called_count_source_id'>\n";
	echo "<input type=hidden name=enable_reset_called_count_owner value='$enable_reset_called_count_owner'>\n";
	echo "<input type=hidden name=enable_reset_called_count_state value='$enable_reset_called_count_state'>\n";
	echo "<input type=hidden name=enable_reset_called_count_entry_date value='$enable_reset_called_count_entry_date'>\n";
	echo "<input type=hidden name=enable_reset_called_count_modify_date value='$enable_reset_called_count_modify_date'>\n";
	echo "<input type=hidden name=enable_reset_called_count_security_phrase value='$enable_reset_called_count_security_phrase'>\n";
	echo "<input type=hidden name=enable_reset_called_count_count value='$enable_reset_called_count_count'>\n";
	echo "<input type=hidden name=reset_called_count_country_code value=\"$reset_called_count_country_code\">\n";
	echo "<input type=hidden name=reset_called_count_vendor_lead_code value=\"$reset_called_count_vendor_lead_code\">\n";
	echo "<input type=hidden name=reset_called_count_source_id value=\"$reset_called_count_source_id\">\n";
	echo "<input type=hidden name=reset_called_count_owner value=\"$reset_called_count_owner\">\n";
	echo "<input type=hidden name=reset_called_count_state value=\"$reset_called_count_state\">\n";
	echo "<input type=hidden name=reset_called_count_entry_date value=\"$reset_called_count_entry_date\">\n";
	echo "<input type=hidden name=reset_called_count_entry_date_end value=\"$reset_called_count_entry_date_end\">\n";
	echo "<input type=hidden name=reset_called_count_entry_date_op value=\"$reset_called_count_entry_date_op\">\n";
	echo "<input type=hidden name=reset_called_count_modify_date value=\"$reset_called_count_modify_date\">\n";
	echo "<input type=hidden name=reset_called_count_modify_date_end value=\"$reset_called_count_modify_date_end\">\n";
	echo "<input type=hidden name=reset_called_count_modify_date_op value=\"$reset_called_count_modify_date_op\">\n";
	echo "<input type=hidden name=reset_called_count_security_phrase value=\"$reset_called_count_security_phrase\">\n";
	echo "<input type=hidden name=reset_called_count_list value='" . implode("|", $reset_called_count_list) . "'>\n";
	echo "<input type=hidden name=reset_called_count_status value=\"" . implode("|", $reset_called_count_status) . "\">\n";
	echo "<input type=hidden name=reset_called_count_count_op value=\"$reset_called_count_count_op\">\n";
	echo "<input type=hidden name=reset_called_count_count_num value=\"$reset_called_count_count_num\">\n";
	echo "<input type=hidden name=reset_called_count_lead_id value=\"$reset_called_count_lead_id\">\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_reset_called_count value='" . _QXZ("confirm") . "'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	echo "</body>\n</html>\n";
}

# actually do the reset called count
if (($confirm_reset_called_count == _QXZ("confirm")) && ($modify_leads > 0)) {
	# get the variables
	$enable_reset_called_count_lead_id = "";
	$enable_reset_called_count_country_code = "";
	$enable_reset_called_count_vendor_lead_code = "";
	$enable_reset_called_count_source_id = "";
	$enable_reset_called_count_owner = "";
	$enable_reset_called_count_state = "";
	$enable_reset_called_count_entry_date = "";
	$enable_reset_called_count_modify_date = "";
	$enable_reset_called_count_security_phrase = "";
	$enable_reset_called_count_count = "";
	$reset_called_count_country_code = "";
	$reset_called_count_vendor_lead_code = "";
	$reset_called_count_source_id = "";
	$reset_called_count_owner = "";
	$reset_called_count_state = "";
	$reset_called_count_entry_date = "";
	$reset_called_count_entry_date_end = "";
	$reset_called_count_entry_date_op = "";
	$reset_called_count_modify_date = "";
	$reset_called_count_modify_date_end = "";
	$reset_called_count_modify_date_op = "";
	$reset_called_count_security_phrase = "";
	$reset_called_count_list = "";
	$reset_called_count_status = "";
	$reset_called_count_count_op = "";
	$reset_called_count_count_num = "";
	$reset_called_count_lead_id = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_reset_called_count_lead_id"])) {
		$enable_reset_called_count_lead_id = $_GET["enable_reset_called_count_lead_id"];
	} elseif (isset($_POST["enable_reset_called_count_lead_id"])) {
		$enable_reset_called_count_lead_id = $_POST["enable_reset_called_count_lead_id"];
	}
	if (isset($_GET["enable_reset_called_count_country_code"])) {
		$enable_reset_called_count_country_code = $_GET["enable_reset_called_count_country_code"];
	} elseif (isset($_POST["enable_reset_called_count_country_code"])) {
		$enable_reset_called_count_country_code = $_POST["enable_reset_called_count_country_code"];
	}
	if (isset($_GET["enable_reset_called_count_vendor_lead_code"])) {
		$enable_reset_called_count_vendor_lead_code = $_GET["enable_reset_called_count_vendor_lead_code"];
	} elseif (isset($_POST["enable_reset_called_count_vendor_lead_code"])) {
		$enable_reset_called_count_vendor_lead_code = $_POST["enable_reset_called_count_vendor_lead_code"];
	}
	if (isset($_GET["enable_reset_called_count_source_id"])) {
		$enable_reset_called_count_source_id = $_GET["enable_reset_called_count_source_id"];
	} elseif (isset($_POST["enable_reset_called_count_source_id"])) {
		$enable_reset_called_count_source_id = $_POST["enable_reset_called_count_source_id"];
	}
	if (isset($_GET["enable_reset_called_count_owner"])) {
		$enable_reset_called_count_owner = $_GET["enable_reset_called_count_owner"];
	} elseif (isset($_POST["enable_reset_called_count_owner"])) {
		$enable_reset_called_count_owner = $_POST["enable_reset_called_count_owner"];
	}
	if (isset($_GET["enable_reset_called_count_state"])) {
		$enable_reset_called_count_state = $_GET["enable_reset_called_count_state"];
	} elseif (isset($_POST["enable_reset_called_count_state"])) {
		$enable_reset_called_count_state = $_POST["enable_reset_called_count_state"];
	}
	if (isset($_GET["enable_reset_called_count_entry_date"])) {
		$enable_reset_called_count_entry_date = $_GET["enable_reset_called_count_entry_date"];
	} elseif (isset($_POST["enable_reset_called_count_entry_date"])) {
		$enable_reset_called_count_entry_date = $_POST["enable_reset_called_count_entry_date"];
	}
	if (isset($_GET["enable_reset_called_count_modify_date"])) {
		$enable_reset_called_count_modify_date = $_GET["enable_reset_called_count_modify_date"];
	} elseif (isset($_POST["enable_reset_called_count_modify_date"])) {
		$enable_reset_called_count_modify_date = $_POST["enable_reset_called_count_modify_date"];
	}
	if (isset($_GET["enable_reset_called_count_security_phrase"])) {
		$enable_reset_called_count_security_phrase = $_GET["enable_reset_called_count_security_phrase"];
	} elseif (isset($_POST["enable_reset_called_count_security_phrase"])) {
		$enable_reset_called_count_security_phrase = $_POST["enable_reset_called_count_security_phrase"];
	}
	if (isset($_GET["enable_reset_called_count_count"])) {
		$enable_reset_called_count_count = $_GET["enable_reset_called_count_count"];
	} elseif (isset($_POST["enable_reset_called_count_count"])) {
		$enable_reset_called_count_count = $_POST["enable_reset_called_count_count"];
	}
	if (isset($_GET["reset_called_count_country_code"])) {
		$reset_called_count_country_code = $_GET["reset_called_count_country_code"];
	} elseif (isset($_POST["reset_called_count_country_code"])) {
		$reset_called_count_country_code = $_POST["reset_called_count_country_code"];
	}
	if (isset($_GET["reset_called_count_vendor_lead_code"])) {
		$reset_called_count_vendor_lead_code = $_GET["reset_called_count_vendor_lead_code"];
	} elseif (isset($_POST["reset_called_count_vendor_lead_code"])) {
		$reset_called_count_vendor_lead_code = $_POST["reset_called_count_vendor_lead_code"];
	}
	if (isset($_GET["reset_called_count_source_id"])) {
		$reset_called_count_source_id = $_GET["reset_called_count_source_id"];
	} elseif (isset($_POST["reset_called_count_source_id"])) {
		$reset_called_count_source_id = $_POST["reset_called_count_source_id"];
	}
	if (isset($_GET["reset_called_count_owner"])) {
		$reset_called_count_owner = $_GET["reset_called_count_owner"];
	} elseif (isset($_POST["reset_called_count_owner"])) {
		$reset_called_count_owner = $_POST["reset_called_count_owner"];
	}
	if (isset($_GET["reset_called_count_state"])) {
		$reset_called_count_state = $_GET["reset_called_count_state"];
	} elseif (isset($_POST["reset_called_count_state"])) {
		$reset_called_count_state = $_POST["reset_called_count_state"];
	}
	if (isset($_GET["reset_called_count_entry_date"])) {
		$reset_called_count_entry_date = $_GET["reset_called_count_entry_date"];
	} elseif (isset($_POST["reset_called_count_entry_date"])) {
		$reset_called_count_entry_date = $_POST["reset_called_count_entry_date"];
	}
	if (isset($_GET["reset_called_count_entry_date_end"])) {
		$reset_called_count_entry_date_end = $_GET["reset_called_count_entry_date_end"];
	} elseif (isset($_POST["reset_called_count_entry_date_end"])) {
		$reset_called_count_entry_date_end = $_POST["reset_called_count_entry_date_end"];
	}
	if (isset($_GET["reset_called_count_entry_date_op"])) {
		$reset_called_count_entry_date_op = $_GET["reset_called_count_entry_date_op"];
	} elseif (isset($_POST["reset_called_count_entry_date_op"])) {
		$reset_called_count_entry_date_op = $_POST["reset_called_count_entry_date_op"];
	}
	if (isset($_GET["reset_called_count_modify_date"])) {
		$reset_called_count_modify_date = $_GET["reset_called_count_modify_date"];
	} elseif (isset($_POST["reset_called_count_modify_date"])) {
		$reset_called_count_modify_date = $_POST["reset_called_count_modify_date"];
	}
	if (isset($_GET["reset_called_count_modify_date_end"])) {
		$reset_called_count_modify_date_end = $_GET["reset_called_count_modify_date_end"];
	} elseif (isset($_POST["reset_called_count_modify_date_end"])) {
		$reset_called_count_modify_date_end = $_POST["reset_called_count_modify_date_end"];
	}
	if (isset($_GET["reset_called_count_modify_date_op"])) {
		$reset_called_count_modify_date_op = $_GET["reset_called_count_modify_date_op"];
	} elseif (isset($_POST["reset_called_count_modify_date_op"])) {
		$reset_called_count_modify_date_op = $_POST["reset_called_count_modify_date_op"];
	}
	if (isset($_GET["reset_called_count_security_phrase"])) {
		$reset_called_count_security_phrase = $_GET["reset_called_count_security_phrase"];
	} elseif (isset($_POST["reset_called_count_security_phrase"])) {
		$reset_called_count_security_phrase = $_POST["reset_called_count_security_phrase"];
	}
	if (isset($_GET["reset_called_count_list"])) {
		$reset_called_count_list = $_GET["reset_called_count_list"];
	} elseif (isset($_POST["reset_called_count_list"])) {
		$reset_called_count_list = $_POST["reset_called_count_list"];
	}
	if (isset($_GET["reset_called_count_status"])) {
		$reset_called_count_status = $_GET["reset_called_count_status"];
	} elseif (isset($_POST["reset_called_count_status"])) {
		$reset_called_count_status = $_POST["reset_called_count_status"];
	}
	if (isset($_GET["reset_called_count_lead_id"])) {
		$reset_called_count_lead_id = $_GET["reset_called_count_lead_id"];
	} elseif (isset($_POST["reset_called_count_lead_id"])) {
		$reset_called_count_lead_id = $_POST["reset_called_count_lead_id"];
	}
	if (isset($_GET["reset_called_count_count_op"])) {
		$reset_called_count_count_op = $_GET["reset_called_count_count_op"];
	} elseif (isset($_POST["reset_called_count_count_op"])) {
		$reset_called_count_count_op = $_POST["reset_called_count_count_op"];
	}
	if (isset($_GET["reset_called_count_count_num"])) {
		$reset_called_count_count_num = $_GET["reset_called_count_count_num"];
	} elseif (isset($_POST["reset_called_count_count_num"])) {
		$reset_called_count_count_num = $_POST["reset_called_count_count_num"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_reset_called_count_country_code") . " = $enable_reset_called_count_country_code | " . _QXZ("enable_reset_called_count_vendor_lead_code") . " = $enable_reset_called_count_vendor_lead_code | " . _QXZ("enable_reset_called_count_source_id") . " = $enable_reset_called_count_source_id | " . _QXZ("enable_reset_called_count_owner") . " = $enable_reset_called_count_owner | " . _QXZ("enable_reset_called_count_state") . " = $enable_reset_called_count_state | " . _QXZ("enable_reset_called_count_entry_date") . " = $enable_reset_called_count_entry_date | " . _QXZ("enable_reset_called_count_modify_date") . " = $enable_reset_called_count_modify_date | " . _QXZ("enable_reset_called_count_security_phrase") . " = $enable_reset_called_count_security_phrase | " . _QXZ("enable_reset_called_count_count") . " = $enable_reset_called_count_count | " . _QXZ("reset_called_count_country_code") . " = $reset_called_count_country_code | " . _QXZ("reset_called_count_vendor_lead_code") . " = $reset_called_count_vendor_lead_code | " . _QXZ("reset_called_count_source_id") . " = $reset_called_count_source_id | " . _QXZ("reset_called_count_owner") . " = $reset_called_count_owner | " . _QXZ("reset_called_count_state") . " = $reset_called_count_state | " . _QXZ("reset_called_count_entry_date") . " = $reset_called_count_entry_date | " . _QXZ("reset_called_count_entry_date_end") . " = $reset_called_count_entry_date_end | " . _QXZ("reset_called_count_entry_date_op") . " = $reset_called_count_entry_date_op | " . _QXZ("reset_called_count_modify_date") . " = $reset_called_count_modify_date | " . _QXZ("reset_called_count_modify_date_end") . " = $reset_called_count_modify_date_end | " . _QXZ("reset_called_count_modify_date_op") . " = $reset_called_count_modify_date_op | " . _QXZ("reset_called_count_security_phrase") . " = $reset_called_count_security_phrase | " . _QXZ("reset_called_count_list") . " = $reset_called_count_list | " . _QXZ("reset_called_count_status") . " = $reset_called_count_status | " . _QXZ("reset_called_count_count_op") . " = $reset_called_count_count_op | " . _QXZ("reset_called_count_count_num") . " = $reset_called_count_count_num | " . _QXZ("reset_called_count_lead_id") . " = $reset_called_count_lead_id</p>";
	}

	# filter out anything bad
	$enable_reset_called_count_lead_id = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_lead_id);
	$enable_reset_called_count_country_code = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_country_code);
	$enable_reset_called_count_vendor_lead_code = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_vendor_lead_code);
	$enable_reset_called_count_source_id = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_source_id);
	$enable_reset_called_count_owner = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_owner);
	$enable_reset_called_count_state = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_state);
	$enable_reset_called_count_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_entry_date);
	$enable_reset_called_count_modify_date = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_modify_date);
	$enable_reset_called_count_security_phrase = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_security_phrase);
	$enable_reset_called_count_count = preg_replace('/[^a-zA-Z]/', '', $enable_reset_called_count_count);
	$reset_called_count_country_code = preg_replace('/[^-_%a-zA-Z0-9]/', '', $reset_called_count_country_code);
	$reset_called_count_vendor_lead_code = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_vendor_lead_code);
	$reset_called_count_source_id = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_source_id);
	$reset_called_count_owner = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_owner);
	$reset_called_count_state = preg_replace('/[^-_%0-9a-zA-Z]/', '', $reset_called_count_state);
	$reset_called_count_entry_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_entry_date);
	$reset_called_count_entry_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_entry_date_end);
	$reset_called_count_entry_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $reset_called_count_entry_date_op);
	$reset_called_count_modify_date = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_modify_date);
	$reset_called_count_modify_date_end = preg_replace('/[^- \:_%0-9a-zA-Z]/', '', $reset_called_count_modify_date_end);
	$reset_called_count_modify_date_op = preg_replace('/[^<>=_0-9a-zA-Z]/', '', $reset_called_count_modify_date_op);
	$reset_called_count_security_phrase = preg_replace('/[^- _\'%0-9a-zA-Z]/', '', $reset_called_count_security_phrase);
	$reset_called_count_status = preg_replace('/[^-_%0-9a-zA-Z\|]/', '', $reset_called_count_status);
	$reset_called_count_lead_id = preg_replace('/[^0-9]/', '', $reset_called_count_lead_id);
	$reset_called_count_list = preg_replace('/[^0-9\|]/', '', $reset_called_count_list);
	$reset_called_count_count_num = preg_replace('/[^0-9]/', '', $reset_called_count_count_num);
	$reset_called_count_count_op = preg_replace('/[^<>=]/', '', $reset_called_count_count_op);

	$reset_called_count_count_op_phrase = "";
	if ($reset_called_count_count_op == "<") {
		$reset_called_count_count_op_phrase = _QXZ("less than") . " ";
	} elseif ($reset_called_count_count_op == "<=") {
		$reset_called_count_count_op_phrase = _QXZ("less than or equal to") . " ";
	} elseif ($reset_called_count_count_op == ">") {
		$reset_called_count_count_op_phrase = _QXZ("greater than") . " ";
	} elseif ($reset_called_count_count_op == ">=") {
		$reset_called_count_count_op_phrase = _QXZ("greater than or equal to") . " ";
	}

	# build the reset_called_count_entry_date operation phrase
	$reset_called_count_entry_date_op_phrase = "";
	$reset_called_count_entry_operator_a = '>=';
	$reset_called_count_entry_operator_b = '<=';
	if ($reset_called_count_entry_date_op == "<") {
		$reset_called_count_entry_operator_a = '>=';
		$reset_called_count_entry_operator_b = '<';
		$reset_called_count_entry_date_op_phrase = _QXZ("less than") . " $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == "<=") {
		$reset_called_count_entry_date_op_phrase = _QXZ("less than or equal to") . " $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == ">") {
		$reset_called_count_entry_operator_a = '>';
		$reset_called_count_entry_operator_b = '<';
		$reset_called_count_entry_date_op_phrase = _QXZ("greater than") . " $reset_called_count_entry_date";
	} elseif ($reset_called_count_entry_date_op == ">=") {
		$reset_called_count_entry_date_op_phrase = _QXZ("greater than or equal to") . " $reset_called_count_entry_date";
	} elseif ($reset_called_count_entry_date_op == "range") {
		$reset_called_count_entry_date_op_phrase = _QXZ("range") . " $reset_called_count_entry_date - $reset_called_count_entry_date_end";
	} elseif ($reset_called_count_entry_date_op == "=") {
		$reset_called_count_entry_date_op_phrase = _QXZ("equal to") . " $reset_called_count_entry_date_end";
	}

	# build the reset_called_count_modify_date operation phrase
	$reset_called_count_modify_date_op_phrase = "";
	$reset_called_count_modify_operator_a = '>=';
	$reset_called_count_modify_operator_b = '<=';
	if ($reset_called_count_modify_date_op == "<") {
		$reset_called_count_modify_operator_a = '>=';
		$reset_called_count_modify_operator_b = '<';
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date = '0000-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("less than") . " $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == "<=") {
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date = '0000-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("less than or equal to") . " $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == ">") {
		$reset_called_count_modify_operator_a = '>';
		$reset_called_count_modify_operator_b = '<';
		$reset_called_count_modify_date_end = '2100-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("greater than") . " $reset_called_count_modify_date";
	} elseif ($reset_called_count_modify_date_op == ">=") {
		$reset_called_count_modify_date_end = '2100-00-00 00:00:00';
		$reset_called_count_modify_date_op_phrase = _QXZ("greater than or equal to") . " $reset_called_count_modify_date";
	} elseif ($reset_called_count_modify_date_op == "range") {
		$reset_called_count_modify_date_op_phrase = _QXZ("range") . " $reset_called_count_modify_date - $reset_called_count_modify_date_end";
	} elseif ($reset_called_count_modify_date_op == "=") {
		$reset_called_count_modify_date_end = $reset_called_count_modify_date;
		$reset_called_count_modify_date_op_phrase = _QXZ("equal to") . " $reset_called_count_modify_date";
	}

	if (strlen($reset_called_count_entry_date) == 10) {
		$reset_called_count_entry_date .= " 00:00:00";
	}
	if (strlen($reset_called_count_entry_date_end) == 10) {
		$reset_called_count_entry_date_end .= " 23:59:59";
	}
	if (strlen($reset_called_count_modify_date) == 10) {
		$reset_called_count_modify_date .= " 00:00:00";
	}
	if (strlen($reset_called_count_modify_date_end) == 10) {
		$reset_called_count_modify_date_end .= " 23:59:59";
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_reset_called_count_country_code") . " = $enable_reset_called_count_country_code | " . _QXZ("enable_reset_called_count_vendor_lead_code") . " = $enable_reset_called_count_vendor_lead_code | " . _QXZ("enable_reset_called_count_source_id") . " = $enable_reset_called_count_source_id | " . _QXZ("enable_reset_called_count_owner") . " = $enable_reset_called_count_owner | " . _QXZ("enable_reset_called_count_state") . " = $enable_reset_called_count_state | " . _QXZ("enable_reset_called_count_entry_date") . " = $enable_reset_called_count_entry_date | " . _QXZ("enable_reset_called_count_modify_date") . " = $enable_reset_called_count_modify_date | " . _QXZ("enable_reset_called_count_security_phrase") . " = $enable_reset_called_count_security_phrase | " . _QXZ("enable_reset_called_count_count") . " = $enable_reset_called_count_count | " . _QXZ("reset_called_count_country_code") . " = $reset_called_count_country_code | " . _QXZ("reset_called_count_vendor_lead_code") . " = $reset_called_count_vendor_lead_code | " . _QXZ("reset_called_count_source_id") . " = $reset_called_count_source_id | " . _QXZ("reset_called_count_owner") . " = $reset_called_count_owner | " . _QXZ("reset_called_count_state") . " = $reset_called_count_state | " . _QXZ("reset_called_count_entry_date") . " = $reset_called_count_entry_date | " . _QXZ("reset_called_count_entry_date_end") . " = $reset_called_count_entry_date_end | " . _QXZ("reset_called_count_entry_date_op") . " = $reset_called_count_entry_date_op | " . _QXZ("reset_called_count_modify_date") . " = $reset_called_count_modify_date | " . _QXZ("reset_called_count_modify_date_end") . " = $reset_called_count_modify_date_end | " . _QXZ("reset_called_count_modify_date_op") . " = $reset_called_count_modify_date_op | " . _QXZ("reset_called_count_security_phrase") . " = $reset_called_count_security_phrase | " . _QXZ("reset_called_count_list") . " = $reset_called_count_list | " . _QXZ("reset_called_count_status") . " = $reset_called_count_status | " . _QXZ("reset_called_count_count_op") . " = $reset_called_count_count_op | " . _QXZ("reset_called_count_count_num") . " = $reset_called_count_count_num | " . _QXZ("reset_called_count_lead_id") . " = $reset_called_count_lead_id</p>";
	}

	# make sure the required fields are set
	if ($reset_called_count_status == '') {
		missing_required_field('Status');
	}
	if ($reset_called_count_list == '') {
		missing_required_field('List ID');
	}

	# build the sql query's where phrase and the reset called count phrase
	$sql_where = "";
	$reset_called_count_status_array = explode("|", $reset_called_count_status);
	if ($reset_called_count_status != '---ALL---') {
		$sql_where = $sql_where . " and status IN('" . implode("', '", $reset_called_count_status_array) . "') ";
	}
	$reset_called_count_parm = "";
	$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("status is like") . " " . implode(" ", $reset_called_count_status_array) . " <br />";
	if (($enable_reset_called_count_lead_id == "enabled") && ($reset_called_count_lead_id != '')) {
		if ($reset_called_count_lead_id == '---BLANK---') {
			$reset_called_count_lead_id = '';
		}
		$sql_where = $sql_where . " and lead_id like '$reset_called_count_lead_id' ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("lead ID is like") . " $reset_called_count_lead_id<br />";
		if ($reset_called_count_lead_id == '') {
			$reset_called_count_lead_id = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_lead_id == "enabled") {
		blank_field('Lead ID', true);
	}
	if (($enable_reset_called_count_country_code == "enabled") && ($reset_called_count_country_code != '')) {
		if ($reset_called_count_country_code == '---BLANK---') {
			$reset_called_count_country_code = '';
		}
		$sql_where = $sql_where . " and country_code like \"$reset_called_count_country_code\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("country code is like") . " $reset_called_count_country_code<br />";
		if ($reset_called_count_country_code == '') {
			$reset_called_count_country_code = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_country_code == "enabled") {
		blank_field('Country Code', true);
	}
	if (($enable_reset_called_count_vendor_lead_code == "enabled") && ($reset_called_count_vendor_lead_code != '')) {
		if ($reset_called_count_vendor_lead_code == '---BLANK---') {
			$reset_called_count_vendor_lead_code = '';
		}
		$sql_where = $sql_where . " and vendor_lead_code like \"$reset_called_count_vendor_lead_code\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("vendor lead code is like") . " $reset_called_count_vendor_lead_code<br />";
		if ($reset_called_count_vendor_lead_code == '') {
			$reset_called_count_vendor_lead_code = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_vendor_lead_code == "enabled") {
		blank_field('Vendor Lead Code', true);
	}
	if (($enable_reset_called_count_source_id == "enabled") && ($reset_called_count_source_id != '')) {
		if ($reset_called_count_source_id == '---BLANK---') {
			$reset_called_count_source_id = '';
		}
		$sql_where = $sql_where . " and source_id like \"$reset_called_count_source_id\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("source id code is like") . " $reset_called_count_source_id<br />";
		if ($reset_called_count_source_id == '') {
			$reset_called_count_source_id = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_source_id == "enabled") {
		blank_field('Source ID', true);
	}
	if (($enable_reset_called_count_security_phrase == "enabled") && ($reset_called_count_security_phrase != '')) {
		if ($reset_called_count_security_phrase == '---BLANK---') {
			$reset_called_count_security_phrase = '';
		}
		$sql_where = $sql_where . " and security_phrase like \"$reset_called_count_security_phrase\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("security phrase is like") . " $reset_called_count_security_phrase<br />";
		if ($reset_called_count_security_phrase == '') {
			$reset_called_count_security_phrase = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_security_phrase == "enabled") {
		blank_field('Security Phrase', true);
	}
	if (($enable_reset_called_count_owner == "enabled") && ($reset_called_count_owner != '')) {
		if ($reset_called_count_owner == '---BLANK---') {
			$reset_called_count_owner = '';
		}
		$sql_where = $sql_where . " and owner like \"$reset_called_count_owner\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("owner is like") . " $reset_called_count_owner<br />";
		if ($reset_called_count_owner == '') {
			$reset_called_count_owner = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_owner == "enabled") {
		blank_field('Owner', true);
	}
	if (($enable_reset_called_count_state == "enabled") && ($reset_called_count_state != '')) {
		if ($reset_called_count_state == '---BLANK---') {
			$reset_called_count_state = '';
		}
		$sql_where = $sql_where . " and state like \"$reset_called_count_state\" ";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("state is like") . " $reset_called_count_state<br />";
		if ($reset_called_count_state == '') {
			$reset_called_count_state = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_state == "enabled") {
		blank_field('State', true);
	}
	if (($enable_reset_called_count_entry_date == "enabled") && ($reset_called_count_entry_date != '')) {
		if ($reset_called_count_entry_date == '---BLANK---') {
			$sql_where = $sql_where . " and entry_date == '' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and entry_date $reset_called_count_entry_operator_a '$reset_called_count_entry_date' and entry_date $reset_called_count_entry_operator_b '$reset_called_count_entry_date_end' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry date was") . " $reset_called_count_entry_date_op_phrase<br />";
		}
	} elseif ($enable_reset_called_count_entry_date == "enabled") {
		blank_field('Entry Date', true);
	}
	if (($enable_reset_called_count_modify_date == "enabled") && ($reset_called_count_modify_date != '')) {
		if ($reset_called_count_modify_date == '---BLANK---') {
			$sql_where = $sql_where . " and modify_date == '' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("modify date is blank") . "<br />";
		} else {
			$sql_where = $sql_where . " and modify_date $reset_called_count_modify_operator_a '$reset_called_count_modify_date' and modify_date $reset_called_count_modify_operator_b '$reset_called_count_modify_date_end' ";
			$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("last modify date was") . " $reset_called_count_modify_date_op_phrase<br />";
		}
	} elseif ($enable_reset_called_count_modify_date == "enabled") {
		blank_field('Modify Date', true);
	}
	if (($enable_reset_called_count_count == "enabled") && ($reset_called_count_count_op != '') && ($reset_called_count_count_num != '')) {
		if ($reset_called_count_count_op == '---BLANK---') {
			$reset_called_count_count_op = '';
		}
		if ($reset_called_count_count_num == '---BLANK---') {
			$reset_called_count_count_num = '';
		}
		$sql_where = $sql_where . " and called_count $reset_called_count_count_op $reset_called_count_count_num";
		$reset_called_count_parm = $reset_called_count_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("called count is") . " $reset_called_count_count_op_phrase $reset_called_count_count_num<br />";
		if ($reset_called_count_count_op == '') {
			$reset_called_count_count_op = '---BLANK---';
		}
		if ($reset_called_count_count_num == '') {
			$reset_called_count_count_num = '---BLANK---';
		}
	} elseif ($enable_reset_called_count_count == "enabled") {
		blank_field('Move Count', true);
	}

	$reset_called_count_list_array = explode("|", $reset_called_count_list);
	$reset_called_count_lead_stmt = "UPDATE vicidial_list SET called_count=0 WHERE list_id IN('" . implode("', '", $reset_called_count_list_array) . "') $sql_where";
	if ($DB) {
		echo "|$reset_called_count_lead_stmt|\n";
	}
	$reset_called_count_lead_rslt = mysql_to_mysqli($reset_called_count_lead_stmt, $link);
	$reset_called_count_lead_count = mysqli_affected_rows($link);

	$reset_called_count_sentence = "<B>$reset_called_count_lead_count</B> leads reset to a called_count of zero -0- from list $reset_called_count_list with the following parameters:<br /><br />$reset_called_count_parm<br />";

	$SQL_log = "$reset_called_count_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$reset_called_count_list', event_code='ADMIN RESET CALLED COUNT LEADS', event_sql=\"$SQL_log\", event_notes=\"$reset_called_count_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$reset_called_count_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
##### END reset called count process #####


##### BEGIN callback process #####
# callback confirmation page
if ($callback_submit == _QXZ("switchcallbacks")) {
	# get the variables
	$enable_callback_entry_date = "";
	$enable_callback_callback_date = "";
	$callback_entry_start_date = "";
	$callback_entry_end_date = "";
	$callback_callback_start_date = "";
	$callback_callback_end_date = "";
	$callback_list = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_callback_entry_date"])) {
		$enable_callback_entry_date = $_GET["enable_callback_entry_date"];
	} elseif (isset($_POST["enable_callback_entry_date"])) {
		$enable_callback_entry_date = $_POST["enable_callback_entry_date"];
	}
	if (isset($_GET["enable_callback_callback_date"])) {
		$enable_callback_callback_date = $_GET["enable_callback_callback_date"];
	} elseif (isset($_POST["enable_callback_callback_date"])) {
		$enable_callback_callback_date = $_POST["enable_callback_callback_date"];
	}
	if (isset($_GET["callback_entry_start_date"])) {
		$callback_entry_start_date = $_GET["callback_entry_start_date"];
	} elseif (isset($_POST["callback_entry_start_date"])) {
		$callback_entry_start_date = $_POST["callback_entry_start_date"];
	}
	if (isset($_GET["callback_entry_end_date"])) {
		$callback_entry_end_date = $_GET["callback_entry_end_date"];
	} elseif (isset($_POST["callback_entry_end_date"])) {
		$callback_entry_end_date = $_POST["callback_entry_end_date"];
	}
	if (isset($_GET["callback_callback_start_date"])) {
		$callback_callback_start_date = $_GET["callback_callback_start_date"];
	} elseif (isset($_POST["callback_callback_start_date"])) {
		$callback_callback_start_date = $_POST["callback_callback_start_date"];
	}
	if (isset($_GET["callback_callback_end_date"])) {
		$callback_callback_end_date = $_GET["callback_callback_end_date"];
	} elseif (isset($_POST["callback_callback_end_date"])) {
		$callback_callback_end_date = $_POST["callback_callback_end_date"];
	}
	if (isset($_GET["callback_list"])) {
		$callback_list = $_GET["callback_list"];
	} elseif (isset($_POST["callback_list"])) {
		$callback_list = $_POST["callback_list"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_callback_entry_date") . " = $enable_callback_entry_date | " . _QXZ("enable_callback_callback_date") . " = $enable_callback_callback_date | " . _QXZ("callback_entry_start_date") . " = $callback_entry_start_date | " . _QXZ("callback_entry_end_date") . " = $callback_entry_end_date | " . _QXZ("callback_callback_start_date") . " = $callback_callback_start_date | " . _QXZ("callback_callback_end_date") . " = $callback_callback_end_date | " . _QXZ("callback_list") . " = $callback_list</p>";
	}

	# filter out anything bad
	$enable_callback_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_callback_entry_date);
	$enable_callback_callback_date = preg_replace('/[^a-zA-Z]/', '', $enable_callback_callback_date);
	$callback_entry_start_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_entry_start_date);
	$callback_entry_end_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_entry_end_date);
	$callback_callback_start_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_callback_start_date);
	$callback_callback_end_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_callback_end_date);
	$callback_list = preg_replace('/[^0-9\|]/', '', $callback_list);

	if ($DB) {
		echo "<p>" . _QXZ("enable_callback_entry_date") . " = $enable_callback_entry_date | " . _QXZ("enable_callback_callback_date") . " = $enable_callback_callback_date | " . _QXZ("callback_entry_start_date") . " = $callback_entry_start_date | " . _QXZ("callback_entry_end_date") . " = $callback_entry_end_date | " . _QXZ("callback_callback_start_date") . " = $callback_callback_start_date | " . _QXZ("callback_callback_end_date") . " = $callback_callback_end_date | " . _QXZ("callback_list") . " = $callback_list</p>";
	}


	# make sure the required fields are set
	if ($callback_list == '') {
		callback_list('List');
	}


	# build the sql query's where phrase and the callback phrase
	$sql_where = "";
	$callback_parm = "";



	if (($enable_callback_entry_date == "enabled") && ($callback_entry_start_date != '')) {
		$sql_where = $sql_where . " and entry_time >= '$callback_entry_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry time greater than") . " $callback_entry_start_date 00:00:00<br />";
	} elseif ($enable_callback_entry_date == "enabled") {
		blank_field('Entry Start Date', false);
	}
	if (($enable_callback_entry_date == "enabled") && ($callback_entry_end_date != '')) {
		$sql_where = $sql_where . " and entry_time <= '$callback_entry_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry time less than") . " $callback_entry_end_date 23:59:59<br />";
	} elseif ($enable_callback_entry_date == "enabled") {
		blank_field('Entry End Date', false);
	}

	if (($enable_callback_callback_date == "enabled") && ($callback_callback_start_date != '')) {
		$sql_where = $sql_where . " and callback_time >= '$callback_callback_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("callback time greater than") . " $callback_callback_start_date 00:00:00<br />";
	} elseif ($enable_callback_callback_date == "enabled") {
		blank_field('Callback Start Date', false);
	}
	if (($enable_callback_callback_date == "enabled") && ($callback_callback_end_date != '')) {
		$sql_where = $sql_where . " and callback_time <= '$callback_callback_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("callback time less than") . " $callback_callback_end_date 23:59:59<br />";
	} elseif ($enable_callback_callback_date == "enabled") {
		blank_field('Callback End Date', false);
	}


	# get the number of call backs that will be switched
	$callback_lead_count = 0;
	$callback_lead_count_stmt = "SELECT count(1) FROM vicidial_callbacks WHERE list_id IN('" . implode("','", $callback_list) . "') and recipient = 'USERONLY' $sql_where";
	if ($DB) {
		echo "|$callback_lead_count_stmt|\n";
	}
	$callback_lead_count_rslt = mysql_to_mysqli($callback_lead_count_stmt, $link);
	$callback_lead_count_row = mysqli_fetch_row($callback_lead_count_rslt);
	$callback_lead_count = $callback_lead_count_row[0];

	echo "<p>" . _QXZ("You are about to switch") . " <B>$callback_lead_count</B> " . _QXZ("call backs in list") . " " . implode(",", $callback_list) . " " . _QXZ("from USERONLY callbacks to EVERYONE callbacks with these parameters") . ":<br /><br />$callback_parm <br />" . _QXZ("Please press confirm to continue") . ".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_callback_entry_date value='$enable_callback_entry_date'>\n";
	echo "<input type=hidden name=enable_callback_callback_date value='$enable_callback_callback_date'>\n";
	echo "<input type=hidden name=callback_entry_start_date value='$callback_entry_start_date'>\n";
	echo "<input type=hidden name=callback_entry_end_date value='$callback_entry_end_date'>\n";
	echo "<input type=hidden name=callback_callback_start_date value='$callback_callback_start_date'>\n";
	echo "<input type=hidden name=callback_callback_end_date value='$callback_callback_end_date'>\n";
	echo "<input type=hidden name=callback_list value='" . implode("|", $callback_list) . "'>\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_callback value='" . _QXZ("confirm") . "'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	echo "</body>\n</html>\n";
}

# actually do the callback
if ($confirm_callback == _QXZ("confirm")) {
	# get the variables
	$enable_callback_entry_date = "";
	$enable_callback_callback_date = "";
	$callback_entry_start_date = "";
	$callback_entry_end_date = "";
	$callback_callback_start_date = "";
	$callback_callback_end_date = "";
	$callback_list = "";

	# check the get / post data for the variables
	if (isset($_GET["enable_callback_entry_date"])) {
		$enable_callback_entry_date = $_GET["enable_callback_entry_date"];
	} elseif (isset($_POST["enable_callback_entry_date"])) {
		$enable_callback_entry_date = $_POST["enable_callback_entry_date"];
	}
	if (isset($_GET["enable_callback_callback_date"])) {
		$enable_callback_callback_date = $_GET["enable_callback_callback_date"];
	} elseif (isset($_POST["enable_callback_callback_date"])) {
		$enable_callback_callback_date = $_POST["enable_callback_callback_date"];
	}
	if (isset($_GET["callback_entry_start_date"])) {
		$callback_entry_start_date = $_GET["callback_entry_start_date"];
	} elseif (isset($_POST["callback_entry_start_date"])) {
		$callback_entry_start_date = $_POST["callback_entry_start_date"];
	}
	if (isset($_GET["callback_entry_end_date"])) {
		$callback_entry_end_date = $_GET["callback_entry_end_date"];
	} elseif (isset($_POST["callback_entry_end_date"])) {
		$callback_entry_end_date = $_POST["callback_entry_end_date"];
	}
	if (isset($_GET["callback_callback_start_date"])) {
		$callback_callback_start_date = $_GET["callback_callback_start_date"];
	} elseif (isset($_POST["callback_callback_start_date"])) {
		$callback_callback_start_date = $_POST["callback_callback_start_date"];
	}
	if (isset($_GET["callback_callback_end_date"])) {
		$callback_callback_end_date = $_GET["callback_callback_end_date"];
	} elseif (isset($_POST["callback_callback_end_date"])) {
		$callback_callback_end_date = $_POST["callback_callback_end_date"];
	}
	if (isset($_GET["callback_list"])) {
		$callback_list = $_GET["callback_list"];
	} elseif (isset($_POST["callback_list"])) {
		$callback_list = $_POST["callback_list"];
	}

	if ($DB) {
		echo "<p>" . _QXZ("enable_callback_entry_date") . " = $enable_callback_entry_date | " . _QXZ("enable_callback_callback_date") . " = $enable_callback_callback_date | " . _QXZ("callback_entry_start_date") . " = $callback_entry_start_date | " . _QXZ("callback_entry_end_date") . " = $callback_entry_end_date | " . _QXZ("callback_callback_start_date") . " = $callback_callback_start_date | " . _QXZ("callback_callback_end_date") . " = $callback_callback_end_date | " . _QXZ("callback_list") . " = $callback_list</p>";
	}

	# filter out anything bad
	$enable_callback_entry_date = preg_replace('/[^a-zA-Z]/', '', $enable_callback_entry_date);
	$enable_callback_callback_date = preg_replace('/[^a-zA-Z]/', '', $enable_callback_callback_date);
	$callback_entry_start_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_entry_start_date);
	$callback_entry_end_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_entry_end_date);
	$callback_callback_start_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_callback_start_date);
	$callback_callback_end_date = preg_replace('/[^-_%0-9a-zA-Z]/', '', $callback_callback_end_date);
	$callback_list = preg_replace('/[^0-9\|]/', '', $callback_list);

	if ($DB) {
		echo "<p>" . _QXZ("enable_callback_entry_date") . " = $enable_callback_entry_date | " . _QXZ("enable_callback_callback_date") . " = $enable_callback_callback_date | " . _QXZ("callback_entry_start_date") . " = $callback_entry_start_date | " . _QXZ("callback_entry_end_date") . " = $callback_entry_end_date | " . _QXZ("callback_callback_start_date") . " = $callback_callback_start_date | " . _QXZ("callback_callback_end_date") . " = $callback_callback_end_date | " . _QXZ("callback_list") . " = $callback_list</p>";
	}


	# make sure the required fields are set
	if ($callback_list == '') {
		callback_list('List');
	}


	# build the sql query's where phrase and the callback phrase
	$sql_where = "";
	$callback_parm = "";



	if (($enable_callback_entry_date == "enabled") && ($callback_entry_start_date != '')) {
		$sql_where = $sql_where . " and entry_time >= '$callback_entry_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry time greater than") . " $callback_entry_start_date 00:00:00<br />";
	} elseif ($enable_callback_entry_date == "enabled") {
		blank_field('Entry Start Date', false);
	}
	if (($enable_callback_entry_date == "enabled") && ($callback_entry_end_date != '')) {
		$sql_where = $sql_where . " and entry_time <= '$callback_entry_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("entry time less than") . " $callback_entry_end_date 23:59:59<br />";
	} elseif ($enable_callback_entry_date == "enabled") {
		blank_field('Entry End Date', false);
	}

	if (($enable_callback_callback_date == "enabled") && ($callback_callback_start_date != '')) {
		$sql_where = $sql_where . " and callback_time >= '$callback_callback_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("callback time greater than") . " $callback_callback_start_date 00:00:00<br />";
	} elseif ($enable_callback_callback_date == "enabled") {
		blank_field('Callback Start Date', false);
	}
	if (($enable_callback_callback_date == "enabled") && ($callback_callback_end_date != '')) {
		$sql_where = $sql_where . " and callback_time <= '$callback_callback_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;" . _QXZ("callback time less than") . " $callback_callback_end_date 23:59:59<br />";
	} elseif ($enable_callback_callback_date == "enabled") {
		blank_field('Callback End Date', false);
	}

	$callback_list_array = explode("|", $callback_list);
	$callback_lead_stmt = "UPDATE vicidial_callbacks SET recipient = 'ANYONE' WHERE list_id IN('" . implode("', '", $callback_list_array) . "') and recipient = 'USERONLY' $sql_where";
	if ($DB) {
		echo "|$callback_lead_stmt|\n";
	}
	$callback_lead_rslt = mysql_to_mysqli($callback_lead_stmt, $link);
	$callback_lead_count = mysqli_affected_rows($link);

	$callback_sentence = "<B>$callback_lead_count</B> " . _QXZ("leads have been set to ANYONE callbacks from list") . " $callback_list " . _QXZ("with the following parameters") . ":<br /><br />$callback_parm <br />";

	$SQL_log = "$callback_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = preg_replace('/\"/', "'", $SQL_log);
	$admin_log_stmt = "INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$callback_from_list', event_code='ADMIN SWITCH CALLBACKS', event_sql=\"$SQL_log\", event_notes=\"$callback_sentence\";";
	if ($DB) {
		echo "|$admin_log_stmt|\n";
	}
	$admin_log_rslt = mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$callback_sentence</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
}
##### END callback process #####




##### BEGIN main page display #####
# main page display
if (
	($copy_submit != _QXZ("copy")) && ($move_submit != _QXZ("move")) && ($update_submit != _QXZ("update")) && ($delete_submit != _QXZ("delete")) && ($reset_called_count_submit != _QXZ("reset called count")) && ($callback_submit != _QXZ("switchcallbacks")) &&
	($confirm_move != _QXZ("confirm")) && ($confirm_copy != _QXZ("confirm")) && ($confirm_update != _QXZ("confirm")) && ($confirm_delete != _QXZ("confirm")) && ($confirm_reset_called_count != _QXZ("confirm")) && ($confirm_callback != _QXZ("confirm"))
) {
	# figure out which campaigns this user is allowed to work on
	$allowed_campaigns_stmt = "SELECT allowed_campaigns from vicidial_user_groups where user_group='$user_group';";
	if ($DB) {
		echo "|$allowed_campaigns_stmt|\n";
	}
	$rslt = mysql_to_mysqli($allowed_campaigns_stmt, $link);
	$allowed_campaigns_row = mysqli_fetch_row($rslt);
	$allowed_campaigns = $allowed_campaigns_row[0];
	if ($DB) {
		echo "|$allowed_campaigns|\n";
	}
	$allowed_campaigns_sql = "";
	if (preg_match("/ALL\-CAMPAIGNS/i", $allowed_campaigns)) {
		if ($DB) {
			echo "|" . _QXZ("Processing All Campaigns") . "|\n";
		}
		$campaign_id_stmt = "SELECT campaign_id FROM vicidial_campaigns";
		$campaign_id_rslt = mysql_to_mysqli($campaign_id_stmt, $link);
		$campaign_id_num_rows = mysqli_num_rows($campaign_id_rslt);
		if ($DB) {
			echo "|campaign_id_num_rows = $campaign_id_num_rows|\n";
		}
		if ($campaign_id_num_rows > 0) {
			$i = 0;
			while ($i < $campaign_id_num_rows) {
				$campaign_id_row = mysqli_fetch_row($campaign_id_rslt);
				if ($i == 0) {
					$allowed_campaigns_sql = "'$campaign_id_row[0]'";
				} else {
					$allowed_campaigns_sql = "$allowed_campaigns_sql, '$campaign_id_row[0]'";
				}
				$i++;
			}
		}
	} else {
		$allowed_campaigns_sql = preg_replace("/ -/", '', $allowed_campaigns);
		$allowed_campaigns_sql = preg_replace("/^ /", '', $allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ $/", '', $allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ /", "','", $allowed_campaigns_sql);
		$allowed_campaigns_sql = "'$allowed_campaigns_sql'";
	}

	# figure out which lists they are allowed to see
	$activeSQL = "and active = 'N'";
	if ($SSallow_manage_active_lists > 0) {
		$activeSQL = '';
	}
	$lists_stmt = "SELECT list_id, list_name FROM vicidial_lists WHERE campaign_id IN ($allowed_campaigns_sql) $activeSQL ORDER BY list_id";
	if ($DB) {
		echo "|$lists_stmt|\n";
	}

	testEcho($lists_stmt, '$lists_stmt');

	$lists_rslt = mysql_to_mysqli($lists_stmt, $link);
	$num_rows = mysqli_num_rows($lists_rslt);
	$i = 0;
	$allowed_lists_count = 0;
	while ($i < $num_rows) {
		$lists_row = mysqli_fetch_row($lists_rslt);

		# check how many leads are in the list
		$lead_count_stmt = "SELECT count(1)  FROM vicidial_list WHERE list_id = '$lists_row[0]'";
		if ($DB) {
			echo "|$lead_count_stmt|\n";
		}

		testEcho($lead_count_stmt, '$lead_count_stmt____');


		$lead_count_rslt = mysql_to_mysqli($lead_count_stmt, $link);
		$lead_count_row = mysqli_fetch_row($lead_count_rslt);
		$lead_count = $lead_count_row[0];

		# only show lists that are under the list_lead_limit
		if ($lead_count <= $list_lead_limit) {
			$list_ary[$allowed_lists_count] = $lists_row[0];
			$list_name_ary[$allowed_lists_count] = $lists_row[1];
			$list_lead_count_ary[$allowed_lists_count] = $lead_count_row[0];

			if ($allowed_lists_count == 0) {
				$allowed_lists_sql = "'$lists_row[0]'";
			} else {
				$allowed_lists_sql = "$allowed_lists_sql, '$lists_row[0]'";
			}

			$allowed_lists_count++;
		}
		$i++;
	}

	# figure out which statuses are in the lists they are allowed to look at
	$status_stmt = "SELECT DISTINCT status FROM vicidial_list WHERE list_id IN ( $allowed_lists_sql ) ORDER BY status";
	if ($DB) {
		echo "|$status_stmt|\n";
	}
	$status_rslt = mysql_to_mysqli($status_stmt, $link);
	$status_count = mysqli_num_rows($status_rslt);
	$i = 0;
	while ($i < $status_count) {
		$status_row = mysqli_fetch_row($status_rslt);
		$statuses[$i] = $status_row[0];
		$i++;
	}

	# figure out which statuses are in the lists they are allowed to look at
	$sys_status_stmt = "SELECT status FROM vicidial_statuses ORDER BY status";
	if ($DB) {
		echo "|$sys_status_stmt|\n";
	}
	$sys_status_rslt = mysql_to_mysqli($sys_status_stmt, $link);
	$sys_status_count = mysqli_num_rows($sys_status_rslt);
	$i = 0;
	while ($i < $sys_status_count) {
		$sys_status_row = mysqli_fetch_row($sys_status_rslt);
		$sys_statuses[$i] = $sys_status_row[0];
		$i++;
	}


	if ($SSallow_manage_active_lists > 0) {
		echo "<p>" . _QXZ("The following are advanced lead management tools.  They will only work on lists with less than");
	} else {
		echo "<p>" . _QXZ("The following are advanced lead management tools.  They will only work on inactive lists with less than");
	}
	echo " $list_lead_limit " . _QXZ("leads in them. This is to avoid data inconsistencies") . ".</p>";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><table width=$section_width cellspacing=3>\n";


	// testEcho($allowed_lists_count, '$allowed_lists_count_____');
	// testEcho($list_lead_count_ary, '$list_lead_count_ary__');

	##################### BEGIN COPY LEAD SECTION #######################
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Copy Leads") . "</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("From List") . "</td><td align=left>\n";
	echo "<select size=8 name=copy_from_list[] id='copy_from_list' multiple>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("To List") . "</td><td align=left>\n";
	echo "<select size=1 name=copy_to_list>\n";
	echo "<option value='-'>" . _QXZ("Select A List") . "</option>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Status") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_status' id='enable_copy_status' value='enabled'>\n";
	echo "<select size=1 name='copy_status' id='copy_status' disabled=true>\n";
	echo "<option value='-'>" . _QXZ("Select A Status") . "</option>\n";
	echo "<option value='%'>" . _QXZ("All Statuses") . "</option>\n";

	$i = 0;
	while ($i < $status_count) {
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Country Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_country_code' id='enable_copy_country_code' value='enabled'>\n";
	echo "<input type='text' name='copy_country_code' id='copy_country_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Vendor Lead Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_vendor_lead_code' id='enable_copy_vendor_lead_code' value='enabled'>\n";
	echo "<input type='text' name='copy_vendor_lead_code' id='copy_vendor_lead_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Source ID") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_source_id' id='enable_copy_source_id' value='enabled'>\n";
	echo "<input type='text' name='copy_source_id' id='copy_source_id' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Owner") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_owner' id='enable_copy_owner' value='enabled'>\n";
	echo "<input type='text' name='copy_owner' id='copy_owner' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("State") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_state' id='enable_copy_state' value='enabled'>\n";
	echo "<input type='text' name='copy_state' id='copy_state' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_entry_date' id='enable_copy_entry_date' value='enabled'>\n";
	echo "<select size=1 name=copy_entry_date_op id='copy_entry_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='copy_entry_date' id='copy_entry_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='copy_entry_date_end' id='copy_entry_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Modify Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_modify_date' id='enable_copy_modify_date' value='enabled'>\n";
	echo "<select size=1 name=copy_modify_date_op id='copy_modify_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='copy_modify_date' id='copy_modify_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='copy_modify_date_end' id='copy_modify_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Security Phrase") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_security_phrase' id='enable_copy_security_phrase' value='enabled'>\n";
	echo "<input type='text' name='copy_security_phrase' id='copy_security_phrase' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Called Count") . "</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_copy_count' id='enable_copy_count' value='enabled'>\n";
	echo "<select size=1 name=copy_count_op id='copy_count_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name=copy_count_num id='copy_count_num' disabled=true>\n";
	$i = 0;
	while ($i <= $max_count) {
		echo "<option value='$i'>$i</option>\n";
		$i++;
	}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=copy_submit value='" . _QXZ("copy") . "'></td></tr>\n";
	echo "</table></center>\n";
	##################### END COPY LEAD SECTION #######################

	# BEGIN lead move
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Move Leads") . "</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("From List") . "</td><td align=left>\n";
	echo "<select size=8 name=move_from_list[] id='move_from_list' multiple>\n";
	# echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("To List") . "</td><td align=left>\n";
	echo "<select size=1 name=move_to_list>\n";
	echo "<option value='-'>" . _QXZ("Select A List") . "</option>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Status") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_status' id='enable_move_status' value='enabled'>\n";
	echo "<select size=1 name='move_status' id='move_status' disabled=true>\n";
	echo "<option value='-'>" . _QXZ("Select A Status") . "</option>\n";
	echo "<option value='%'>" . _QXZ("All Statuses") . "</option>\n";

	$i = 0;
	while ($i < $status_count) {
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Country Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_country_code' id='enable_move_country_code' value='enabled'>\n";
	echo "<input type='text' name='move_country_code' id='move_country_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Vendor Lead Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_vendor_lead_code' id='enable_move_vendor_lead_code' value='enabled'>\n";
	echo "<input type='text' name='move_vendor_lead_code' id='move_vendor_lead_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Source ID") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_source_id' id='enable_move_source_id' value='enabled'>\n";
	echo "<input type='text' name='move_source_id' id='move_source_id' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Owner") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_owner' id='enable_move_owner' value='enabled'>\n";
	echo "<input type='text' name='move_owner' id='move_owner' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("State") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_state' id='enable_move_state' value='enabled'>\n";
	echo "<input type='text' name='move_state' id='move_state' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_entry_date' id='enable_move_entry_date' value='enabled'>\n";
	echo "<select size=1 name=move_entry_date_op id='move_entry_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='move_entry_date' id='move_entry_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='move_entry_date_end' id='move_entry_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Modify Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_modify_date' id='enable_move_modify_date' value='enabled'>\n";
	echo "<select size=1 name=move_modify_date_op id='move_modify_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='move_modify_date' id='move_modify_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='move_modify_date_end' id='move_modify_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Security Phrase") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_security_phrase' id='enable_move_security_phrase' value='enabled'>\n";
	echo "<input type='text' name='move_security_phrase' id='move_security_phrase' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Called Count") . "</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_count' id='enable_move_count' value='enabled'>\n";
	echo "<select size=1 name=move_count_op id='move_count_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name=move_count_num id='move_count_num' disabled=true>\n";
	$i = 0;
	while ($i <= $max_count) {
		echo "<option value='$i'>$i</option>\n";
		$i++;
	}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=move_submit value='" . _QXZ("move") . "'></td></tr>\n";
	echo "</table></center>\n";
	# END lead move

	# BEGIN Status Update
	echo "<br /><center><table width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Update Lead Statuses") . "</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("List") . "</td><td align=left>\n";
	echo "<select size=8 name=update_list[] id='update_list' multiple>\n";
	# echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("To Status") . "</td><td align=left>\n";
	echo "<select size=1 name=update_to_status>\n";
	echo "<option value='-'>" . _QXZ("Select A Status") . "</option>\n";

	$i = 0;
	while ($i < $sys_status_count) {
		echo "<option value='$sys_statuses[$i]'>$sys_statuses[$i]</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("From Status") . "</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_from_status' id='enable_update_from_status' value='enabled'>\n";
	echo "<select size=1 name=update_from_status id='update_from_status' disabled=true>\n";
	echo "<option value='-'>" . _QXZ("Select A Status") . "</option>\n";

	$i = 0;
	while ($i < $status_count) {
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Country Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_country_code' id='enable_update_country_code' value='enabled'>\n";
	echo "<input type='text' name='update_country_code' id='update_country_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Vendor Lead Code") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_vendor_lead_code' id='enable_update_vendor_lead_code' value='enabled'>\n";
	echo "<input type='text' name='update_vendor_lead_code' id='update_vendor_lead_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Source ID") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_source_id' id='enable_update_source_id' value='enabled'>\n";
	echo "<input type='text' name='update_source_id' id='update_source_id' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Owner") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_owner' id='enable_update_owner' value='enabled'>\n";
	echo "<input type='text' name='update_owner' id='update_owner' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("State") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_state' id='enable_update_state' value='enabled'>\n";
	echo "<input type='text' name='update_state' id='update_state' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_entry_date' id='enable_update_entry_date' value='enabled'>\n";
	echo "<select size=1 name=update_entry_date_op id='update_entry_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='update_entry_date' id='update_entry_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='update_entry_date_end' id='update_entry_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Modify Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_modify_date' id='enable_update_modify_date' value='enabled'>\n";
	echo "<select size=1 name=update_modify_date_op id='update_modify_date_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='range'>range</option>\n";
	echo "<option value='=' SELECTED>=</option>\n";
	echo "</select>\n";
	echo "<input type='text' name='update_modify_date' id='update_modify_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='update_modify_date_end' id='update_modify_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Security Phrase") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_security_phrase' id='enable_update_security_phrase' value='enabled'>\n";
	echo "<input type='text' name='update_security_phrase' id='update_security_phrase' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Called Count") . "</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_count' id='enable_update_count' value='enabled'>\n";
	echo "<select size=1 name='update_count_op' id='update_count_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name='update_count_num' id='update_count_num' disabled=true>\n";
	$i = 0;
	while ($i <= $max_count) {
		echo "<option value='$i'>$i</option>\n";
		$i++;
	}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=update_submit value='" . _QXZ("update") . "'></td></tr>\n";
	# END Status Update

	if ($delete_lists > 0) {
		# BEGIN Delete Leads
		echo "</table></center>\n";
		echo "<br /><center><table width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Delete Leads") . "</b></font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("List") . "</td><td align=left>\n";
		echo "<select size=8 name=delete_list[] id='delete_list' multiple>\n";
		# echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

		$i = 0;
		while ($i < $allowed_lists_count) {
			echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
			$i++;
		}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Status") . "</td><td align=left>\n";
		echo "<select size=1 name=delete_status>\n";
		echo "<option value='-'>" . _QXZ("Select A Status") . "</option>\n";

		$i = 0;
		while ($i < $status_count) {
			echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
			$i++;
		}

		echo "<option value='---ALL---'>" . _QXZ("-- ALL STATUSES --") . "</option>\n";
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Country Code") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_country_code' id='enable_delete_country_code' value='enabled'>\n";
		echo "<input type='text' name='delete_country_code' id='delete_country_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Vendor Lead Code") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_vendor_lead_code' id='enable_delete_vendor_lead_code' value='enabled'>\n";
		echo "<input type='text' name='delete_vendor_lead_code' id='delete_vendor_lead_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Source ID") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_source_id' id='enable_delete_source_id' value='enabled'>\n";
		echo "<input type='text' name='delete_source_id' id='delete_source_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Owner") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_owner' id='enable_delete_owner' value='enabled'>\n";
		echo "<input type='text' name='delete_owner' id='delete_owner' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("State") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_state' id='enable_delete_state' value='enabled'>\n";
		echo "<input type='text' name='delete_state' id='delete_state' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_entry_date' id='enable_delete_entry_date' value='enabled'>\n";
		echo "<select size=1 name=delete_entry_date_op id='delete_entry_date_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='range'>range</option>\n";
		echo "<option value='=' SELECTED>=</option>\n";
		echo "</select>\n";
		echo "<input type='text' name='delete_entry_date' id='delete_entry_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='delete_entry_date_end' id='delete_entry_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Modify Date") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_modify_date' id='enable_delete_modify_date' value='enabled'>\n";
		echo "<select size=1 name=delete_modify_date_op id='delete_modify_date_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='range'>range</option>\n";
		echo "<option value='=' SELECTED>=</option>\n";
		echo "</select>\n";
		echo "<input type='text' name='delete_modify_date' id='delete_modify_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='delete_modify_date_end' id='delete_modify_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Security Phrase") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_security_phrase' id='enable_delete_security_phrase' value='enabled'>\n";
		echo "<input type='text' name='delete_security_phrase' id='delete_security_phrase' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Lead ID") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_lead_id' id='enable_delete_lead_id' value='enabled'>\n";
		echo "<input type='text' name='delete_lead_id' id='delete_lead_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Called Count") . "</td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_count' id='enable_delete_count' value='enabled'>\n";
		echo "<select size=1 name='delete_count_op' id='delete_count_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='='>=</option>\n";
		echo "</select>\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<select size=1 name='delete_count_num' id='delete_count_num' disabled=true>\n";
		$i = 0;
		while ($i <= $max_count) {
			echo "<option value='$i'>$i</option>\n";
			$i++;
		}
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=delete_submit value='" . _QXZ("delete") . "'></td></tr>\n";
		# END Delete Leads
	}

	if ($modify_leads > 0) {
		# BEGIN Reset Leads called_count
		echo "</table></center>\n";
		echo "<br /><center><table width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Reset Leads Called Count to Zero") . "</b></font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("List") . "</td><td align=left>\n";
		echo "<select size=8 name=reset_called_count_list[] id='reset_called_count_list' multiple>\n";
		# echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

		$i = 0;
		while ($i < $allowed_lists_count) {
			echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
			$i++;
		}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Status") . "</td><td align=left>\n";
		echo "<select size=8 name=reset_called_count_status[] id='reset_called_count_status' multiple>\n";
		#echo "<option value='-'>"._QXZ("Select A Status")."</option>\n";

		$i = 0;
		while ($i < $status_count) {
			echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
			$i++;
		}

		#echo "<option value='---ALL---'>"._QXZ("-- ALL STATUSES --")."</option>\n";
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Country Code") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_country_code' id='enable_reset_called_count_country_code' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_country_code' id='reset_called_count_country_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Vendor Lead Code") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_vendor_lead_code' id='enable_reset_called_count_vendor_lead_code' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_vendor_lead_code' id='reset_called_count_vendor_lead_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Source ID") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_source_id' id='enable_reset_called_count_source_id' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_source_id' id='reset_called_count_source_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Owner") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_owner' id='enable_reset_called_count_owner' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_owner' id='reset_called_count_owner' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("State") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_state' id='enable_reset_called_count_state' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_state' id='reset_called_count_state' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_entry_date' id='enable_reset_called_count_entry_date' value='enabled'>\n";
		echo "<select size=1 name=reset_called_count_entry_date_op id='reset_called_count_entry_date_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='range'>range</option>\n";
		echo "<option value='=' SELECTED>=</option>\n";
		echo "</select>\n";
		echo "<input type='text' name='reset_called_count_entry_date' id='reset_called_count_entry_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='reset_called_count_entry_date_end' id='reset_called_count_entry_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Modify Date") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_modify_date' id='enable_reset_called_count_modify_date' value='enabled'>\n";
		echo "<select size=1 name=reset_called_count_modify_date_op id='reset_called_count_modify_date_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='range'>range</option>\n";
		echo "<option value='=' SELECTED>=</option>\n";
		echo "</select>\n";
		echo "<input type='text' name='reset_called_count_modify_date' id='reset_called_count_modify_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " <input type='text' name='reset_called_count_modify_date_end' id='reset_called_count_modify_date_end' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD) " . _QXZ("or") . " (YYYY-MM-DD HH:MM:SS)</font>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Security Phrase") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_security_phrase' id='enable_reset_called_count_security_phrase' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_security_phrase' id='reset_called_count_security_phrase' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Lead ID") . "</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_lead_id' id='enable_reset_called_count_lead_id' value='enabled'>\n";
		echo "<input type='text' name='reset_called_count_lead_id' id='reset_called_count_lead_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Called Count") . "</td><td align=left>\n";
		echo "<input type='checkbox' name='enable_reset_called_count_count' id='enable_reset_called_count_count' value='enabled'>\n";
		echo "<select size=1 name='reset_called_count_count_op' id='reset_called_count_count_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='='>=</option>\n";
		echo "</select>\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<select size=1 name='reset_called_count_count_num' id='reset_called_count_count_num' disabled=true>\n";
		$i = 0;
		while ($i <= $max_count) {
			echo "<option value='$i'>$i</option>\n";
			$i++;
		}
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=reset_called_count_submit value='" . _QXZ("reset called count") . "'></td></tr>\n";
		# END Reset Leads called_count
	}

	# BEGIN Callback Convert
	echo "</table></center>\n";
	echo "<br /><center><table width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>" . _QXZ("Switch Callbacks") . "</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("List") . "</td><td align=left>\n";
	echo "<select size=8 name=callback_list[] id='callback_list' multiple>\n";
	# echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ($i < $allowed_lists_count) {
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] " . _QXZ("leads") . ")</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Entry Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_callback_entry_date' id='enable_callback_entry_date' value='enabled'>\n";
	echo "<input type='text' name='callback_entry_start_date' id='callback_entry_start_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " ";
	echo "<input type='text' name='callback_entry_end_date' id='callback_entry_end_date' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>" . _QXZ("Callback Date") . "</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_callback_callback_date' id='enable_callback_callback_date' value='enabled'>\n";
	echo "<input type='text' name='callback_callback_start_date' id='callback_callback_start_date' value='' disabled=true length=20 maxlength=19> " . _QXZ("to") . " ";
	echo "<input type='text' name='callback_callback_end_date' id='callback_callback_end_date' value='' disabled=true length=20 maxlength=19> <font size=1>(YYYY-MM-DD)</font>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=callback_submit value='" . _QXZ("switch callbacks") . "'></td></tr>\n";
	# END Callback Convert


	echo "</table></center>\n";
	echo "</form>\n";
	echo "</body></html>\n";
}

echo "</td></tr></table>\n";
echo "<br><center><FONT STYLE=\"font-family:HELVETICA;font-size:9;color:black;\"><br><br><!-- RUNTIME: $RUNtime seconds<BR> -->";
echo _QXZ("VERSION") . ": $version &nbsp; &nbsp; ";
echo _QXZ("BUILD") . ": $build\n";
echo "</FONT></center>";
##### END main page display #####


##### BEGIN functions #####
function blank_field($field_name, $allow_blank)
{
	echo "<p>$field_name " . _QXZ("cannot be blank") . ". ";
	if ($allow_blank) {
		echo _QXZ("If you wish to search for an empty field use ---BLANK--- instead") . ".</p>";
	}
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	exit();
}

function missing_required_field($field_name)
{
	echo "<p>" . _QXZ("The field") . " '$field_name' " . _QXZ("must have a value") . ".</p>";
	echo "<p><a href='$PHP_SELF$DBlink'>" . _QXZ("Click here to start over") . ".</a></p>\n";
	exit();
}
##### END functions #####

?>