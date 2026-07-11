import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ExceptionReportController::index
* @see app/Http/Controllers/ExceptionReportController.php:12
* @route '/exceptions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExceptionReportController::index
* @see app/Http/Controllers/ExceptionReportController.php:12
* @route '/exceptions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExceptionReportController::index
* @see app/Http/Controllers/ExceptionReportController.php:12
* @route '/exceptions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExceptionReportController::index
* @see app/Http/Controllers/ExceptionReportController.php:12
* @route '/exceptions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExceptionReportController::data
* @see app/Http/Controllers/ExceptionReportController.php:21
* @route '/exceptions/data'
*/
export const data = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

data.definition = {
    methods: ["get","head"],
    url: '/exceptions/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExceptionReportController::data
* @see app/Http/Controllers/ExceptionReportController.php:21
* @route '/exceptions/data'
*/
data.url = (options?: RouteQueryOptions) => {
    return data.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExceptionReportController::data
* @see app/Http/Controllers/ExceptionReportController.php:21
* @route '/exceptions/data'
*/
data.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExceptionReportController::data
* @see app/Http/Controllers/ExceptionReportController.php:21
* @route '/exceptions/data'
*/
data.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: data.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExceptionReportController::module
* @see app/Http/Controllers/ExceptionReportController.php:28
* @route '/exceptions/{module}'
*/
export const module = (args: { module: string | number } | [module: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: module.url(args, options),
    method: 'get',
})

module.definition = {
    methods: ["get","head"],
    url: '/exceptions/{module}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExceptionReportController::module
* @see app/Http/Controllers/ExceptionReportController.php:28
* @route '/exceptions/{module}'
*/
module.url = (args: { module: string | number } | [module: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { module: args }
    }

    if (Array.isArray(args)) {
        args = {
            module: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        module: args.module,
    }

    return module.definition.url
            .replace('{module}', parsedArgs.module.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExceptionReportController::module
* @see app/Http/Controllers/ExceptionReportController.php:28
* @route '/exceptions/{module}'
*/
module.get = (args: { module: string | number } | [module: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: module.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExceptionReportController::module
* @see app/Http/Controllers/ExceptionReportController.php:28
* @route '/exceptions/{module}'
*/
module.head = (args: { module: string | number } | [module: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: module.url(args, options),
    method: 'head',
})

const ExceptionReportController = { index, data, module }

export default ExceptionReportController