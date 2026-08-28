<?php
$response = file_get_contents('http://localhost/monitoring-defect/public/api/dashboard/stats');
echo $response;
