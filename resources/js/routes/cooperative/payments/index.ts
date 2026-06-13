import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/payments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:19
* @route '/cooperative/payments'
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
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:38
* @route '/cooperative/payments'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/payments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:38
* @route '/cooperative/payments'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:38
* @route '/cooperative/payments'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:38
* @route '/cooperative/payments'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:38
* @route '/cooperative/payments'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:57
* @route '/cooperative/payments/{payment}/approve'
*/
export const approve = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/cooperative/payments/{payment}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:57
* @route '/cooperative/payments/{payment}/approve'
*/
approve.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { payment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            payment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        payment: typeof args.payment === 'object'
        ? args.payment.id
        : args.payment,
    }

    return approve.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:57
* @route '/cooperative/payments/{payment}/approve'
*/
approve.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:57
* @route '/cooperative/payments/{payment}/approve'
*/
const approveForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:57
* @route '/cooperative/payments/{payment}/approve'
*/
approveForm.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

const payments = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    approve: Object.assign(approve, approve),
}

export default payments