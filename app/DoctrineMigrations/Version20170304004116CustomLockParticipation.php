<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Add a date field which holds the datetime when a
 * participant has to be registered to meal
 */
class Version20170304004116CustomLockParticipation extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->validateDatabase();
        $this->addSql('ALTER TABLE day ADD lockParticipationDateTime DATETIME DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->validateDatabase();
        $this->addSql('ALTER TABLE day DROP lockParticipationDateTime');
    }
}
