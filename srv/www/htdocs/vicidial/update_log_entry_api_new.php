<?php

/**
 * NOTES: the codes are modifications for the function that is requested by the user.
 */
// need to put this at the top of the file
// start of update_agents_performace
// value must be true
if (isset($_GET["update_agents_performace"])) {
	$update_agents_performace = filter_var($_GET["update_agents_performace"], FILTER_VALIDATE_BOOLEAN);
} elseif (isset($_POST["update_agents_performace"])) {
	$update_agents_performace = filter_var($_POST["update_agents_performace"], FILTER_VALIDATE_BOOLEAN);
}
// end of update_agents_performace

// modifation of update start
# and vicidial_agent_log.uniqueid = '$uniqueid'  -- newly added to the query
if ($inbound_call > 0) {
	if ($update_agents_performace) {
		// new implementation (update both vicidial_closer_log and vicidial_agent_log)
		$stmt = "UPDATE vicidial_closer_log, vicidial_agent_log 
		SET vicidial_closer_log.status='$status', vicidial_agent_log.status='$status' 
		where 
		vicidial_closer_log.campaign_id='$group' 
		and vicidial_closer_log.closecallid='$uniqueid'
		and vicidial_agent_log.uniqueid = '$uniqueid' 
		and vicidial_agent_log.lead_id = vicidial_closer_log.lead_id 
		order by closecallid desc limit 1;";
	} else {
		$stmt = "UPDATE vicidial_closer_log SET status='$status' where campaign_id='$group' and closecallid='$uniqueid' order by closecallid desc limit 1;";
	}
} else {
	if ($update_agents_performace) {
		// new implementation (update both vicidial_log and vicidial_agent_log)
		$stmt = "UPDATE vicidial_log, vicidial_agent_log 
		SET vicidial_log.status='$status', vicidial_agent_log.status='$status' 
		where 
		vicidial_log.campaign_id='$group' 
		and vicidial_log.uniqueid='$uniqueid'
		and vicidial_agent_log.uniqueid = '$uniqueid' 
		and vicidial_agent_log.lead_id = vicidial_log.lead_id;";
	} else {
		$stmt = "UPDATE vicidial_log SET status='$status' where campaign_id='$group' and uniqueid='$uniqueid';";
	}
}
// modifation of update end