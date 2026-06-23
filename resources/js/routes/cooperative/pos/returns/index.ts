import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
export const create = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/transactions/{transaction}/returns/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
create.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return create.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
create.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
create.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
const createForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
createForm.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::create
* @see app/Http/Controllers/Cooperative/PosReturnController.php:17
* @route '/cooperative/pos/transactions/{transaction}/returns/create'
*/
createForm.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::store
* @see app/Http/Controllers/Cooperative/PosReturnController.php:46
* @route '/cooperative/pos/transactions/{transaction}/returns'
*/
export const store = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/transactions/{transaction}/returns',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::store
* @see app/Http/Controllers/Cooperative/PosReturnController.php:46
* @route '/cooperative/pos/transactions/{transaction}/returns'
*/
store.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::store
* @see app/Http/Controllers/Cooperative/PosReturnController.php:46
* @route '/cooperative/pos/transactions/{transaction}/returns'
*/
store.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::store
* @see app/Http/Controllers/Cooperative/PosReturnController.php:46
* @route '/cooperative/pos/transactions/{transaction}/returns'
*/
const storeForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReturnController::store
* @see app/Http/Controllers/Cooperative/PosReturnController.php:46
* @route '/cooperative/pos/transactions/{transaction}/returns'
*/
storeForm.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const returns = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
}

export default returns