import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PettyCashTransactionController::store
* @see app/Http/Controllers/PettyCashTransactionController.php:13
* @route '/petty-cash/transactions'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/petty-cash/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PettyCashTransactionController::store
* @see app/Http/Controllers/PettyCashTransactionController.php:13
* @route '/petty-cash/transactions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashTransactionController::store
* @see app/Http/Controllers/PettyCashTransactionController.php:13
* @route '/petty-cash/transactions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const PettyCashTransactionController = { store }

export default PettyCashTransactionController