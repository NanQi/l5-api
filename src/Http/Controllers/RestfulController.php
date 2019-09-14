<?php

namespace Specialtactics\L5Api\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestfulController extends BaseRestfulController
{
    /**
     * Request to retrieve a collection of all items of this resource
     *
     * @return \Dingo\Api\Http\Response
     */
    public function getAll()
    {
        $this->authorizeUserAction('viewAll');

        $model = new static::$model;

        $query = $model::with($model::getCollectionWith());
        $this->qualifyCollectionQuery($query);

        // Handle pagination, if applicable
        $perPage = $model->getPerPage();
        if ($perPage) {
            $paginator = $query->paginate($perPage);

            return $this->response->paginator($paginator, $this->getTransformer());
        } else {
            $resources = $query->get();

            return $this->response->collection($resources, $this->getTransformer());
        }
    }

    /**
     * Request to retrieve a single item of this resource
     *
     * @param int $id of the resource
     * @return \Dingo\Api\Http\Response
     * @throws HttpException
     */
    public function get($id)
    {
        $model = new static::$model;

        $resource = $model::with($model::getItemWith())->findOrFail($id);

        $this->authorizeUserAction('view', $resource);

        return $this->response->item($resource, $this->getTransformer());
    }

    /**
     * Request to create a new resource
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * @throws HttpException|QueryException
     */
    public function post(Request $request)
    {
        $this->authorizeUserAction('create');

        $model = new static::$model;

        $this->restfulService->validateResource($model, $request->input());

        $resource = $this->restfulService->persistResource(new $model($request->input()));

        // Retrieve full model
        $resource = $model::with($model::getItemWith())->where($model->getKeyName(), '=', $resource->getKey())->first();

        if ($this->shouldTransform()) {
            $response = $this->response->item($resource, $this->getTransformer())->setStatusCode(201);
        } else {
            $response = $resource;
        }

        return $response;
    }

    /**
     * Request to create or replace a resource
     *
     * @param Request $request
     * @param int $id
     * @return \Dingo\Api\Http\Response
     */
    public function put(Request $request, $id)
    {
        $model = static::$model::find($id);

        if (! $model) {
            // Doesn't exist - create
            $this->authorizeUserAction('create');

            $model = new static::$model;

            $this->restfulService->validateResource($model, $request->input());
            $resource = $this->restfulService->persistResource(new $model($request->input()));

            $resource->loadMissing($model::getItemWith());

            if ($this->shouldTransform()) {
                $response = $this->response->item($resource, $this->getTransformer())->setStatusCode(201);
            } else {
                $response = $resource;
            }
        } else {
            // Exists - replace
            $this->authorizeUserAction('update', $model);

            $this->restfulService->validateResourceUpdate($model, $request->input());
            $this->restfulService->persistResource($model->fill($request->input()));

            if ($this->shouldTransform()) {
                $response = $this->response->item($model, $this->getTransformer())->setStatusCode(200);
            } else {
                $response = $model;
            }
        }

        return $response;
    }

    /**
     * Request to update the specified resource
     *
     * @param int $id of the resource
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * @throws HttpException
     */
    public function patch($id, Request $request)
    {
        $model = static::$model::findOrFail($id);

        $this->authorizeUserAction('update', $model);

        $this->restfulService->validateResourceUpdate($model, $request->input());

        $this->restfulService->patch($model, $request->input());

        if ($this->shouldTransform()) {
            $response = $this->response->item($model, $this->getTransformer());
        } else {
            $response = $model;
        }

        return $response;
    }

    /**
     * Deletes a resource by ID
     *
     * @param int $id of the resource
     * @return \Dingo\Api\Http\Response
     * @throws NotFoundHttpException
     */
    public function delete($id)
    {
        $this->authorizeUserAction('delete', $id);

        $deletedCount = static::$model::destroy($id);

        if ($deletedCount < 1) {
            throw new NotFoundHttpException('无法找到需要删除的资源ID');
        }

        return $this->response->noContent()->setStatusCode(204);
    }
}
