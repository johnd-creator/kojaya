import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\PointApiController::balance
* @see app/Http/Controllers/Api/V1/PointApiController.php:16
* @route '/api/v1/points/balance'
*/
export const balance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balance.url(options),
    method: 'get',
})

balance.definition = {
    methods: ["get","head"],
    url: '/api/v1/points/balance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::balance
* @see app/Http/Controllers/Api/V1/PointApiController.php:16
* @route '/api/v1/points/balance'
*/
balance.url = (options?: RouteQueryOptions) => {
    return balance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::balance
* @see app/Http/Controllers/Api/V1/PointApiController.php:16
* @route '/api/v1/points/balance'
*/
balance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::balance
* @see app/Http/Controllers/Api/V1/PointApiController.php:16
* @route '/api/v1/points/balance'
*/
balance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: balance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::history
* @see app/Http/Controllers/Api/V1/PointApiController.php:30
* @route '/api/v1/points/history'
*/
export const history = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/api/v1/points/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::history
* @see app/Http/Controllers/Api/V1/PointApiController.php:30
* @route '/api/v1/points/history'
*/
history.url = (options?: RouteQueryOptions) => {
    return history.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::history
* @see app/Http/Controllers/Api/V1/PointApiController.php:30
* @route '/api/v1/points/history'
*/
history.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PointApiController::history
* @see app/Http/Controllers/Api/V1/PointApiController.php:30
* @route '/api/v1/points/history'
*/
history.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(options),
    method: 'head',
})

const PointApiController = { balance, history }

export default PointApiController