<?php
// ALEMAC // 2026/02/28 18:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

final class DumpCleanUp
{
    public static function clean(string $sql): string
    {
        /*
         |--------------------------------------------------------------------------
         | REMOVE COMANDOS DE BANCO
         |--------------------------------------------------------------------------
         */

        // Remove CREATE DATABASE
        $sql = preg_replace('/CREATE DATABASE.*?;\s*/is', '', $sql);

        // Remove USE database
        $sql = preg_replace('/USE\s+`?.*?`?\s*;\s*/is', '', $sql);

        // Remove START TRANSACTION
        $sql = preg_replace('/START TRANSACTION\s*;\s*/is', '', $sql);

        // Remove COMMIT
        $sql = preg_replace('/COMMIT\s*;\s*/is', '', $sql);

        /*
         |--------------------------------------------------------------------------
         | REMOVE TABELAS DE CONTROLE DO DOCTRINE
         |--------------------------------------------------------------------------
         */

        // migration_versions (antigo)
        $sql = preg_replace(
            '/CREATE TABLE\s+`?migration_versions`?.*?;\s*/is',
            '',
            $sql
        );

        $sql = preg_replace(
            '/ALTER TABLE\s+`?migration_versions`?.*?;\s*/is',
            '',
            $sql
        );

        // doctrine_migration_versions (novo)
        $sql = preg_replace(
            '/CREATE TABLE\s+`?doctrine_migration_versions`?.*?;\s*/is',
            '',
            $sql
        );

        $sql = preg_replace(
            '/ALTER TABLE\s+`?doctrine_migration_versions`?.*?;\s*/is',
            '',
            $sql
        );

        return $sql;
    }
}