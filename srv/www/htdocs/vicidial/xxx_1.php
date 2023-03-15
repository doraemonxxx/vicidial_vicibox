<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>run query here</title>
</head>

<body>
	<p>Query statement</p>
	<form action="" method="post" id="formQuery" onsubmit="handleSubmit(e)">
		<textarea name="query" id="query" cols="30" rows="10"></textarea>
		<input id="submitQuery" type="submit" value="Submit">
	</form>

</body>

<script type="text/javascript">
	function jsFunction() {
		alert('Execute Javascript Function Through PHP');
	}
	// get submitQuery button element
	var formQuery = document.getElementById('formQuery');
	// prevent click of the button

	console.log('formQuery__', formQuery);

	function handleSubmit(e) {
		console.log('e', e);
		e.preventDefault();
		var query = document.getElementById('query');
		var queryValue = query.value;
		var submitQuery = document.getElementById('submitQuery');
		submitQuery.value = queryValue;
		formQuery.submit();
	}
</script>

</html>