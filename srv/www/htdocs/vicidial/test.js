var list_id_ary = ['545', '546', '998', '999'];
var list_name_ary = ['test', 'test_test', 'Default Manual list', 'Default inbound list'];
var campaign_id_ary = ['demo1', 'demo1', 'TESTCAMP', 'TESTCAMP'];


function LoadLists(FromBox) {
	console.log('DIPOTA NGA CODE MANGO2x');
	if (!FromBox) {
		alert("NO");
		return false;
	}
	var selectedCampaigns = "|";
	var selectedcamps = new Array();



	for (i = 0; i < document.getElementById('group').options.length; i++) {
		if (document.getElementById('group').options[i].selected) {
			selectedCampaigns += document.getElementById('group').options[i].value + "|";
		}
	}

	// Clear List menu
	document.getElementById('list_ids').options.length = 0;
	var new_list = new Option();
	new_list.value = "--ALL--";
	new_list.text = "--ALL LISTS--";
	document.getElementById('list_ids')[0] = new_list;

	list_id_index = 1;
	for (j = 0; j < campaign_id_ary.length; j++) {
		var campaignID = "/\|" + campaign_id_ary[j] + "\|/g";
		var campaign_matches = selectedCampaigns.match(campaignID);
		if (campaign_matches) {

			var new_list = new Option();
			new_list.value = list_id_ary[j];
			new_list.text = list_id_ary[j] + " - " + list_name_ary[j];
			document.getElementById('list_ids')[list_id_index] = new_list;
			list_id_index++;
		}
	}
}