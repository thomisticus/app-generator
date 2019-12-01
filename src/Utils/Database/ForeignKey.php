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

    /**
     * Retrieves and array of additional params for the relationship method based on foreign key properties
     *
     * @param ForeignKey $foreignKey The foreign key to be analyzed
     * @param string $relationshipType Relationship type. '1t1' (One to One), '1tm' (One to Many), 'mt1' (Many to One), 'mtm' (Many to Many)
     * @param null|string $tableNameOrOk Name of the table for the current Model or Owner Key (used owner key for 'mt1')
     * @param null|string $localPk
     * @param null|string $relatedPk
     * @return array
     */
    public function getAdditionalParamsByFk(
        $foreignKey,
        $relationshipType,
        $tableNameOrOk = null,
        $localPk = null,
        $relatedPk = null
    ) {
        if (in_array($relationshipType, ['1t1', '1tm']) && !empty($foreignKey->localField) && !empty($foreignKey->foreignField)) {
            return [
                'foreignKey' => $foreignKey->localField,
                'localKey' => $foreignKey->foreignField
            ];
        }

        if ($relationshipType === 'mt1') {
            if (!empty($foreignKey->localField)) {
                return [
                    'foreignKey' => $foreignKey->localField,
                    'ownerKey' => $tableNameOrOk
                ];
            }
        }

        $additionalParams = [];
        if ($relationshipType === 'mtm') {
            $keyName = 'relatedPivotKey';
            if ($foreignKey->foreignField == $localPk && $foreignKey->foreignTable == $tableNameOrOk) {
                $keyName = 'foreignPivotKey';
            }

            $additionalParams[$keyName] = $foreignKey->localField;
            $additionalParams['primaryKey'] = $relatedPk;
        }

        return $additionalParams;
    }
}
