import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\OpenApiController::__invoke
* @see app/Http/Controllers/OpenApiController.php:10
* @route '/api/openapi.json'
*/
const OpenApiController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OpenApiController.url(options),
    method: 'get',
})

OpenApiController.definition = {
    methods: ["get","head"],
    url: '/api/openapi.json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OpenApiController::__invoke
* @see app/Http/Controllers/OpenApiController.php:10
* @route '/api/openapi.json'
*/
OpenApiController.url = (options?: RouteQueryOptions) => {
    return OpenApiController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OpenApiController::__invoke
* @see app/Http/Controllers/OpenApiController.php:10
* @route '/api/openapi.json'
*/
OpenApiController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OpenApiController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OpenApiController::__invoke
* @see app/Http/Controllers/OpenApiController.php:10
* @route '/api/openapi.json'
*/
OpenApiController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: OpenApiController.url(options),
    method: 'head',
})

export default OpenApiController