<?php

namespace Thomisticus\Generator\Common;

use ICanBoogie\Inflector;

class GeneratorFieldRelation
{
	/** @var string */
	public $type;
	public $inputs;

	public static function parseRelation($relationInput, $aditionalParams = [])
	{
		$inputs = explode(',', $relationInput);

		$relation                  = new self();
		$relation->type            = array_shift($inputs);
		$relation->inputs          = $inputs;
		$relation->aditionalParams = $aditionalParams;

		return $relation;
	}

	public function getRelationFunctionText()
	{
		$inflector = Inflector::get('pt');

		$modelName = $this->inputs[0];
		switch ($this->type) {
			case '1t1':
				$functionName  = camel_case($modelName);
				$relation      = 'hasOne';
				$relationClass = 'HasOne';
				break;
			case '1tm':
				$functionName  = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
				$relation      = 'hasMany';
				$relationClass = 'HasMany';
				break;
			case 'mt1':
				$functionName  = camel_case($modelName);
				$relation      = 'belongsTo';
				$relationClass = 'BelongsTo';
				break;
			case 'mtm':
				$functionName  = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
				$relation      = 'belongsToMany';
				$relationClass = 'BelongsToMany';
				break;
			case 'hmt':
				$functionName  = !empty($modelName) ? camel_case($inflector->pluralize($modelName)) : camel_case($modelName);
				$relation      = 'hasManyThrough';
				$relationClass = 'HasManyThrough';
				break;
			default:
				$functionName  = '';
				$relation      = '';
				$relationClass = '';
				break;
		}

		if (!empty($functionName) and !empty($relation)) {
			return $this->generateRelation($functionName, $relation, $relationClass);
		}

		return '';
	}

	private function generateRelation($functionName, $relation, $relationClass)
	{
		$inputs    = $this->inputs;
		$modelName = array_shift($inputs);

		$template = get_template('model.relationship', 'crud-generator');

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
