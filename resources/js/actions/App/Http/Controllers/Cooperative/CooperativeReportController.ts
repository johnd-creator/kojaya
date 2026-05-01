import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
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
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
const summaryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
summaryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::summary
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:27
* @route '/api/v1/reports/cooperative-summary'
*/
summaryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary.form = summaryForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
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
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
sales.url = (options?: RouteQueryOptions) => {
    return sales.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
sales.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sales.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
sales.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sales.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
const salesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sales.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
salesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sales.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::sales
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:34
* @route '/api/v1/reports/sales'
*/
salesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sales.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

sales.form = salesForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
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
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:20
* @route '/cooperative/reports'
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

const CooperativeReportController = { summary, sales, index }

export default CooperativeReportController