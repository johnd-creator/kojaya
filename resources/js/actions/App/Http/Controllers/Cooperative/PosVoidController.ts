import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
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
* @see \App\Http\Controllers\Cooperative\PosVoidController::store
* @see app/Http/Controllers/Cooperative/PosVoidController.php:31
* @route '/cooperative/pos/transactions/{transaction}/void-request'
*/
const storeForm = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::store
* @see app/Http/Controllers/Cooperative/PosVoidController.php:31
* @route '/cooperative/pos/transactions/{transaction}/void-request'
*/
storeForm.post = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
export const process = (args: { voidRequest: number | { id: number } } | [voidRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
process.url = (args: { voidRequest: number | { id: number } } | [voidRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
process.post = (args: { voidRequest: number | { id: number } } | [voidRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
const processForm = (args: { voidRequest: number | { id: number } } | [voidRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: process.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::process
* @see app/Http/Controllers/Cooperative/PosVoidController.php:39
* @route '/cooperative/pos/void-requests/{voidRequest}/process'
*/
processForm.post = (args: { voidRequest: number | { id: number } } | [voidRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: process.url(args, options),
    method: 'post',
})

process.form = processForm

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

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosVoidController::index
* @see app/Http/Controllers/Cooperative/PosVoidController.php:18
* @route '/cooperative/pos/void-requests'
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

const PosVoidController = { store, process, index }

export default PosVoidController