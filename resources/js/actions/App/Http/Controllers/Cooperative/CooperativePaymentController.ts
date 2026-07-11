import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:23
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
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:23
* @route '/cooperative/payments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:23
* @route '/cooperative/payments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::index
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:23
* @route '/cooperative/payments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:54
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
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:54
* @route '/cooperative/payments'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::store
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:54
* @route '/cooperative/payments'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:109
* @route '/cooperative/payments/{payment}/approve'
*/
export const approve = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/cooperative/payments/{payment}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::approve
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:109
* @route '/cooperative/payments/{payment}/approve'
*/
approve.url = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:109
* @route '/cooperative/payments/{payment}/approve'
*/
approve.post = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::bulkApprove
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:73
* @route '/cooperative/payments/bulk-approve'
*/
export const bulkApprove = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkApprove.url(options),
    method: 'post',
})

bulkApprove.definition = {
    methods: ["post"],
    url: '/cooperative/payments/bulk-approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::bulkApprove
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:73
* @route '/cooperative/payments/bulk-approve'
*/
bulkApprove.url = (options?: RouteQueryOptions) => {
    return bulkApprove.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativePaymentController::bulkApprove
* @see app/Http/Controllers/Cooperative/CooperativePaymentController.php:73
* @route '/cooperative/payments/bulk-approve'
*/
bulkApprove.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkApprove.url(options),
    method: 'post',
})

const CooperativePaymentController = { index, store, approve, bulkApprove }

export default CooperativePaymentController