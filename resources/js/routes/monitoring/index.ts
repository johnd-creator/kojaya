import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
export const health = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: health.url(options),
    method: 'get',
})

health.definition = {
    methods: ["get","head"],
    url: '/monitoring/health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
health.url = (options?: RouteQueryOptions) => {
    return health.definition.url + queryParams(options)
}

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
health.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: health.url(options),
    method: 'get',
})

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
health.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: health.url(options),
    method: 'head',
})

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
const healthForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: health.url(options),
    method: 'get',
})

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
healthForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: health.url(options),
    method: 'get',
})

/**
* @see routes/web.php:340
* @route '/monitoring/health'
*/
healthForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: health.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

health.form = healthForm

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
export const metrics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: metrics.url(options),
    method: 'get',
})

metrics.definition = {
    methods: ["get","head"],
    url: '/monitoring/metrics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
metrics.url = (options?: RouteQueryOptions) => {
    return metrics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
metrics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: metrics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
metrics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: metrics.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
const metricsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: metrics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
metricsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: metrics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Monitoring\MetricsController::metrics
* @see app/Http/Controllers/Monitoring/MetricsController.php:11
* @route '/monitoring/metrics'
*/
metricsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: metrics.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

metrics.form = metricsForm

const monitoring = {
    health: Object.assign(health, health),
    metrics: Object.assign(metrics, metrics),
}

export default monitoring