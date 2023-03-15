<?php
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


	// $vicidialListColumns = "SHOW COLUMNS FROM vicidial_list";
	// $vicidialListColumnsRes = mysql_to_mysqli($vicidialListColumns, $link);
	// while ($row = mysqli_fetch_array($result)) {
	// 	echo $row['Field'] . "<br>";
	// }


	// $copy_lead_stmt = "INSERT INTO vicidial_list SELECT * FROM vicidial_list WHERE list_id IN('" . implode("', '", $copy_from_list_array) . "') $sql_where";
	// $copy_lead_stmt = "UPDATE vicidial_list SET list_id = '$copy_to_list' WHERE list_id IN('" . implode("', '", $copy_from_list_array) . "') $sql_where";


	$select_copy_lead_stmt = "SELECT * FROM vicidial_list WHERE list_id IN('" . implode("', '", $copy_from_list_array) . "') $sql_where";


	if ($DB) {
		echo "|\n\n\n\n\n\n\|$select_copy_lead_stmt|\n\n\n\n\n\n\n";
	}
	$select_copy_lead_rslt = mysql_to_mysqli($select_copy_lead_stmt, $link);
	$sl_ct = mysqli_num_rows($select_copy_lead_rslt);
	$row = array();
	if ($sl_ct > 0) {
		$row = mysqli_fetch_row($select_copy_lead_rslt);
	}

	testEcho($row, '$row_____');

	$copy_sentence = "<B>$copy_lead_count</B> " . _QXZ("leads have been copyd from list") . " $copy_from_list " . _QXZ("to") . " $copy_to_list " . _QXZ("with the following parameters") . ":<br /><br />$copy_parm <br />";

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
