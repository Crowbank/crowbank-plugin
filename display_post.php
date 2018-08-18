<?php
echo '
<html>
<head>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body>

<div class="w3-container">';

$cart = $_REQUEST['cartId'];
$amount = $_REQUEST['cost'];

preg_match_all('/PBL-(\d+)/', $cart, $matches);

$bk_no = $matches[1][0];
echo 'cartId: ' . $cart . '<br>';
echo 'amount: ' . $amount . '<br>';
echo 'bk_no: ' . $bk_no . '<br>';

echo '</div>

<div class="w3-container">
<h1>SERVER</h1>
<table class="w3-table w3-striped w3-border">
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
</div>

<div class="w3-container">
<h1>REQUEST</h1>
<table class="w3-table w3-striped w3-border">
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
</div>

</body>
</html>';


