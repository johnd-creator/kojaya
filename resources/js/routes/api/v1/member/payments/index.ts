import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
export const status = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
status.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
status.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
status.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
const statusForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
statusForm.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::status
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:369
* @route '/api/v1/member/payments/{payment}/status'
*/
statusForm.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

status.form = statusForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
export const qrisImage = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrisImage.url(args, options),
    method: 'get',
})

qrisImage.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}/qris-image',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return qrisImage.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrisImage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: qrisImage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
const qrisImageForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qrisImage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImageForm.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qrisImage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:394
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImageForm.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qrisImage.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

qrisImage.form = qrisImageForm

const payments = {
    status: Object.assign(status, status),
    qrisImage: Object.assign(qrisImage, qrisImage),
}

export default payments