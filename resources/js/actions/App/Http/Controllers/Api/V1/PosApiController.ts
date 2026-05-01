import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
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
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
products.url = (options?: RouteQueryOptions) => {
    return products.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
products.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
products.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: products.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
const productsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
productsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::products
* @see app/Http/Controllers/Api/V1/PosApiController.php:14
* @route '/api/v1/pos/products'
*/
productsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

products.form = productsForm

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:35
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
* @see app/Http/Controllers/Api/V1/PosApiController.php:35
* @route '/api/v1/pos/transactions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:35
* @route '/api/v1/pos/transactions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:35
* @route '/api/v1/pos/transactions'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\PosApiController::store
* @see app/Http/Controllers/Api/V1/PosApiController.php:35
* @route '/api/v1/pos/transactions'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const PosApiController = { products, store }

export default PosApiController