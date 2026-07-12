import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
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

const reports = {
    index: Object.assign(index, index),
}

export default reports