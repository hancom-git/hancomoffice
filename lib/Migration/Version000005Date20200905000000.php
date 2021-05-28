<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000005Date20200905000000 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('hancomoffice_locks')) {
            $table = $schema->createTable('hancomoffice_locks');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('key', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('lock', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('ttl', 'integer', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['key'], 'hancomofficelock_key_index');
            $table->addIndex(['key'], 'hancomofficelocks_key_index');
        }
        return $schema;
    }
    
}