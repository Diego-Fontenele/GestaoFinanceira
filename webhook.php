<?php

$instancia = getenv('ZAPI_INSTANCIA');  
$token = getenv('ZAPI_TOKEN');          
$telefone = '556181243772';             
$mensagem = 'Teste envio Z-API via cURL';

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.z-api.io/instances/$instancia/token/$token/send-text",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode([
    "phone" => $telefone,
    "message" => $mensagem
  ]),
  CURLOPT_HTTPHEADER => array(
    "client-token: $token",
    "content-type: application/json"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}