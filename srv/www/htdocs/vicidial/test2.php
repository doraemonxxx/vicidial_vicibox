<?php

################################################################################
### update_log_entry - updates the status of a log entry
################################################################################
if ($function == 'update_log_entry') {
	if (strlen($source) < 2) {
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
	} else {
		if ((!preg_match("/ $function /", $api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/", $api_allowed_functions))) {
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
			exit;
		}
		$stmt = "SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 7 and active='Y';";
		$rslt = mysql_to_mysqli($stmt, $link);
		$row = mysqli_fetch_row($rslt);
		$allowed_user = $row[0];
		if ($allowed_user < 1) {
			$result = 'ERROR';
			$result_reason = "update_log_entry USER DOES NOT HAVE PERMISSION TO UPDATE LOGS";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
			exit;
		} else {
			$search_SQL = '';
			$search_ready = 0;
			if ((strlen($group) < 2) or (strlen($call_id) < 8) or (strlen($status) < 8)) {
				$stmt = "SELECT count(*) from vicidial_inbound_groups where group_id='$group';";
				$rslt = mysql_to_mysqli($stmt, $link);
				$row = mysqli_fetch_row($rslt);
				$inbound_call = $row[0];
				if ($inbound_call < 1) {
					$stmt = "SELECT count(*) from vicidial_campaigns where campaign_id='$group';";
					$rslt = mysql_to_mysqli($stmt, $link);
					$row = mysqli_fetch_row($rslt);
					$outbound_call = $row[0];
				}
				if (($inbound_call < 1) and ($outbound_call < 1)) {
					$result = 'ERROR';
					$result_reason = "update_log_entry GROUP NOT FOUND";
					$data = "$user|$group";
					echo "$result: $result_reason: $data\n";
					api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
					exit;
				} else {
					testEcho('123123', '123123');
					if (preg_match("/\./", $call_id)) {
						if ($inbound_call > 0) {
							$stmt = "SELECT lead_id,status,closecallid from vicidial_closer_log where campaign_id='$group' and uniqueid='$call_id' order by closecallid desc limit 1;";
						} else {
							$stmt = "SELECT lead_id,status,uniqueid from vicidial_log where campaign_id='$group' and uniqueid='$call_id';";
						}
					} else {
						$lead_id = substr($call_id, -10);
						$lead_id = ($lead_id + 0);

						if ($inbound_call > 0) {
							$stmt = "SELECT lead_id,status,closecallid from vicidial_closer_log where campaign_id='$group' and lead_id='$lead_id' order by closecallid desc limit 1;";
						} else {
							$stmt = "SELECT lead_id,status,uniqueid from vicidial_log where campaign_id='$group' and lead_id='$lead_id' order by call_date desc limit 1;";
						}
					}
					$rslt = mysql_to_mysqli($stmt, $link);
					$found_recs = mysqli_num_rows($rslt);
					if ($found_recs > 0) {
						$row = mysqli_fetch_row($rslt);
						$lead_id = $row[0];
						$old_status = $row[1];
						$uniqueid = $row[2];
					} else {
						$result = 'ERROR';
						$result_reason = "update_log_entry NO RECORDS FOUND";
						$data = "$user|$group|$call_id|$status";
						echo "$result: $result_reason: $data\n";
						api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
						exit;
					}

					if ($inbound_call > 0) {
						$stmt = "UPDATE vicidial_closer_log SET status='$status' where campaign_id='$group' and closecallid='$uniqueid' order by closecallid desc limit 1;";
					} else {
						$stmt = "UPDATE vicidial_log SET status='$status' where campaign_id='$group' and uniqueid='$uniqueid';";
					}
					$rslt = mysql_to_mysqli($stmt, $link);
					$update_count = mysqli_affected_rows($link);
					if ($update_count > 0) {
						$result = 'SUCCESS';
						$result_reason = "update_log_entry RECORD HAS BEEN UPDATED";
						$data = "$user|$group|$call_id|$status|$old_status|$uniqueid|$affected_rows";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
					} else {
						$result = 'ERROR';
						$result_reason = "update_log_entry NO RECORDS UPDATED";
						$data = "$user|$group|$call_id|$status|$old_status|$uniqueid";
						echo "$result: $result_reason: $data\n";
						api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
						exit;
					}
				}
			} else {
				$result = 'ERROR';
				$result_reason = "update_log_entry INVALID SEARCH PARAMETERS";
				$data = "$user|$group|$call_id|$status";
				echo "$result: $result_reason: $data\n";
				api_log($link, $api_logging, $api_script, $user, $agent_user, $function, $value, $result, $result_reason, $source, $data);
				exit;
			}
		}
	}
	exit;
}
		################################################################################
		### END update_log_entry
		################################################################################