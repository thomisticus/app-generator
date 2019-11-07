<?php

namespace Thomisticus\Generator\Utils\Database;

class GeneratorForeignKey
{
    /** @var string */
    public $name;
    public $localField;
    public $foreignField;
    public $foreignTable;
    public $onUpdate;
    public $onDelete;

    /**
     * GeneratorForeignKey constructor.
     *
     * @param string $name
     * @param string $localField
     * @param string $foreignField
     * @param string $foreignTable
     * @param string|boolean $onUpdate
     * @param string|boolean $onDelete
     */
    public function __construct($name, $localField, $foreignField, $foreignTable, $onUpdate, $onDelete)
    {
        $this->name = $name;
        $this->localField = $localField;
        $this->foreignField = $foreignField;
        $this->foreignTable = $foreignTable;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
    }
}
