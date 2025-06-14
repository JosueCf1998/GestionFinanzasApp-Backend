<?php


use Utils\ResponseHelper;

class BaseController
{
    protected function successResponse($data = [], $mensaje = 'Operación exitosa', $code = 200)
    {
        ResponseHelper::success($data, $mensaje, $code);
    }

    protected function errorResponse($mensaje = 'Error en la operación', $code = 500, $error = [])
    {
        ResponseHelper::error($mensaje, $code, $error);
    }
}