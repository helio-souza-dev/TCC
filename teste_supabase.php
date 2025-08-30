<?php
require_once __DIR__ . '/vendor/autoload.php';

// Teste direto com cURL para verificar SSL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://shqdmrqhddaxnvutsomv.supabase.co');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignora SSL
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignora SSL

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "SSL Error: " . curl_error($ch) . "\n";

curl_close($ch);