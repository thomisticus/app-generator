<?php

namespace Thomisticus\Generator\Common;

//use App\Traits\ExceptionHandlerTrait;
use Exception;
use Prettus\Validator\Exceptions\ValidatorException;

abstract class BaseRepository extends \Prettus\Repository\Eloquent\BaseRepository
{
//	use ExceptionHandlerTrait;

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return mixed
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     * @throws Exception
     */
    public function create(array $attributes)
    {
        return $this->getResult(function () use ($attributes) {
            return parent::create($attributes);
        });
    }

    /**
     * Update a entity in repository by id
     *
     * @param array $attributes
     * @param       $id
     *
     * @return mixed
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     * @throws Exception
     */
    public function update(array $attributes, $id)
    {
        return $this->getResult(function () use ($attributes, $id) {
            return parent::update($attributes, $id);
        });
    }

    /**
     * Update or Create an entity in repository
     *
     * @param array $attributes
     * @param array $values
     *
     * @return mixed
     * @throws Exception
     *
     * @throws ValidatorException
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->getResult(function () use ($attributes, $values) {
            return parent::updateOrCreate($attributes, $values);
        });
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     * @throws Exception
     */
    public function delete($id)
    {
        return $this->getResult(function () use ($id) {
            return parent::delete($id);
        });
    }

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     * @throws Exception
     */
    public function deleteWhere(array $where)
    {
        return $this->getResult(function () use ($where) {
            return parent::deleteWhere($where);
        });
    }


    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function all($columns = ['*'])
    {
        return $this->getResult(function () use ($columns) {
            return parent::all($columns);
        }, false);
    }

    /**
     * Retrieve first data of repository
     *
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function first($columns = ['*'])
    {
        return $this->getResult(function () use ($columns) {
            return parent::first($columns);
        }, false);
    }

    /**
     * Retrieve first data of repository, or return new Entity
     *
     * @param array $attributes
     *
     * @return mixed
     * @throws Exception
     */
    public function firstOrNew(array $attributes = [])
    {
        return $this->getResult(function () use ($attributes) {
            return parent::firstOrNew($attributes);
        });
    }

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     *
     * @return mixed
     * @throws Exception
     */
    public function firstOrCreate(array $attributes = [])
    {
        return $this->getResult(function () use ($attributes) {
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
     * @throws Exception
     */
    public function find($id, $columns = ['*'])
    {
        return $this->getResult(function () use ($id, $columns) {
            return parent::find($id, $columns);
        }, false);
    }

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        return $this->getResult(function () use ($field, $value, $columns) {
            return parent::findByField($field, $value, $columns);
        }, false);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        return $this->getResult(function () use ($where, $columns) {
            return parent::findWhere($where, $columns);
        }, false);
    }

    /**
     * Find data by multiple values in one field
     *
     * @param       $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function findWhereIn($field, array $values, $columns = ['*'])
    {
        return $this->getResult(function () use ($field, $values, $columns) {
            return parent::findWhereIn($field, $values, $columns);
        }, false);
    }

    /**
     * Find data by excluding multiple values in one field
     *
     * @param       $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     * @throws Exception
     */
    public function findWhereNotIn($field, array $values, $columns = ['*'])
    {
        return $this->getResult(function () use ($field, $values, $columns) {
            return parent::findWhereNotIn($field, $values, $columns);
        }, false);
    }

    /**
     * @param      $anonymousFunc
     * @param bool $withTransaction
     *
     * @return bool|Exception|mixed
     * @throws Exception
     */
    private function getResult($anonymousFunc, $withTransaction = true)
    {
        if (is_callable($anonymousFunc)) {
            return config('app.env') != 'production' ? $anonymousFunc() :
                ($withTransaction ? $this->tryWithTransaction($anonymousFunc) : $this->tryWithoutTransaction($anonymousFunc));
        }

        return false;
    }

}
