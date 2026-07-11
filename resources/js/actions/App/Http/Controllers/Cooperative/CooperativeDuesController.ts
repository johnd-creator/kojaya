import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:22
* @route '/cooperative/dues'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/dues',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:22
* @route '/cooperative/dues'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:22
* @route '/cooperative/dues'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:22
* @route '/cooperative/dues'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:108
* @route '/cooperative/dues/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/cooperative/dues/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:108
* @route '/cooperative/dues/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:108
* @route '/cooperative/dues/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:115
* @route '/cooperative/dues/mark-paid'
*/
export const markPaid = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markPaid.url(options),
    method: 'post',
})

markPaid.definition = {
    methods: ["post"],
    url: '/cooperative/dues/mark-paid',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:115
* @route '/cooperative/dues/mark-paid'
*/
markPaid.url = (options?: RouteQueryOptions) => {
    return markPaid.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:115
* @route '/cooperative/dues/mark-paid'
*/
markPaid.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markPaid.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markUnpaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:160
* @route '/cooperative/dues/{invoice}/mark-unpaid'
*/
export const markUnpaid = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markUnpaid.url(args, options),
    method: 'post',
})

markUnpaid.definition = {
    methods: ["post"],
    url: '/cooperative/dues/{invoice}/mark-unpaid',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markUnpaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:160
* @route '/cooperative/dues/{invoice}/mark-unpaid'
*/
markUnpaid.url = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return markUnpaid.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markUnpaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:160
* @route '/cooperative/dues/{invoice}/mark-unpaid'
*/
markUnpaid.post = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markUnpaid.url(args, options),
    method: 'post',
})

const CooperativeDuesController = { index, generate, markPaid, markUnpaid }

export default CooperativeDuesController