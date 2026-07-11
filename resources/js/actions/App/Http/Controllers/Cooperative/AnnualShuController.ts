import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
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
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:18
* @route '/cooperative/shu'
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
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:40
* @route '/cooperative/shu/close'
*/
const closeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:40
* @route '/cooperative/shu/close'
*/
closeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

close.form = closeForm

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
export const requestRevision = (args: { period: number | { id: number } } | [period: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
requestRevision.url = (args: { period: number | { id: number } } | [period: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
requestRevision.post = (args: { period: number | { id: number } } | [period: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
const requestRevisionForm = (args: { period: number | { id: number } } | [period: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestRevision.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::requestRevision
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:56
* @route '/cooperative/shu/{period}/request-revision'
*/
requestRevisionForm.post = (args: { period: number | { id: number } } | [period: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestRevision.url(args, options),
    method: 'post',
})

requestRevision.form = requestRevisionForm

const AnnualShuController = { index, close, requestRevision }

export default AnnualShuController