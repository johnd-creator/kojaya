import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitoring/metrics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::index
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
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

const MetricsController = { index }

export default MetricsController