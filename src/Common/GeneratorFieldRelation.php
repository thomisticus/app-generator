<?php

namespace Thomisticus\Generator\Common;

use ICanBoogie\Inflector;
use Illuminate\Support\Str;

class GeneratorFieldRelation
{
    /** @var string */
    public $type;
    public $inputs;
    public $relationName;

    public static function parseRelation($relationInput, $aditionalParams = [])
    {
        $inputs = explode(',', $relationInput);

        $relation = new self();
        $relation->type = array_shift($inputs);
        $modelWithRelation = explode(':', array_shift($inputs)); //e.g ModelName:relationName
        if (count($modelWithRelation) == 2) {
            $relation->relationName = $modelWithRelation[1];
            unset($modelWithRelation[1]);
        }
        $relation->inputs = array_merge($modelWithRelation, $inputs);
        $relation->aditionalParams = $aditionalParams;

        return $relation;
    }

    public function getRelationAttributes($relationText = null)
    {
//        $inflector = Inflector::get('pt');

        $singularRelation = (!empty($this->relationName)) ? $this->relationName : Str::camel($relationText);
        $pluralRelation = (!empty($this->relationName)) ? $this->relationName : Str::camel(Str::plural($relationText));

//        $modelName = $this->inputs[0];
        switch ($this->type) {
            case '1t1':
//                $functionName = camel_case($modelName);
                $functionName = $singularRelation;
                $relation = 'hasOne';
                $relationClass = 'HasOne';
                break;
            case '1tm':
//                $functionName = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
                $functionName = $pluralRelation;
                $relation = 'hasMany';
                $relationClass = 'HasMany';
                break;
            case 'mt1':
//                $functionName = camel_case($modelName);
                if (!empty($this->relationName)) {
                    $singularRelation = $this->relationName;
                } elseif (isset($this->inputs[1])) {
                    $singularRelation = Str::camel(str_replace('_id', '', strtolower($this->inputs[1])));
                }
                $functionName = $singularRelation;
                $relation = 'belongsTo';
                $relationClass = 'BelongsTo';
                break;
            case 'mtm':
//                $functionName = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
                $functionName = $pluralRelation;
                $relation = 'belongsToMany';
                $relationClass = 'BelongsToMany';
                break;
            case 'hmt':
//                $functionName = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
                $functionName = $pluralRelation;
                $relation = 'hasManyThrough';
                $relationClass = 'HasManyThrough';
                break;
            default:
                $functionName = '';
                $relation = '';
                $relationClass = '';
                break;
        }

        return [
            'functionName' => $functionName,
            'relation' => $relation,
            'relationClass' => $relationClass
        ];
    }

    public function getRelationFunctionText($relationText = null)
    {
        $relationAttr = $this->getRelationAttributes($relationText);

        if (!empty($relationAttr['functionName']) && !empty($relationAttr['relation'])) {
            return $this->generateRelation(
                $relationAttr['functionName'],
                $relationAttr['relation'],
                $relationAttr['relationClass']
            );
        }

        return '';
    }

    private function generateRelation($functionName, $relation, $relationClass)
    {
        $inputs = $this->inputs;
        $modelName = array_shift($inputs);

        $template = get_template('model.relationship', 'app-generator');

        $template = str_replace('$RELATIONSHIP_CLASS$', $relationClass, $template);
        $template = str_replace('$FUNCTION_NAME$', $functionName, $template);
        $template = str_replace('$RELATION$', $relation, $template);
        $template = str_replace('$RELATION_MODEL_NAME$', $modelName, $template);

        if (count($inputs) > 0) {
            $inputFields = implode("', '", $inputs);
            $inputFields = ", '" . $inputFields . "'";
        } else {
            $inputFields = '';
        }

        if (!empty($this->aditionalParams)) {
            ksort($this->aditionalParams);
            $inputFields .= ", '" . implode("', '", $this->aditionalParams) . "'";
        }

        $template = str_replace('$INPUT_FIELDS$', strtolower($inputFields), $template);

        return $template;
    }
}
