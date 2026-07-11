import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:16
* @route '/api/v1/savings/categories'
*/
export const categories = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

categories.definition = {
    methods: ["get","head"],
    url: '/api/v1/savings/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:16
* @route '/api/v1/savings/categories'
*/
categories.url = (options?: RouteQueryOptions) => {
    return categories.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:16
* @route '/api/v1/savings/categories'
*/
categories.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:16
* @route '/api/v1/savings/categories'
*/
categories.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categories.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:37
* @route '/api/v1/savings/ledger'
*/
export const ledger = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(options),
    method: 'get',
})

ledger.definition = {
    methods: ["get","head"],
    url: '/api/v1/savings/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:37
* @route '/api/v1/savings/ledger'
*/
ledger.url = (options?: RouteQueryOptions) => {
    return ledger.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:37
* @route '/api/v1/savings/ledger'
*/
ledger.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:37
* @route '/api/v1/savings/ledger'
*/
ledger.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ledger.url(options),
    method: 'head',
})

const SavingsApiController = { categories, ledger }

export default SavingsApiController