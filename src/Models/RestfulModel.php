<?php

namespace Specialtactics\L5Api\Models;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Database\Eloquent\Model;
use App\Transformers\BaseTransformer;
use Specialtactics\L5Api\Transformers\RestfulTransformer;
use Specialtactics\L5Api\APIBoilerplate;

class RestfulModel extends Model
{
    /**
     * These attributes (in addition to primary) are not allowed to be updated explicitly through
     *  API routes of update and put. They can still be updated internally by Laravel, and your own code.
     *
     * @var array Attributes to disallow updating through an API update or put
     */
    public $immutableAttributes = ['created_at', 'deleted_at'];

    /**
     * Acts like $with (eager loads relations), however only for immediate controller requests for that object
     * This is useful if you want to use "with" for immediate resource routes, however don't want these relations
     *  always loaded in various service functions, for performance reasons
     *
     * @deprecated Use  getItemWith() and getCollectionWith()
     * @var array Relations to load implicitly by Restful controllers
     */
    public static $localWith = null;

    /**
     * What relations should one model of this entity be returned with, from a relevant controller
     *
     * @var null|array
     */
    public static $itemWith = [];

    /**
     * What relations should a collection of models of this entity be returned with, from a relevant controller
     * If left null, then $itemWith will be used
     *
     * @var null|array
     */
    public static $collectionWith = null;

    /**
     * You can define a custom transformer for a model, if you wish to override the functionality of the Base transformer
     *
     * @var null|RestfulTransformer The transformer to use for this model, if overriding the default
     */
    public static $transformer = null;

    /**
     * Return the validation rules for this model
     *
     * @return array Validation rules to be used for the model when creating it
     */
    public function getValidationRules()
    {
        return [];
    }

    /**
     * Return the validation rules for this model's update operations
     * In most cases, they will be the same as for the create operations
     *
     * @return array Validation roles to use for updating model
     */
    public function getValidationRulesUpdating()
    {
        return $this->getValidationRules();
    }

    /**
     * Return any custom validation rule messages to be used
     *
     * @return array
     */
    public function getValidationMessages()
    {
        return [];
    }

    /**
     * Boot the model
     *
     * Add various functionality in the model lifecycle hooks
     */
    public static function boot()
    {
        parent::boot();

        // Add functionality for updating a model
        static::updating(function (self $model) {
            // Disallow updating id keys
            if ($model->getAttribute($model->getKeyName()) != $model->getOriginal($model->getKeyName())) {
                throw new BadRequestHttpException('不允许更新资源的id');
            }

            // Disallow updating immutable attributes
            if (! empty($model->immutableAttributes)) {
                // For each immutable attribute, check if they have changed
                foreach ($model->immutableAttributes as $attributeName) {
                    if ($model->getOriginal($attributeName) != $model->getAttribute($attributeName)) {
                        throw new BadRequestHttpException('禁止修改属性"'. APIBoilerplate::formatCaseAccordingToResponseFormat($attributeName) .'"');
                    }
                }
            }
        });
    }

    /**
     * Return this model's transformer, or a generic one if a specific one is not defined for the model
     *
     * @return BaseTransformer
     */
    public static function getTransformer()
    {
        return is_null(static::$transformer) ? new BaseTransformer : new static::$transformer;
    }

    /**
     * If using deprecated $localWith then use that
     * Otherwise, use $itemWith
     *
     * @return array
     */
    public static function getItemWith()
    {
        if (is_null(static::$localWith)) {
            return static::$itemWith;
        } else {
            return static::$localWith;
        }
    }

    /**
     * If using deprecated $localWith then use that
     * Otherwise, if collectionWith hasn't been set, use $itemWith by default
     * Otherwise, use collectionWith
     *
     * @return array
     */
    public static function getCollectionWith()
    {
        if (is_null(static::$localWith)) {
            if (! is_null(static::$collectionWith)) {
                return static::$collectionWith;
            } else {
                return static::$itemWith;
            }
        } else {
            return static::$localWith;
        }
    }

    /************************************************************
     * Extending Laravel Functions Below
     ***********************************************************/

    /**
     * We're extending the existing Laravel Builder
     *
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }
}
