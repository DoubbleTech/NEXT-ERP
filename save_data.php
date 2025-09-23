<?php
header('Content-Type: application/json');
echo json_encode(array('success' => true, 'message' => 'Hello from server'));
exit;
?>