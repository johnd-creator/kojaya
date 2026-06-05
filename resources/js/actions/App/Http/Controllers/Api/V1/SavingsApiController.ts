import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
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
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
categories.url = (options?: RouteQueryOptions) => {
    return categories.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
categories.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
categories.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categories.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
const categoriesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
categoriesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::categories
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:13
* @route '/api/v1/savings/categories'
*/
categoriesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

categories.form = categoriesForm

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
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
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
ledger.url = (options?: RouteQueryOptions) => {
    return ledger.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
ledger.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ledger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
ledger.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ledger.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
const ledgerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
ledgerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\SavingsApiController::ledger
* @see app/Http/Controllers/Api/V1/SavingsApiController.php:34
* @route '/api/v1/savings/ledger'
*/
ledgerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ledger.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ledger.form = ledgerForm

const SavingsApiController = { categories, ledger }

export default SavingsApiController