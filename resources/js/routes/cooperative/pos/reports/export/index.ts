import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
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
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
csv.url = (options?: RouteQueryOptions) => {
    return csv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
csv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
csv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
const csvForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
csvForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::csv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
csvForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: csv.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

csv.form = csvForm

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
export const pdf = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(options),
    method: 'get',
})

pdf.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
pdf.url = (options?: RouteQueryOptions) => {
    return pdf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
pdf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
pdf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pdf.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
const pdfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
pdfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
pdfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pdf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pdf.form = pdfForm

const exportMethod = {
    csv: Object.assign(csv, csv),
    pdf: Object.assign(pdf, pdf),
}

export default exportMethod