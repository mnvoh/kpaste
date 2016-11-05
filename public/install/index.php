<!DOCTYPE html>
<html>
	<head>
		<title>Installation</title>

		<script>
			function install() {
				document.getElementById("install").style.display = "none";
				document.getElementById('status').style.display = "block";
				var xhr = new XMLHttpRequest();
				var url = "<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/install/install.php";

				xhr.open("GET", url);
				xhr.onreadystatechange = function () {
					if (xhr.readyState == XMLHttpRequest.DONE) {
						if (xhr.status == 200) {
							// done
							document.getElementById('status').innerHTML = "Done: <br>" + xhr.response.toString();
						}
						else {
							// error
							document.getElementById('status').innerHTML = "Error: <br>" + xhr.status;
						}
					}
				};
				xhr.send();
			}
		</script>
	</head>

	<body>
		<button style="font-size: 32px;" id="install" onclick="install();">
			Install
		</button>

		<h1 id="status" style="display: none;">Pending</h1>
	</body>
</html>