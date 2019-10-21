<?php

namespace Thomisticus\Generator\Common;

use Thomisticus\Generator\Common\GeneratorForeignKey;

class GeneratorTable
{
    /** @var string */
    public $primaryKey;

    /** @var GeneratorForeignKey[] */
    public $foreignKeys;
}
