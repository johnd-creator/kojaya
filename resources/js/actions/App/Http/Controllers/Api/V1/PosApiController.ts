import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:16
* @route '/api/v1/pos/products'
*/
export const products = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: products.url(options),
    method: 'get',
})

products.definition = {
    methods: ["get","head"],
    url: '/api/v1/pos/products',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:16
* @route '/api/v1/pos/products'
*/
products.url = (options?: RouteQueryOptions) => {
    return products.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:16
* @route '/api/v1/pos/products'
*/
products.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:16
* @route '/api/v1/pos/products'
*/
products.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: products.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:37
* @route '/api/v1/pos/transactions'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/pos/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:37
* @route '/api/v1/pos/transactions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:37
* @route '/api/v1/pos/transactions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::processReturn
* @see app/Http/Controllers/Api/V1/PosApiController.php:46
* @route '/api/v1/pos/returns'
*/
export const processReturn = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processReturn.url(options),
    method: 'post',
})

processReturn.definition = {
    methods: ["post"],
    url: '/api/v1/pos/returns',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::processReturn
* @see app/Http/Controllers/Api/V1/PosApiController.php:46
* @route '/api/v1/pos/returns'
*/
processReturn.url = (options?: RouteQueryOptions) => {
    return processReturn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::processReturn
* @see app/Http/Controllers/Api/V1/PosApiController.php:46
* @route '/api/v1/pos/returns'
*/
processReturn.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processReturn.url(options),
    method: 'post',
})

const PosApiController = { products, store, processReturn }

export default PosApiController