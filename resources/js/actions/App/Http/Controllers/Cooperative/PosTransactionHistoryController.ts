import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:15
* @route '/cooperative/pos/transactions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:15
* @route '/cooperative/pos/transactions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:15
* @route '/cooperative/pos/transactions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:15
* @route '/cooperative/pos/transactions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:73
* @route '/cooperative/pos/transactions/{transaction}'
*/
export const show = (args: { transaction: string | number | { id: string | number } } | [transaction: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/transactions/{transaction}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:73
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.url = (args: { transaction: string | number | { id: string | number } } | [transaction: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return show.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:73
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.get = (args: { transaction: string | number | { id: string | number } } | [transaction: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:73
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.head = (args: { transaction: string | number | { id: string | number } } | [transaction: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const PosTransactionHistoryController = { index, show }

export default PosTransactionHistoryController