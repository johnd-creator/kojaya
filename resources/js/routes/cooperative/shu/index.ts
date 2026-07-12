import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/shu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:40
* @route '/cooperative/shu/close'
*/
export const close = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/cooperative/shu/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:40
* @route '/cooperative/shu/close'
*/
close.url = (options?: RouteQueryOptions) => {
    return close.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:40
* @route '/cooperative/shu/close'
*/
close.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
export const requestRevision = (args: { period: string | number | { id: string | number } } | [period: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

requestRevision.definition = {
    methods: ["post"],
    url: '/cooperative/shu/{period}/request-revision',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
requestRevision.url = (args: { period: string | number | { id: string | number } } | [period: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { period: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: typeof args.period === 'object'
        ? args.period.id
        : args.period,
    }

    return requestRevision.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
requestRevision.post = (args: { period: string | number | { id: string | number } } | [period: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

const shu = {
    index: Object.assign(index, index),
    close: Object.assign(close, close),
    requestRevision: Object.assign(requestRevision, requestRevision),
}

export default shu