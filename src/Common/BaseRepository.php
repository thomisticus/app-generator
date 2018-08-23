<?php

namespace Thomisticus\Generator\Common;

use App\Traits\ExceptionHandlerTrait;
use Exception;

abstract class BaseRepository extends \Prettus\Repository\Eloquent\BaseRepository
{
	use ExceptionHandlerTrait;

	/**
	 * Save a new entity in repository
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 * @throws \Prettus\Validator\Exceptions\ValidatorException
	 */
	public function create(array $attributes)
	{
		$createAnonFunction = function () use ($attributes) {
			$model = $this->getModelSkippingPresenter('create', $attributes);

			$model = $this->updateRelations($model, $attributes);
			$model->save();

			return $model;
		};

		$result = $this->tryWithTransaction($createAnonFunction);

		return ($result instanceof Exception) ? $result : $this->parserResult($result);
	}

	/**
	 * Update a entity in repository by id
	 *
	 * @param array $attributes
	 * @param       $id
	 *
	 * @return mixed
	 * @throws \Prettus\Validator\Exceptions\ValidatorException
	 */
	public function update(array $attributes, $id)
	{
		$updateAnonFunction = function () use ($attributes, $id) {
			$model = $this->getModelSkippingPresenter('update', $attributes, $id);

			$model = $this->updateRelations($model, $attributes);
			$model->save();
		};

		$result = $this->tryWithTransaction($updateAnonFunction);

		return ($result instanceof Exception) ? $result : $this->parserResult($result);
	}

	/**
	 * Update or Create an entity in repository
	 *
	 * @throws ValidatorException
	 *
	 * @param array $attributes
	 * @param array $values
	 *
	 * @return mixed
	 */
	public function updateOrCreate(array $attributes, array $values = [])
	{
		return $this->tryWithTransaction(function () use ($attributes, $values) {
			return parent::updateOrCreate($attributes, $values);
		});
	}

	/**
	 * Update relationships
	 *
	 *
	 * @param $model
	 * @param $attributes
	 *
	 * @return mixed
	 */
	public function updateRelations($model, $attributes)
	{
		foreach ($attributes as $key => $val) {
			if (isset($model) &&
				method_exists($model, $key) &&
				is_a(@$model->$key(), 'Illuminate\Database\Eloquent\Relations\Relation')
			) {
				$methodClass = get_class($model->$key($key));
				switch ($methodClass) {
					case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
						$new_values = array_get($attributes, $key, []);
						if (array_search('', $new_values) !== false) {
							unset($new_values[array_search('', $new_values)]);
						}
						$model->$key()->sync(array_values($new_values));
						break;
					case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
						$model_key         = $model->$key()->getQualifiedForeignKey();
						$new_value         = array_get($attributes, $key, null);
						$new_value         = $new_value == '' ? null : $new_value;
						$model->$model_key = $new_value;
						break;
					case 'Illuminate\Database\Eloquent\Relations\HasOne':
						break;
					case 'Illuminate\Database\Eloquent\Relations\HasOneOrMany':
						break;
					case 'Illuminate\Database\Eloquent\Relations\HasMany':
						$new_values = array_get($attributes, $key, []);
						if (array_search('', $new_values) !== false) {
							unset($new_values[array_search('', $new_values)]);
						}

						list($temp, $model_key) = explode('.', $model->$key($key)->getQualifiedForeignPivotKeyName());

						foreach ($model->$key as $rel) {
							if (!in_array($rel->id, $new_values)) {
								$rel->$model_key = null;
								$rel->save();
							}
							unset($new_values[array_search($rel->id, $new_values)]);
						}

						if (count($new_values) > 0) {
							$related = get_class($model->$key()->getRelated());
							foreach ($new_values as $val) {
								$rel             = $related::find($val);
								$rel->$model_key = $model->id;
								$rel->save();
							}
						}
						break;
				}
			}
		}

		return $model;
	}

	/**
	 * Delete a entity in repository by id
	 *
	 * @param $id
	 *
	 * @return int
	 */
	public function delete($id)
	{
		return $this->tryWithTransaction(function () use ($id) {
			return parent::delete($id);
		});
	}

	/**
	 * Delete multiple entities by given criteria.
	 *
	 * @param array $where
	 *
	 * @return int
	 */
	public function deleteWhere(array $where)
	{
		return $this->tryWithTransaction(function () use ($where) {
			return parent::deleteWhere($where);
		});
	}


	/**
	 * Retrieve all data of repository
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function all($columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($columns) {
			return parent::all($columns);
		});
	}

	/**
	 * Retrieve first data of repository
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function first($columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($columns) {
			return parent::first($columns);
		});
	}

	/**
	 * Retrieve first data of repository, or return new Entity
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 */
	public function firstOrNew(array $attributes = [])
	{
		return $this->tryWithoutTransaction(function () use ($attributes) {
			return parent::firstOrNew($attributes);
		});
	}

	/**
	 * Retrieve first data of repository, or create new Entity
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 */
	public function firstOrCreate(array $attributes = [])
	{
		return $this->tryWithoutTransaction(function () use ($attributes) {
			return parent::firstOrCreate($attributes);
		});
	}

	/**
	 * Find data by id
	 *
	 * @param       $id
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function find($id, $columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($id, $columns) {
			return parent::find($id, $columns);
		});
	}

	/**
	 * Find data by field and value
	 *
	 * @param       $field
	 * @param       $value
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function findByField($field, $value = null, $columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($field, $value, $columns) {
			return parent::findByField($field, $value, $columns);
		});
	}

	/**
	 * Find data by multiple fields
	 *
	 * @param array $where
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function findWhere(array $where, $columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($where, $columns) {
			return parent::findWhere($where, $columns);
		});
	}

	/**
	 * Find data by multiple values in one field
	 *
	 * @param       $field
	 * @param array $values
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function findWhereIn($field, array $values, $columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($field, $values, $columns) {
			return parent::findWhereIn($field, $values, $columns);
		});
	}

	/**
	 * Find data by excluding multiple values in one field
	 *
	 * @param       $field
	 * @param array $values
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function findWhereNotIn($field, array $values, $columns = ['*'])
	{
		return $this->tryWithoutTransaction(function () use ($field, $values, $columns) {
			return parent::findWhereNotIn($field, $values, $columns);
		});
	}

	/**
	 * Get the model skipping presenter, because is not to get some data.
	 *
	 * @param string $method
	 * @param array  $attributes
	 * @param null   $id
	 *
	 * @return mixed
	 * @throws \Prettus\Validator\Exceptions\ValidatorException
	 */
	protected function getModelSkippingPresenter($method = 'create', array $attributes, $id = null)
	{
		$temporarySkipPresenter = $this->skipPresenter;
		$this->skipPresenter(true);

		$model = $method == 'update' && !empty($id) ? parent::update($attributes, $id) : parent::create($attributes);

		$this->skipPresenter($temporarySkipPresenter);

		return $model;
	}

}
