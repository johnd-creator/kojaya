import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
export const proof = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proof.url(options),
    method: 'post',
})

proof.definition = {
    methods: ["post"],
    url: '/member/payments/proof',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
proof.url = (options?: RouteQueryOptions) => {
    return proof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
proof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
const proofForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: proof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
proofForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: proof.url(options),
    method: 'post',
})

proof.form = proofForm

/**
* @see \App\Http\Controllers\MemberPortalController::intent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
export const intent = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: intent.url(options),
    method: 'post',
})

intent.definition = {
    methods: ["post"],
    url: '/member/payments/intent',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::intent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
intent.url = (options?: RouteQueryOptions) => {
    return intent.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::intent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
intent.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: intent.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::intent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
const intentForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: intent.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::intent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
intentForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: intent.url(options),
    method: 'post',
})

intent.form = intentForm

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
export const status = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/member/payments/{payment}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
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
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
status.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
status.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
const statusForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
statusForm.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::status
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
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

const payments = {
    proof: Object.assign(proof, proof),
    intent: Object.assign(intent, intent),
    status: Object.assign(status, status),
}

export default payments