<?php
// config/database.php

require_once __DIR__ . '/../vendor/autoload.php';

class Database {
    public $client;

    private $supabaseUrl = 'https://shqdmrqhddaxnvutsomv.supabase.co';
    private $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNocWRtcnFoZGRheG52dXRzb212Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2MzAyNjAsImV4cCI6MjA3MTIwNjI2MH0.0SX4m5FbJTDgVwJSx1My_p5-s9cNkK9nHTAy52ML27I'; // Cole sua chave aqui

    // O CÓDIGO DE DEPURAÇÃO FOI MOVIDO DAQUI...

    public function __construct() {

        try {
            $this->client = new \Supabase\CreateClient($this->supabaseUrl, $this->supabaseKey);
        } catch (\Exception $e) {
            die('Erro ao conectar com o Supabase: ' . $e->getMessage());
        }
    }
}
?>