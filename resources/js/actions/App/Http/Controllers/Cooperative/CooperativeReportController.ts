import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:29
* @route '/api/v1/reports/cooperative-summary'
*/
export const summary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/v1/reports/cooperative-summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:29
* @route '/api/v1/reports/cooperative-summary'
*/
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:29
* @route '/api/v1/reports/cooperative-summary'
*/
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:29
* @route '/api/v1/reports/cooperative-summary'
*/
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:36
* @route '/api/v1/reports/sales'
*/
export const sales = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sales.url(options),
    method: 'get',
})

sales.definition = {
    methods: ["get","head"],
    url: '/api/v1/reports/sales',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:36
* @route '/api/v1/reports/sales'
*/
sales.url = (options?: RouteQueryOptions) => {
    return sales.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:36
* @route '/api/v1/reports/sales'
*/
sales.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sales.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:36
* @route '/api/v1/reports/sales'
*/
sales.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sales.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::nplAging
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:50
* @route '/api/v1/reports/npl-aging'
*/
export const nplAging = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: nplAging.url(options),
    method: 'get',
})

nplAging.definition = {
    methods: ["get","head"],
    url: '/api/v1/reports/npl-aging',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::nplAging
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:50
* @route '/api/v1/reports/npl-aging'
*/
nplAging.url = (options?: RouteQueryOptions) => {
    return nplAging.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::nplAging
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:50
* @route '/api/v1/reports/npl-aging'
*/
nplAging.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: nplAging.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::nplAging
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:50
* @route '/api/v1/reports/npl-aging'
*/
nplAging.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: nplAging.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:22
* @route '/cooperative/reports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:22
* @route '/cooperative/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:22
* @route '/cooperative/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:22
* @route '/cooperative/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const CooperativeReportController = { summary, sales, nplAging, index }

export default CooperativeReportController