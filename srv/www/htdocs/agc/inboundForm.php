<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style>
	#MaiNfooterspan {
		display: none;
	}

	#AgentViewLinkSpan {
		z-index: 27 !important;
	}

	.can_resize {
		width: 100%;
		resize: both;
		/** support no scroll script container */
		overflow: auto;
		border: 2px dashed #000000;
	}

	.can_resize table {
		/* width: 100% !important;
		height: inherit; */
		/* width: inherit; */
	}

	.can_resize .noscroll_script,
	.can_resize .scroll_script {
		/* width: 100%; */
		/* height: 100%; */
	}

	#ScriptPanel {
		width: auto !important;
	}

	div.container_form {
		display: flex;
		width: 100%;
		margin-top: 10px;
		padding: 20px;
		font-family: system-ui;
		background-color: #ffffff;
		border: 1px dotted #000000;
	}

	div.container_form>div:nth-child(1) {
		margin-right: 25px;
	}

	div.container_form>div {
		width: 50%;
	}

	div.container_form div.input_group {
		width: 100%;
		font-size: 15px;
	}

	div.container_form div.input_group label {
		width: 140px;
		display: inline-block;
		border: 1px solid #5d9aa3;
		/* border-radius: 4px; */
		padding: 5px;
		background-color: #00b050;
		color: #ffffff;
	}

	div.container_form input[type=text],
	div.container_form select,
	div.container_form textarea {
		padding: 5px;
		border: 1.5px solid #ccc;
		/* border-radius: 4px; */
		box-sizing: border-box;
		margin: 6px 0;
		/* margin-bottom: 16px; */
		resize: vertical;
		width: 337px;
		background-color: #e7e6e6;
	}

	@media only screen and (max-width: 800px) {

		div.container_form {
			display: block;
		}

		div.container_form>div {
			width: 100%;
		}

		div.container_form div.input_group {
			font-size: 13px;
		}

		div.container_form div.input_group label {
			width: 100px;
		}

		div.container_form input[type=text],
		div.container_form select,
		div.container_form textarea {
			width: 200px;
		}

		/* .can_resize table {
			height: 100%;
		}

		.can_resize .noscroll_script,
		.can_resize .scroll_script {
			height: 100%;
		} */

	}
</style>

<!-- <form action="" method="post" id="inboundForm"> -->
<div class="container_form">
	<div>
		<div class="input_group">
			<label for="inbound_ingroup_name">In-Group Name</label>
			<input type="text" id="inbound_ingroup_name" name="inbound_ingroup_name">
		</div>

		<div class="input_group">
			<label for="inbound_phone_number">Phone Number</label>
			<input type="text" id="inbound_phone_number" name="inbound_phone_number" disabled readonly>
		</div>

		<div class="input_group">
			<label for="inbound_first_name">First Name</label>
			<input type="text" id="inbound_first_name" name="inbound_first_name" maxlength="30">
		</div>

		<div class="input_group">
			<label for="inbound_last_name">Last Name</label>
			<input type="text" id="inbound_last_name" name="inbound_last_name" maxlength="30">
		</div>
	</div>

	<div>
		<div class="input_group">
			<label for="inbound_email">Email</label>
			<input type="text" id="inbound_email" name="inbound_email" maxlength="70">
		</div>

		<div class="input_group">
			<label for="inbound_comments">Comments</label>
			<input type="text" id="inbound_comments" name="inbound_comments">
		</div>

		<div class="input_group">
			<label for="inbound_field">Field</label>
			<input type="text" id="inbound_field" name="inbound_field">
		</div>

		<div class="input_group">
			<label for="inbound_field1">Field 1</label>
			<input type="text" id="inbound_field1" name="inbound_field1">
		</div>
	</div>
</div>
<!-- </form> -->


<script type="text/javascript">
	// for script
	// start
	const resizeContainer = document.getElementById('resizable');
	const defaultWidth = resizeContainer.offsetWidth;
	const defaultHeight = resizeContainer.offsetHeight;

	function outputsize() {
		const elWidth = resizable?.offsetWidth + 4;
		const elHeight = resizable?.offsetHeight + 4;

		if (defaultWidth != resizable.offsetWidth) {
			const tableEl = resizeContainer.getElementsByTagName('table');

			if (tableEl?.length > 0) {
				Array.from(tableEl).forEach(function(el) {
					// tables
					el.style.height = 'inherit';
					el.style.width = '100%';
				});
				const scrollScript = document.querySelector('.can_resize .scroll_script');
				const noScrollScript = document.querySelector('.can_resize .noscroll_script');

				if (scrollScript) {
					scrollScript.style.height = '100%';
					scrollScript.style.width = '100%';
				}
				if (noScrollScript) {
					noScrollScript.style.height = '100%';
					noScrollScript.style.width = '100%';
				}
			}
		}
	}

	outputsize();
	new ResizeObserver(outputsize).observe(resizable);
	// end


	// set width and height of resizable div when it comes to <= 800 of window
	// start
	window.addEventListener('resize', function(event) {
		console.log('window size width', window.innerWidth);
		console.log('window size height', window.innerHeight);
		if (window.innerWidth <= 800) {
			console.log('window width is equal to or lessthan to 800');
			const dialerLeftSideEl = document.querySelector('#MainPanel #MainTable tr:nth-child(3) td');
			const testWidth = (window.innerWidth - dialerLeftSideEl?.offsetWidth) - 50;
			const testHeight = window.innerHeight - 100;
			resizeContainer.style.width = testWidth + 'px';
			resizeContainer.style.height = testHeight + 'px';
		}
	}, true);
	// end



	// for form inputs
	// start
	// inbound form inputs
	const inboundInGroupName = document.getElementById('inbound_ingroup_name');
	const inboundPhoneNumber = document.getElementById('inbound_phone_number');
	const inboundFirstName = document.getElementById('inbound_first_name');
	const inboundLastName = document.getElementById('inbound_last_name');
	const inboundEmail = document.getElementById('inbound_email');
	const inboundComments = document.getElementById('inbound_comments');
	const inboundField = document.getElementById('inbound_field');
	const inbondField1 = document.getElementById('inbound_field1');

	// vicidial form inputs
	const viciEmail = document.getElementById('email');
	const viciFirstName = document.getElementById('first_name');
	const viciLastName = document.getElementById('last_name');
	const viciComments = document.getElementById('comments');
	const viciPhoneNumber = document.getElementById('phone_number');

	// listeners
	if (viciEmail && inboundEmail) {
		viciEmail?.addEventListener('change', function() {
			console.log('viciEmail value', viciEmail.value);
			inboundEmail.value = viciEmail.value;
		});

		inboundEmail.addEventListener('change', function() {
			console.log('inboundEmail value', inboundEmail.value);
			viciEmail.value = inboundEmail.value;
		});
	}

	if (viciFirstName && inboundFirstName) {
		viciFirstName?.addEventListener('change', function() {
			console.log('viciFirstName value', viciFirstName.value);
			inboundFirstName.value = viciFirstName.value;
		});

		inboundFirstName.addEventListener('change', function() {
			console.log('inboundFirstName value', inboundFirstName.value);
			viciFirstName.value = inboundFirstName.value;
		});
	}

	if (viciLastName && inboundLastName) {
		viciLastName?.addEventListener('change', function() {
			console.log('viciLastName value', viciLastName.value);
			inboundLastName.value = viciLastName.value;
		});

		inboundLastName.addEventListener('change', function() {
			console.log('inboundLastName value', inboundLastName.value);
			viciLastName.value = inboundLastName.value;
		});
	}

	if (viciComments && inboundComments) {
		viciComments?.addEventListener('change', function() {
			console.log('viciComments value', viciComments.value);
			inboundComments.value = viciComments.value;
		});

		inboundComments.addEventListener('change', function() {
			console.log('inboundComments value', inboundComments.value);
			viciComments.value = inboundComments.value;
		});
	}


	// end
</script>