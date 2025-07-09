<?php
// db.php — conexão PDO com o banco ON_Proc, ajustando timezone do MySQL

require_once __DIR__ . '/config.php';

function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Monta o DSN sem timezone — vamos setar manualmente depois
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Ajusta o timezone do servidor MySQL para o mesmo do PHP
            // date('P') retorna algo como "-03:00" para America/Sao_Paulo
            $tz = date('P');
            $pdo->exec("SET time_zone = '{$tz}'");

            // (Opcional) Reafirma o charset e collation
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        } catch (PDOException $e) {
            // Em produção: logue $e->getMessage() e alerte genericamente
            error_log('DB connection error: ' . $e->getMessage());
            exit('Erro ao conectar ao banco.');
        }
    }

    return $pdo;
}
