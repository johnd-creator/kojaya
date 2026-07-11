import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
import exportMethod from './export'
/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:26
* @route '/cooperative/pos/reports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:26
* @route '/cooperative/pos/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:26
* @route '/cooperative/pos/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:26
* @route '/cooperative/pos/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const reports = {
    index: Object.assign(index, index),
    export: Object.assign(exportMethod, exportMethod),
}

export default reports