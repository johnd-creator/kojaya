import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::store
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:17
* @route '/api/v1/dues/payments'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/dues/payments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::store
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:17
* @route '/api/v1/dues/payments'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::store
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:17
* @route '/api/v1/dues/payments'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::store
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:17
* @route '/api/v1/dues/payments'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::store
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:17
* @route '/api/v1/dues/payments'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::batch
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:43
* @route '/api/v1/dues/payments/batch'
*/
export const batch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batch.url(options),
    method: 'post',
})

batch.definition = {
    methods: ["post"],
    url: '/api/v1/dues/payments/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::batch
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:43
* @route '/api/v1/dues/payments/batch'
*/
batch.url = (options?: RouteQueryOptions) => {
    return batch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::batch
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:43
* @route '/api/v1/dues/payments/batch'
*/
batch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::batch
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:43
* @route '/api/v1/dues/payments/batch'
*/
const batchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: batch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::batch
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:43
* @route '/api/v1/dues/payments/batch'
*/
batchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: batch.url(options),
    method: 'post',
})

batch.form = batchForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::approve
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:36
* @route '/api/v1/dues/payments/{payment}/approve'
*/
export const approve = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/api/v1/dues/payments/{payment}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::approve
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:36
* @route '/api/v1/dues/payments/{payment}/approve'
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
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::approve
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:36
* @route '/api/v1/dues/payments/{payment}/approve'
*/
approve.post = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::approve
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:36
* @route '/api/v1/dues/payments/{payment}/approve'
*/
const approveForm = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativePaymentApiController::approve
* @see app/Http/Controllers/Api/V1/CooperativePaymentApiController.php:36
* @route '/api/v1/dues/payments/{payment}/approve'
*/
approveForm.post = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

const CooperativePaymentApiController = { store, batch, approve }

export default CooperativePaymentApiController