function dialedcall_send_hangup(dispowindow,hotkeysused,altdispo,nodeletevdac,DSHclick) {
	var required_fail=0;
	if (allow_required_fields=='Y')
		{
		var required_fields_list =  document.vicidial_form.required_fields.value;
		var error_field_list='';
		if (required_fields_list.length > 4)
			{
			var regRFtitle = new RegExp("title","ig");
			var regRFfirst_name = new RegExp("first_name","ig");
			var regRFmiddle_initial = new RegExp("middle_initial","ig");
			var regRFlast_name = new RegExp("last_name","ig");
			var regRFaddress1 = new RegExp("address1","ig");
			var regRFaddress2 = new RegExp("address2","ig");
			var regRFaddress3 = new RegExp("address3","ig");
			var regRFcity = new RegExp("city","ig");
			var regRFstate = new RegExp("state","ig");
			var regRFpostal_code = new RegExp("postal_code","ig");
			var regRFprovince = new RegExp("province","ig");
			var regRFphone_code = new RegExp("phone_code","ig");
			var regRFalt_phone = new RegExp("alt_phone","ig");
			var regRFvendor_lead_code = new RegExp("vendor_lead_code","ig");
			var regRFsecurity_phrase = new RegExp("security_phrase","ig");
			var regRFemail = new RegExp("email","ig");

			if (required_fields_list.match(regRFtitle))
				{
				var TEMP_title = document.vicidial_form.title.value;
				if (TEMP_title.length < 1){required_fail++;  error_field_list = error_field_list + " title";}
				}
			if (required_fields_list.match(regRFfirst_name))
				{
				var TEMP_first_name = document.vicidial_form.first_name.value;
				if (TEMP_first_name.length < 1){required_fail++;  error_field_list = error_field_list + " first_name";}
				}
			if (required_fields_list.match(regRFmiddle_initial))
				{
				var TEMP_middle_initial = document.vicidial_form.middle_initial.value;
				if (TEMP_middle_initial.length < 1){required_fail++;  error_field_list = error_field_list + " middle_initial";}
				}
			if (required_fields_list.match(regRFlast_name))
				{
				var TEMP_last_name = document.vicidial_form.last_name.value;
				if (TEMP_last_name.length < 1){required_fail++;  error_field_list = error_field_list + " last_name";}
				}
			if (required_fields_list.match(regRFaddress1))
				{
				var TEMP_address1 = document.vicidial_form.address1.value;
				if (TEMP_address1.length < 1){required_fail++;  error_field_list = error_field_list + " address1";}
				}
			if (required_fields_list.match(regRFaddress2))
				{
				var TEMP_address2 = document.vicidial_form.address2.value;
				if (TEMP_address2.length < 1){required_fail++;  error_field_list = error_field_list + " address2";}
				}
			if (required_fields_list.match(regRFaddress3))
				{
				var TEMP_address3 = document.vicidial_form.address3.value;
				if (TEMP_address3.length < 1){required_fail++;  error_field_list = error_field_list + " address3";}
				}
			if (required_fields_list.match(regRFcity))
				{
				var TEMP_city = document.vicidial_form.city.value;
				if (TEMP_city.length < 1){required_fail++;  error_field_list = error_field_list + " city";}
				}
			if (required_fields_list.match(regRFstate))
				{
				var TEMP_state = document.vicidial_form.state.value;
				if (TEMP_state.length < 1){required_fail++;  error_field_list = error_field_list + " state";}
				}
			if (required_fields_list.match(regRFpostal_code))
				{
				var TEMP_postal_code = document.vicidial_form.postal_code.value;
				if (TEMP_postal_code.length < 1){required_fail++;  error_field_list = error_field_list + " postal_code";}
				}
			if (required_fields_list.match(regRFprovince))
				{
				var TEMP_province = document.vicidial_form.province.value;
				if (TEMP_province.length < 1){required_fail++;  error_field_list = error_field_list + " province";}
				}
			if (required_fields_list.match(regRFvendor_lead_code))
				{
				var TEMP_vendor_lead_code = document.vicidial_form.vendor_lead_code.value;
				if (TEMP_vendor_lead_code.length < 1){required_fail++;  error_field_list = error_field_list + " vendor_lead_code";}
				}
			if (required_fields_list.match(regRFphone_code))
				{
				var TEMP_phone_code = document.vicidial_form.phone_code.value;
				if (TEMP_phone_code.length < 1){required_fail++;  error_field_list = error_field_list + " phone_code";}
				}
			if (required_fields_list.match(regRFalt_phone))
				{
				var TEMP_alt_phone = document.vicidial_form.alt_phone.value;
				if (TEMP_alt_phone.length < 1){required_fail++;  error_field_list = error_field_list + " alt_phone";}
				}
			if (required_fields_list.match(regRFsecurity_phrase))
				{
				var TEMP_security_phrase = document.vicidial_form.security_phrase.value;
				if (TEMP_security_phrase.length < 1){required_fail++;  error_field_list = error_field_list + " security_phrase";}
				}
			if (required_fields_list.match(regRFemail))
				{
				var TEMP_email = document.vicidial_form.email.value;
				if (TEMP_email.length < 1){required_fail++;  error_field_list = error_field_list + " email";}
				}
			}
		if (custom_fields_enabled > 0)
			{
			var temp_custom_required = vcFormIFrame.document.form_custom_fields.custom_required.value;
			var temp_custom_required_check = vcFormIFrame.document.form_custom_fields.custom_required_check.value;
			var temp_custom_required_radio = vcFormIFrame.document.form_custom_fields.custom_required_radio.value;
			var temp_custom_required_select = vcFormIFrame.document.form_custom_fields.custom_required_select.value;
			var temp_custom_required_multi = vcFormIFrame.document.form_custom_fields.custom_required_multi.value;
		//	alert_box("checking for required custom fields: " + temp_custom_required + "-" + temp_custom_required_check + "-" + temp_custom_required_radio + "-" + temp_custom_required_select + "-" + temp_custom_required_multi + "-");

			// check TEXT, AREA and DATE required custom fields
			if (temp_custom_required.length > 2)
				{
				var CFR_array=temp_custom_required.split('|');
				var CFR_count=CFR_array.length;
				var CFR_tick=0;
				while (CFR_tick < CFR_count)
					{
					var CFR_field = CFR_array[CFR_tick];
					if (CFR_field.length > 0)
						{
						var temp_field_check = vcFormIFrame.document.getElementById(CFR_field).value;
						if (temp_field_check.length < 1){required_fail++;  error_field_list = error_field_list + " CF: " + CFR_field;}
						}
					CFR_tick++;
					}
				}
			// check CHECKBOX required custom fields
			if (temp_custom_required_check.length > 2)
				{
				var CFR_arrayC = temp_custom_required_check.split('|');
				var CFR_countC = CFR_arrayC.length;
				var CFR_tick=0;
				while (CFR_tick < CFR_countC)
					{
					var CFR_field = CFR_arrayC[CFR_tick] + '[]';
					if (CFR_field.length > 2)
						{
						var CFR_field_boxes = vcFormIFrame.document.getElementsByName(CFR_field);
						if (CFR_field_boxes.length > 0)
							{
							var CFR_len = CFR_field_boxes.length;
							var CFR_checked=0;
							for (var i=0; i < CFR_len; i++) 
								{
								if (CFR_field_boxes[i].checked) {CFR_checked++;};
								}
							if (CFR_checked < 1){required_fail++;  error_field_list = error_field_list + " CHECKBOX: " + CFR_arrayC[CFR_tick];}
							}
						}
					CFR_tick++;
					}
				}
			// check RADIO required custom fields
			if (temp_custom_required_radio.length > 2)
				{
				var CFR_arrayR = temp_custom_required_radio.split('|');
				var CFR_countR = CFR_arrayR.length;
				var CFR_tick=0;
				while (CFR_tick < CFR_countR)
					{
					var CFR_field = CFR_arrayR[CFR_tick] + '[]';
					if (CFR_field.length > 2)
						{
						var CFR_field_boxes = vcFormIFrame.document.getElementsByName(CFR_field);
						if (CFR_field_boxes.length > 0)
							{
							var CFR_len = CFR_field_boxes.length;
							var CFR_checked=0;
							for (var i=0; i < CFR_len; i++) 
								{
								if (CFR_field_boxes[i].checked) {CFR_checked++;};
								}
							if (CFR_checked < 1){required_fail++;  error_field_list = error_field_list + " RADIO: " + CFR_arrayR[CFR_tick];}
							}
						}
					CFR_tick++;
					}
				}
			// check SELECT required custom fields
			if (temp_custom_required_select.length > 2)
				{
				var CFR_arrayS = temp_custom_required_select.split('|');
				var CFR_countS = CFR_arrayS.length;
				var CFR_tick=0;
				while (CFR_tick < CFR_countS)
					{
					var CFR_field = CFR_arrayS[CFR_tick];
					if (CFR_field.length > 0)
						{
						var temp_field_check = vcFormIFrame.document.getElementById(CFR_field);
						var temp_field_check_value = temp_field_check.options[temp_field_check.selectedIndex].value;
						if (temp_field_check_value.length < 1){required_fail++;  error_field_list = error_field_list + " SELECT: " + CFR_field;}
						}
					CFR_tick++;
					}
				}
			// check MULTI required custom fields
			if (temp_custom_required_multi.length > 2)
				{
				var CFR_arrayM = temp_custom_required_multi.split('|');
				var CFR_countM = CFR_arrayM.length;
				var CFR_tick=0;
				while (CFR_tick < CFR_countM)
					{
					var CFR_field = CFR_arrayM[CFR_tick] + '[]';
					if (CFR_field.length > 2)
						{
						var CFR_field_box = vcFormIFrame.document.getElementById(CFR_field);
						var CFR_len = CFR_field_box.options.length;
						var CFR_selected=0;
						for (var i=0; i < CFR_len; i++) 
							{
							if (CFR_field_box.options[i].selected ==true) {CFR_selected++;};
							}
						if (CFR_selected < 1){required_fail++;  error_field_list = error_field_list + " MULTI: " + CFR_arrayM[CFR_tick];}
						}
					CFR_tick++;
					}
				}
			}
		}
	if (required_fail > 0)
		{
		alert_box("<?php echo _QXZ(
		 "YOU MUST FILL IN ALL REQUIRED FIELDS BEFORE YOU CAN HANG UP THIS CALL"
 ); ?>: " + error_field_list);
		button_click_log = button_click_log + "" + SQLdate + "-----required_fields_alert---" + error_field_list + "|";
		}
	else
		{
		if (VDCL_group_id.length > 1)
			{group = VDCL_group_id;}
		else
			{group = campaign;}
		var form_cust_channel = document.getElementById("callchannel").innerHTML;
		var form_cust_serverip = document.vicidial_form.callserverip.value;
		var customer_channel = lastcustchannel;
		var customer_server_ip = lastcustserverip;
		AgaiNHanguPChanneL = lastcustchannel;
		AgaiNHanguPServeR = lastcustserverip;
		AgainCalLSecondS = VD_live_call_secondS;
		AgaiNCalLCID = CalLCID;
		dial_next_failed=0;
		if (customer_sec < 1)
			{customer_sec = VD_live_call_secondS;}
		var process_post_hangup=0;

		if (DSHclick=='YES')
			{button_click_log = button_click_log + "" + SQLdate + "-----dialedcall_send_hangup---" + group + "|" + form_cust_channel + "|" + CalLCID + "|" + VD_live_call_secondS + "|";}

		// Force chat to end, if exists.  Uses hangup_override value in EndChat function to end if chat does not exist.
		if (document.getElementById('CustomerChatIFrame') && typeof document.getElementById('CustomerChatIFrame').contentWindow.EndChat=='function')
			{
			document.getElementById('CustomerChatIFrame').contentWindow.EndChat('Hangup');
			}

		if ( (RedirecTxFEr < 1) && ( (MD_channel_look==1) || (auto_dial_level == 0) ) )
			{
			MD_channel_look=0;
			DialTimeHangup('MAIN');
			}
		if (form_cust_channel.length > 3)
			{
			var xmlhttp=false;
			/*@cc_on @*/
			/*@if (@_jscript_version >= 5)
			// JScript gives us Conditional compilation, we can cope with old IE versions.
			// and security blocked creation of the objects.
			 try {
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			 } catch (e) {
				try {
				 xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (E) {
				 xmlhttp = false;
				}
			 }
			@end @*/
			if (!xmlhttp && typeof XMLHttpRequest!='undefined')
				{
				xmlhttp = new XMLHttpRequest();
				}
			if (xmlhttp) 
				{ 
				var queryCID = "HLvdcW" + epoch_sec + user_abb;
				var hangupvalue = customer_channel;
				//		alert(auto_dial_level + "|" + CalLCID + "|" + customer_server_ip + "|" + hangupvalue + "|" + VD_live_call_secondS);
				custhangup_query = "server_ip=" + server_ip + "&session_name=" + session_name + "&ACTION=Hangup&format=text&user=" + user + "&pass=" + pass + "&channel=" + hangupvalue + "&call_server_ip=" + customer_server_ip + "&queryCID=" + queryCID + "&auto_dial_level=" + auto_dial_level + "&CalLCID=" + CalLCID + "&secondS=" + VD_live_call_secondS + "&exten=" + session_id + "&campaign=" + group + "&stage=CALLHANGUP&nodeletevdac=" + nodeletevdac + "&log_campaign=" + campaign + "&qm_extension=" + qm_extension;
				xmlhttp.open('POST', 'manager_send.php'); 
				xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
				xmlhttp.send(custhangup_query); 
				xmlhttp.onreadystatechange = function() 
					{ 
					if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
						{
						Nactiveext = null;
						Nactiveext = xmlhttp.responseText;

					//		alert(xmlhttp.responseText);
					//	var HU_debug = xmlhttp.responseText;
					//	var HU_debug_array=HU_debug.split(" ");
					//	if (HU_debug_array[0] == 'Call')
					//		{
					//		alert(xmlhttp.responseText);
					//		}
						VD_live_customer_call = 0;
						agent_events('agent_hangup', '', aec);   aec++;
						}
					}
				delete xmlhttp;
				}
			process_post_hangup=1;
			}
		else 
			{process_post_hangup=1;}
		if (process_post_hangup==1)
			{
			VD_live_customer_call = 0;
			VD_live_call_secondS = 0;
			MD_ring_secondS = 0;
			CalLCID = '';
			MDnextCID = '';
			cid_lock=0;
			MD_dial_timed_out=0;
			MDcheck_for_answer=0;

		//	UPDATE VICIDIAL_LOG ENTRY FOR THIS CALL PROCESS
			DialLog("end",nodeletevdac);
			conf_dialed=0;
			if (dispowindow == 'NO')
				{
				open_dispo_screen=0;
				}
			else
				{
				if (auto_dial_level == 0)			
					{
					if (document.vicidial_form.DiaLAltPhonE.checked==true)
						{
						reselect_alt_dial = 1;
						open_dispo_screen=0;
						}
					else
						{
						reselect_alt_dial = 0;
						open_dispo_screen=1;
						}
					}
				else
					{
					if (document.vicidial_form.DiaLAltPhonE.checked==true)
						{
						reselect_alt_dial = 1;
						open_dispo_screen=0;
						auto_dial_level=0;
						manual_dial_in_progress=1;
						auto_dial_alt_dial=1;
						}
					else
						{
						reselect_alt_dial = 0;
						open_dispo_screen=1;
						}
					}
				}

		//  DEACTIVATE CHANNEL-DEPENDANT BUTTONS AND VARIABLES
			document.getElementById("callchannel").innerHTML = '';
			document.vicidial_form.callserverip.value = '';
			lastcustchannel='';
			lastcustserverip='';
			MDchannel='';
			manual_call_live=0;
			if (post_phone_time_diff_alert_message.length > 10)
				{
				document.getElementById("post_phone_time_diff_span_contents").innerHTML = "";
				hideDiv('post_phone_time_diff_span');
				post_phone_time_diff_alert_message='';
				}

			if( document.images ) { document.images['livecall'].src = image_livecall_OFF.src;}
			if (enable_first_webform > 0)
				{
				document.getElementById("WebFormSpan").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_webform_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"Web Form\" />";
				}
			if (enable_second_webform > 0)
				{
				document.getElementById("WebFormSpanTwo").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_webform_two_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"Web Form 2\" />";
				}
			if (enable_third_webform > 0)
				{
				document.getElementById("WebFormSpanThree").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_webform_three_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"Web Form 3\" />";
				}
			document.getElementById("ParkControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_LB_parkcall_OFF.gif"
	); ?>\" border=\"0\" alt=\"Park Call\" />";
			if ( (ivr_park_call=='ENABLED') || (ivr_park_call=='ENABLED_PARK_ONLY') )
				{
				document.getElementById("ivrParkControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_ivrparkcall_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"IVR Park Call\" />";
				}
			document.getElementById("HangupControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_LB_hangupcustomer_OFF.gif"
	); ?>\" border=\"0\" alt=\"Hangup Customer 07\" />";
			document.getElementById("XferControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_LB_transferconf_OFF.gif"
	); ?>\" border=\"0\" alt=\"Transfer - Conference\" />";
			document.getElementById("LocalCloser").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_XB_localcloser_OFF.gif"
	); ?>\" border=\"0\" alt=\"LOCAL CLOSER\" style=\"vertical-align:middle\" />";
			document.getElementById("DialBlindTransfer").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_XB_blindtransfer_OFF.gif"
	); ?>\" border=\"0\" alt=\"Dial Blind Transfer\" style=\"vertical-align:middle\" />";
			document.getElementById("DialBlindVMail").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_XB_ammessage_OFF.gif"
	); ?>\" border=\"0\" alt=\"Blind Transfer VMail Message\" style=\"vertical-align:middle\" />";
			document.getElementById("VolumeUpSpan").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_volume_up_off.gif"
	); ?>\" border=\"0\" />";
			document.getElementById("VolumeDownSpan").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			"vdc_volume_down_off.gif"
	); ?>\" border=\"0\" />";

			if (quick_transfer_button_enabled > 0)
				{document.getElementById("QuickXfer").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_quickxfer_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"QUICK TRANSFER\" />";}

			if (custom_3way_button_transfer_enabled > 0)
				{document.getElementById("CustomXfer").innerHTML = "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_customxfer_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"Custom Transfer\" />";}

			if (call_requeue_button > 0)
				{
				document.getElementById("ReQueueCall").innerHTML =  "<img src=\"./images/<?php echo _QXZ(
			 "vdc_LB_requeue_call_OFF.gif"
	 ); ?>\" border=\"0\" alt=\"Re-Queue Call\" />";
				}

			document.getElementById("custdatetime").innerHTML = ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';

			if ( (auto_dial_level == 0) && (dial_method != 'INBOUND_MAN') )
				{
				if (document.vicidial_form.DiaLAltPhonE.checked==true)
					{
					reselect_alt_dial = 1;
					if (altdispo == 'ALTPH2')
						{
						ManualDialOnly('ALTPhonE','NO','0');
						}
					else
						{
						if (altdispo == 'ADDR3')
							{
							ManualDialOnly('AddresS3','NO','0');
							}
						else
							{
							if (hotkeysused == 'YES')
								{
								alt_dial_active = 0;
								alt_dial_status_display = 0;
								reselect_alt_dial = 0;
								manual_auto_hotkey = 1;
								}
							}
						}
					}
				else
					{
					if (hotkeysused == 'YES')
						{
						alt_dial_active = 0;
						alt_dial_status_display = 0;
						manual_auto_hotkey = 1;
						}
					else
						{
						document.getElementById("DiaLControl").innerHTML = "<a href=\"#\" onclick=\"ManualDialNext('','','','','','0','','','YES');\"><img src=\"./images/<?php echo _QXZ(
				 "vdc_LB_dialnextnumber.gif"
		 ); ?>\" border=\"0\" alt=\"Dial Next Number\" /></a>";
						}
					reselect_alt_dial = 0;
					}
				}
			else
				{
				if (document.vicidial_form.DiaLAltPhonE.checked==true)
					{
					reselect_alt_dial = 1;
					if (altdispo == 'ALTPH2')
						{
						ManualDialOnly('ALTPhonE','NO','0');
						}
					else
						{
						if (altdispo == 'ADDR3')
							{
							ManualDialOnly('AddresS3','NO','0');
							}
						else
							{
							if (hotkeysused == 'YES')
								{
								manual_auto_hotkey = 1;
								alt_dial_active=0;
								alt_dial_status_display = 0;

								document.getElementById("MainStatuSSpan").style.background = panel_bgcolor;
								document.getElementById("MainStatuSSpan").innerHTML = '';
								if (dial_method == "INBOUND_MAN")
									{
									document.getElementById("DiaLControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
						"vdc_LB_blank_OFF.gif"
				); ?>\" border=\"0\" alt=\"pause button disabled\" /><br /><img src=\"./images/<?php echo _QXZ(
	"vdc_LB_dialnextnumber_OFF.gif"
); ?>\" border=\"0\" alt=\"Dial Next Number\" />";
									}
								else
									{
									document.getElementById("DiaLControl").innerHTML = DiaLControl_auto_HTML_OFF;
									}
								reselect_alt_dial = 0;
								}
							}
						}
					}
				else
					{
					document.getElementById("MainStatuSSpan").style.background = panel_bgcolor;
					if (dial_method == "INBOUND_MAN")
						{
						document.getElementById("DiaLControl").innerHTML = "<img src=\"./images/<?php echo _QXZ(
				 "vdc_LB_blank_OFF.gif"
		 ); ?>\" border=\"0\" alt=\"pause button disabled\" /><br /><img src=\"./images/<?php echo _QXZ(
	"vdc_LB_dialnextnumber_OFF.gif"
); ?>\" border=\"0\" alt=\"Dial Next Number\" />";
						}
					else
						{
						document.getElementById("DiaLControl").innerHTML = DiaLControl_auto_HTML_OFF;
						}
					reselect_alt_dial = 0;
					}
				}
			ShoWTransferMain('OFF');
			}
		}
	manual_entry_dial=0;
	}