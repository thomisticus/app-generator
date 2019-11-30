<?php

namespace Thomisticus\Generator\Utils\Database;

use Illuminate\Support\Str;

class ForeignKey
{
    /** @var string */
    public $ownerTableName;
    public $name;
    public $localField;
    public $foreignField;
    public $foreignTable;
    public $onUpdate;
    public $onDelete;

    /**
     * ForeignKey constructor.
     *
     * @param string $ownerTableName
     * @param string $name
     * @param string $localField
     * @param string $foreignField
     * @param string $foreignTable
     * @param string|boolean $onUpdate
     * @param string|boolean $onDelete
     */
    public function __construct($ownerTableName, $name, $localField, $foreignField, $foreignTable, $onUpdate, $onDelete)
    {
        $this->ownerTableName = $ownerTableName;
        $this->name = $name;
        $this->localField = $localField;
        $this->foreignField = $foreignField;
        $this->foreignTable = $foreignTable;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
    }

    /**
     * Verify if the foreign key name follows the default formation {'function_name' . '_' . 'local_key/owner_key'}
     *
     * @param string $foreignKeyName
     * @param string $relation
     * @param string $ownerPrimaryKeyName
     * @return bool
     */
    public static function isDefaultForeignKeyName($foreignKeyName, $relation, $ownerPrimaryKeyName)
    {
        return $foreignKeyName === Str::snake($relation) . '_' . $ownerPrimaryKeyName;
    }
}
