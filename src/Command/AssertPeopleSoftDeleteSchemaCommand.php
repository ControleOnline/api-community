<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PeopleSoftDeleteSchemaAssert;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Pre/post deploy check: people.deleted + people.deleted_at must exist.
 * Usage: php bin/console app:schema:assert-people-soft-delete
 */
#[AsCommand(
    name: 'app:schema:assert-people-soft-delete',
    description: 'Fail if people.deleted / people.deleted_at are missing (api-community#83)'
)]
final class AssertPeopleSoftDeleteSchemaCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schema = $this->connection->createSchemaManager();
        if (!$schema->tablesExist(['people'])) {
            $output->writeln('<error>Table people is missing.</error>');
            return Command::FAILURE;
        }

        $columns = array_keys($schema->listTableColumns('people'));
        $missing = PeopleSoftDeleteSchemaAssert::missingColumns($columns);
        if ($missing !== []) {
            $output->writeln('<error>Missing people columns: ' . implode(', ', $missing) . '</error>');
            $output->writeln('Apply DoctrineMigrations\\People\\Version20260820030000 then re-run this command.');
            return Command::FAILURE;
        }

        $output->writeln('<info>people.deleted and people.deleted_at are present.</info>');
        return Command::SUCCESS;
    }
}
