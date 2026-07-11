import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
import pdf from './pdf'
/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
export const csv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

csv.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
csv.url = (options?: RouteQueryOptions) => {
    return csv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
csv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
csv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csv.url(options),
    method: 'head',
})

const exportMethod = {
    csv: Object.assign(csv, csv),
    pdf: Object.assign(pdf, pdf),
}

export default exportMethod