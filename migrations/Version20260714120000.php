<?php
// ALEMAC // 2026/07/14 12:00:00

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add generic people access, schedule, employee profile and export tables';
    }

    public function up(Schema $schema): void
    {
        $this->createEmployeeProfileTable();
        $this->createPeopleAccessEventTable();
        $this->createPeopleScheduleTable();
        $this->createPeopleExportJobTable();
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('people_export_job')) {
            $this->addSql('DROP TABLE people_export_job');
        }

        if ($this->tableExists('people_schedule')) {
            $this->addSql('DROP TABLE people_schedule');
        }

        if ($this->tableExists('people_access_event')) {
            $this->addSql('DROP TABLE people_access_event');
        }

        if ($this->tableExists('employee_profile')) {
            $this->addSql('DROP TABLE employee_profile');
        }
    }

    private function createEmployeeProfileTable(): void
    {
        if ($this->tableExists('employee_profile')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE employee_profile (
  id INT AUTO_INCREMENT NOT NULL,
  people_link_id INT NOT NULL,
  job_title VARCHAR(255) DEFAULT NULL,
  job_function VARCHAR(255) DEFAULT NULL,
  department VARCHAR(255) DEFAULT NULL,
  employment_type VARCHAR(120) DEFAULT NULL,
  admission_date DATE DEFAULT NULL,
  termination_date DATE DEFAULT NULL,
  workload_hours INT DEFAULT NULL,
  linkedin_url VARCHAR(255) DEFAULT NULL,
  linkedin_headline VARCHAR(255) DEFAULT NULL,
  linkedin_summary LONGTEXT DEFAULT NULL,
  linkedin_snapshot JSON DEFAULT NULL,
  notes LONGTEXT DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alter_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX employee_profile_people_link_unique (people_link_id),
  INDEX employee_profile_people_link_idx (people_link_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );

        $this->addSql('ALTER TABLE employee_profile ADD CONSTRAINT FK_EMPLOYEE_PROFILE_PEOPLE_LINK FOREIGN KEY (people_link_id) REFERENCES people_link (id) ON DELETE CASCADE');
    }

    private function createPeopleAccessEventTable(): void
    {
        if ($this->tableExists('people_access_event')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE people_access_event (
  id INT AUTO_INCREMENT NOT NULL,
  context VARCHAR(120) NOT NULL,
  company_id INT NOT NULL,
  people_id INT NOT NULL,
  direction VARCHAR(20) NOT NULL DEFAULT 'entry',
  event_at DATETIME NOT NULL,
  source VARCHAR(120) NOT NULL DEFAULT 'manual',
  payload JSON DEFAULT NULL,
  creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alter_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX people_access_event_context_idx (context),
  INDEX people_access_event_company_idx (company_id),
  INDEX people_access_event_people_idx (people_id),
  INDEX people_access_event_event_at_idx (event_at),
  INDEX people_access_event_direction_idx (direction),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );

        $this->addSql('ALTER TABLE people_access_event ADD CONSTRAINT FK_PEOPLE_ACCESS_EVENT_COMPANY FOREIGN KEY (company_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_access_event ADD CONSTRAINT FK_PEOPLE_ACCESS_EVENT_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE CASCADE');
    }

    private function createPeopleScheduleTable(): void
    {
        if ($this->tableExists('people_schedule')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE people_schedule (
  id INT AUTO_INCREMENT NOT NULL,
  context VARCHAR(120) NOT NULL,
  company_id INT NOT NULL,
  people_id INT NOT NULL,
  professional_people_id INT DEFAULT NULL,
  label VARCHAR(255) DEFAULT NULL,
  mode VARCHAR(20) NOT NULL DEFAULT 'recurring',
  weekday SMALLINT DEFAULT NULL,
  start_time TIME DEFAULT NULL,
  end_time TIME DEFAULT NULL,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  payload JSON DEFAULT NULL,
  creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alter_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX people_schedule_context_idx (context),
  INDEX people_schedule_company_idx (company_id),
  INDEX people_schedule_people_idx (people_id),
  INDEX people_schedule_professional_people_idx (professional_people_id),
  INDEX people_schedule_mode_idx (mode),
  INDEX people_schedule_weekday_idx (weekday),
  INDEX people_schedule_active_idx (active),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );

        $this->addSql('ALTER TABLE people_schedule ADD CONSTRAINT FK_PEOPLE_SCHEDULE_COMPANY FOREIGN KEY (company_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_schedule ADD CONSTRAINT FK_PEOPLE_SCHEDULE_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_schedule ADD CONSTRAINT FK_PEOPLE_SCHEDULE_PROFESSIONAL_PEOPLE FOREIGN KEY (professional_people_id) REFERENCES people (id) ON DELETE SET NULL');
    }

    private function createPeopleExportJobTable(): void
    {
        if ($this->tableExists('people_export_job')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE people_export_job (
  id INT AUTO_INCREMENT NOT NULL,
  context VARCHAR(120) NOT NULL,
  kind VARCHAR(80) NOT NULL,
  company_id INT NOT NULL,
  people_id INT DEFAULT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  file_id INT DEFAULT NULL,
  filters JSON DEFAULT NULL,
  error_message LONGTEXT DEFAULT NULL,
  finished_at DATETIME DEFAULT NULL,
  creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alter_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX people_export_job_context_idx (context),
  INDEX people_export_job_kind_idx (kind),
  INDEX people_export_job_status_idx (status),
  INDEX people_export_job_company_idx (company_id),
  INDEX people_export_job_people_idx (people_id),
  INDEX people_export_job_period_start_idx (period_start),
  INDEX people_export_job_period_end_idx (period_end),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );

        $this->addSql('ALTER TABLE people_export_job ADD CONSTRAINT FK_PEOPLE_EXPORT_JOB_COMPANY FOREIGN KEY (company_id) REFERENCES people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE people_export_job ADD CONSTRAINT FK_PEOPLE_EXPORT_JOB_PEOPLE FOREIGN KEY (people_id) REFERENCES people (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE people_export_job ADD CONSTRAINT FK_PEOPLE_EXPORT_JOB_FILE FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE SET NULL');
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }
}
