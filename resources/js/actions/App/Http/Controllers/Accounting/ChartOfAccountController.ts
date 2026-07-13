import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/chart-of-accounts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::index
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:15
* @route '/finance/chart-of-accounts'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::store
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:31
* @route '/finance/chart-of-accounts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/finance/chart-of-accounts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::store
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:31
* @route '/finance/chart-of-accounts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::store
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:31
* @route '/finance/chart-of-accounts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::store
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:31
* @route '/finance/chart-of-accounts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Accounting\ChartOfAccountController::store
* @see app/Http/Controllers/Accounting/ChartOfAccountController.php:31
* @route '/finance/chart-of-accounts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const ChartOfAccountController = { index, store }

export default ChartOfAccountController