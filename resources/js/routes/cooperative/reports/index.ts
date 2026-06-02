import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
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
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
* @route '/cooperative/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
* @route '/cooperative/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
* @route '/cooperative/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
* @route '/cooperative/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
* @route '/cooperative/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeReportController::index
* @see app/Http/Controllers/Cooperative/CooperativeReportController.php:21
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

const reports = {
    index: Object.assign(index, index),
}

export default reports