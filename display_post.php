<?php
echo '
<html>
<body>
<h1>SERVER</h1>
<table>
<thead>
<th>Key</th>
<th>Value</th>
</thead>
<tbody>';
foreach ($_SERVER as $key => $value) {
	echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
}

echo '</tbody>
</table>

<h1>REQUEST</h1>
<table>
<thead>
<th>Key</th>
<th>Value</th>
</thead>
<tbody>';
foreach ($_REQUEST as $key => $value) {
	echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
}

echo '</tbody>
</table>


</body>
</html>';


