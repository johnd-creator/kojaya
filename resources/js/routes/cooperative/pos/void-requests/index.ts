import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::store
* @see app/Http/Controllers/Cooperative/PosVoidController.php:31
* @route '/cooperative/pos/transactions/{transaction}/void-request'
*/
export const store = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/transactions/{transaction}/void-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::store
* @see app/Http/Controllers/Cooperative/PosVoidController.php:31
* @route '/cooperative/pos/transactions/{transaction}/void-request'
*/
store.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: args.transaction,
    }

    return store.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::store
* @see app/Http/Controllers/Cooperative/PosVoidController.php:31
* @route '/cooperative/pos/transactions/{transaction}/void-request'
*/
store.post = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
export const process = (args: { voidRequest: string | number | { id: string | number } } | [voidRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/cooperative/pos/void-requests/{voidRequest}/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
process.url = (args: { voidRequest: string | number | { id: string | number } } | [voidRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { voidRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { voidRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            voidRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        voidRequest: typeof args.voidRequest === 'object'
        ? args.voidRequest.id
        : args.voidRequest,
    }

    return process.definition.url
            .replace('{voidRequest}', parsedArgs.voidRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
process.post = (args: { voidRequest: string | number | { id: string | number } } | [voidRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/void-requests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const voidRequests = {
    store: Object.assign(store, store),
    process: Object.assign(process, process),
    index: Object.assign(index, index),
}

export default voidRequests