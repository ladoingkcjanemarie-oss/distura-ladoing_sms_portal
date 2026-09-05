<?php

// ============================================
// IPROG SMS CONFIGURATION
// ============================================

$api_token = "ce7dec7661f500ee7aa06ba2b1a60d0f8509836f";

// ============================================
// GET FORM DATA
// ============================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$number = trim($_POST["number"] ?? "");
$message = trim($_POST["message"] ?? "");


// ============================================
// VALIDATE INPUT
// ============================================

if (empty($number)) {
    die("Error: Mobile number is required.");
}

if (empty($message)) {
    die("Error: Emergency message is required.");
}


// ============================================
// CONVERT PHILIPPINE NUMBER
// ============================================
//
// 09171234567
// becomes
// 639171234567
//
// +639171234567
// becomes
// 639171234567
// ============================================

$number = preg_replace('/\s+/', '', $number);

if (substr($number, 0, 1) === '+') {
    $number = substr($number, 1);
}

if (substr($number, 0, 2) === '09') {
    $number = '63' . substr($number, 1);
}


// ============================================
// CHECK PHONE NUMBER FORMAT
// ============================================

if (!preg_match('/^639\d{9}$/', $number)) {
    die("Error: Invalid Philippine mobile number.");
}


// ============================================
// IPROG SMS API
// ============================================

$url = "https://iprogsms.com/api/v1/sms_messages/";

$data = [
    "api_token"   => $api_token,
    "phone_number" => $number,
    "message"     => $message
];


// ============================================
// SEND REQUEST USING CURL
// ============================================

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    http_build_query($data)
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Content-Type: application/x-www-form-urlencoded"
    ]
);

curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curl_error = curl_error($ch);

curl_close($ch);


// ============================================
// CHECK CURL ERROR
// ============================================

if ($response === false) {

    echo "
    <script>
        alert('SMS Error: " . addslashes($curl_error) . "');
        window.location.href='index.php';
    </script>
    ";

    exit;
}


// ============================================
// DECODE IPROG RESPONSE
// ============================================

$result = json_decode($response, true);


// ============================================
// SUCCESS
// ============================================

if (
    $http_code >= 200 &&
    $http_code < 300 &&
    isset($result["status"]) &&
    $result["status"] == 200
) {

    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Emergency Alert Sent</title>
        <link rel='stylesheet' href='style.css'>
    </head>

    <body>

    <div class='container'>

        <div class='alert-header'>
            <div class='alert-icon'>✓</div>

            <h1>Alert Sent Successfully</h1>

            <p>
                The emergency notification has been queued for delivery.
            </p>
        </div>

        <div style='padding:30px; text-align:center;'>

            <p>
                <strong>Recipient:</strong><br>
                $number
            </p>

            <br>

            <p>
                <strong>Message:</strong><br>
                " . htmlspecialchars($message) . "
            </p>

            <br>

            <p>
                Message ID:
                <strong>" . htmlspecialchars($result["message_id"] ?? "N/A") . "</strong>
            </p>

            <br>

            <a href='index.php'
               style='
               display:inline-block;
               padding:12px 20px;
               background:#b91c1c;
               color:white;
               text-decoration:none;
               border-radius:8px;
               '>
               Send Another Alert
            </a>

        </div>

    </div>

    </body>
    </html>
    ";

} else {

    // ========================================
    // ERROR
    // ========================================

    $error_message = "Unable to send SMS.";

    if (isset($result["message"])) {
        $error_message = $result["message"];
    }

    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>SMS Error</title>
        <link rel='stylesheet' href='style.css'>
    </head>

    <body>

    <div class='container'>

        <div class='alert-header'>
            <div class='alert-icon'>!</div>

            <h1>SMS Not Sent</h1>

            <p>
                There was a problem sending the emergency notification.
            </p>
        </div>

        <div style='padding:30px; text-align:center;'>

            <p>
                <strong>Error:</strong><br>
                " . htmlspecialchars($error_message) . "
            </p>

            <br>

            <a href='index.php'
               style='
               display:inline-block;
               padding:12px 20px;
               background:#b91c1c;
               color:white;
               text-decoration:none;
               border-radius:8px;
               '>
               Try Again
            </a>

        </div>

    </div>

    </body>
    </html>
    ";
}

?>
