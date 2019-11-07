<?php

namespace Thomisticus\Generator\Utils\Database;

use Thomisticus\Generator\Utils\Database\GeneratorForeignKey;

class GeneratorTable
{
    /**
     * @var string
     */
    public $primaryKey;

    /**
     * @var GeneratorForeignKey[]
     */
    public $foreignKeys;

    /**
     * GeneratorTable constructor.
     *
     * @param string $primaryKey
     * @param GeneratorForeignKey[] $foreignKeys
     */
    public function __construct($primaryKey, $foreignKeys)
    {
        $this->primaryKey = $primaryKey;
        $this->foreignKeys = $foreignKeys;
    }
}
