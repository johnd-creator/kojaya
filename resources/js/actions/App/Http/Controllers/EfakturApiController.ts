import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EfakturApiController::submit
* @see app/Http/Controllers/EfakturApiController.php:11
* @route '/invoices/{invoice}/efaktur/api/submit'
*/
export const submit = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/invoices/{invoice}/efaktur/api/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EfakturApiController::submit
* @see app/Http/Controllers/EfakturApiController.php:11
* @route '/invoices/{invoice}/efaktur/api/submit'
*/
submit.url = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return submit.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturApiController::submit
* @see app/Http/Controllers/EfakturApiController.php:11
* @route '/invoices/{invoice}/efaktur/api/submit'
*/
submit.post = (args: { invoice: string | { id: string } } | [invoice: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EfakturApiController::status
* @see app/Http/Controllers/EfakturApiController.php:31
* @route '/invoices/efaktur/api/submissions/{submission}/status'
*/
export const status = (args: { submission: string | { id: string } } | [submission: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/invoices/efaktur/api/submissions/{submission}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturApiController::status
* @see app/Http/Controllers/EfakturApiController.php:31
* @route '/invoices/efaktur/api/submissions/{submission}/status'
*/
status.url = (args: { submission: string | { id: string } } | [submission: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { submission: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { submission: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            submission: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        submission: typeof args.submission === 'object'
        ? args.submission.id
        : args.submission,
    }

    return status.definition.url
            .replace('{submission}', parsedArgs.submission.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturApiController::status
* @see app/Http/Controllers/EfakturApiController.php:31
* @route '/invoices/efaktur/api/submissions/{submission}/status'
*/
status.get = (args: { submission: string | { id: string } } | [submission: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturApiController::status
* @see app/Http/Controllers/EfakturApiController.php:31
* @route '/invoices/efaktur/api/submissions/{submission}/status'
*/
status.head = (args: { submission: string | { id: string } } | [submission: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

const EfakturApiController = { submit, status }

export default EfakturApiController