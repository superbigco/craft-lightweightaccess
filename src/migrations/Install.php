<?php
/**
 * Lightweight Access plugin for Craft CMS 3.x
 *
 * LDAP SSO for Craft
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2018 Superbig
 */

namespace superbig\lightweightaccess\migrations;

use Craft;
use craft\db\Migration;
use superbig\lightweightaccess\records\UserReferenceRecord;

/**
 * @author    Superbig
 * @package   LightweightAccess
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(UserReferenceRecord::TABLE_NAME);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                UserReferenceRecord::TABLE_NAME,
                [
                    'id'          => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid'         => $this->uid(),
                    'userId'      => $this->integer()->notNull(),
                    'email'       => $this->string(255)->notNull()->defaultValue(''),
                    'username'    => $this->string(255)->defaultValue(''),
                    'firstName'   => $this->string(255)->defaultValue(''),
                    'lastName'    => $this->string(255)->defaultValue(''),
                    'reference'   => $this->string(255)->defaultValue(''),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                UserReferenceRecord::TABLE_NAME,
                'email',
                true
            ),
            UserReferenceRecord::TABLE_NAME,
            'email',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                UserReferenceRecord::TABLE_NAME,
                'reference',
                true
            ),
            UserReferenceRecord::TABLE_NAME,
            'reference',
            true
        );
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(UserReferenceRecord::TABLE_NAME, 'userId'),
            UserReferenceRecord::TABLE_NAME,
            'userId',
            '{{%elements}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists(UserReferenceRecord::TABLE_NAME);
    }
}
