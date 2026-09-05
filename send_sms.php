<?php
header('Content-Type: application/json');

$api_token = "ef245d325a4e34752e481be3b4fac9d8a927cd93";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$number = trim($_POST["number"] ?? "");
$message = trim($_POST["message"] ?? "");

if (empty($number) || empty($message)) {
    echo json_encode(["success" => false, "message" => "Mobile number and message are required."]);
    exit;
}

// Convert Philippine Number
$number = preg_replace('/\s+/', '', $number);
if (substr($number, 0, 1) === '+') $number = substr($number, 1);
if (substr($number, 0, 2) === '09') $number = '63' . substr($number, 1);

if (!preg_match('/^639\d{9}$/', $number)) {
    echo json_encode(["success" => false, "message" => "Invalid Philippine mobile number."]);
    exit;
}

$url = "https://www.iprogsms.com/api/v1/sms_messages";

$data = [
    "api_token"    => $api_token,
    "phone_number" => $number,
    "message"      => $message
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(["success" => false, "message" => "cURL request failed."]);
    exit;
}

$result = json_decode($response, true);

if ($http_code >= 200 && $http_code < 300 && isset($result["status"]) && $result["status"] == 200) {
    echo json_encode(["success" => true, "message" => "Emergency Alert Sent Successfully!"]);
} else {
    $err = $result["message"] ?? "Unable to send SMS.";
    echo json_encode(["success" => false, "message" => $err]);
}
?>
