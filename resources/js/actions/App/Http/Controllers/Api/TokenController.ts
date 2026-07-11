import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TokenController::rotate
* @see app/Http/Controllers/Api/TokenController.php:13
* @route '/api/token/rotate'
*/
export const rotate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rotate.url(options),
    method: 'post',
})

rotate.definition = {
    methods: ["post"],
    url: '/api/token/rotate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TokenController::rotate
* @see app/Http/Controllers/Api/TokenController.php:13
* @route '/api/token/rotate'
*/
rotate.url = (options?: RouteQueryOptions) => {
    return rotate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TokenController::rotate
* @see app/Http/Controllers/Api/TokenController.php:13
* @route '/api/token/rotate'
*/
rotate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rotate.url(options),
    method: 'post',
})

const TokenController = { rotate }

export default TokenController