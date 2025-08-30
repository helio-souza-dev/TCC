<?php
require __DIR__ . '/vendor/autoload.php';

use Supabase\Client;

$client = Client::create("https://shqdmrqhddaxnvutsomv.supabase.co", "SUA-CHAVE");

var_dump($client);
