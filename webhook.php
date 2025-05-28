<?php
$instancia = getenv('ZAPI_INSTANCIA');  // instanceId
$token = getenv('ZAPI_TOKEN');              // client token
$telefone = '556181243772';
$mensagem = "Teste envio Z-API (token no header)";

$url = "https://api.z-api.io/instances/$instancia/send-text";

$headers = [
    "Content-Type: application/json",
    "Client-Token: $token"
];

$payload = [
    "phone" => $telefone,
    "message" => $mensagem
];

error_log("Token: $token");
error_log("Instância: $instancia");


$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    error_log("cURL error: " . curl_error($ch));
} else {
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP Status: $httpcode");
    error_log("Retorno da API: $response");
}

curl_close($ch);
?>

