import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
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

const chartOfAccounts = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
}

export default chartOfAccounts