import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
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

const MetricsController = { index }

export default MetricsController