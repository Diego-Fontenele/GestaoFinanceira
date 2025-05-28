<?php
$instancia = getenv('ZAPI_INSTANCIA');  
$token = getenv('ZAPI_TOKEN');           
$telefone = '5561981243772';
$mensagem = "Welcome to *Z-API*";

$url = "https://api.z-api.io/instances/$instancia/token/$token/send-text";

$headers = [
    "Content-Type: application/json",
    "client-token: $token"
];

$payload = json_encode([
    "phone" => $telefone,
    "message" => $mensagem
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log("cURL error: " . curl_error($ch));
} else {
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP Status: $httpcode");
    error_log("Retorno da API: $response");
    echo $response;
}

curl_close($ch);
?>

