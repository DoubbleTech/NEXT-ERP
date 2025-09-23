<?php
header('Content-Type: application/json');
file_put_contents('form_data.log', print_r($_POST, true));
echo json_encode(['received_data' => $_POST]);
exit;