import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
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
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::index
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:13
* @route '/cooperative/pos/transactions'
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
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
export const show = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/transactions/{transaction}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
show.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
const showForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
showForm.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosTransactionHistoryController::show
* @see app/Http/Controllers/Cooperative/PosTransactionHistoryController.php:33
* @route '/cooperative/pos/transactions/{transaction}'
*/
showForm.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const PosTransactionHistoryController = { index, show }

export default PosTransactionHistoryController