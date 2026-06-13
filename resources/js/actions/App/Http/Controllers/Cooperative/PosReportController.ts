import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
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
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::index
* @see app/Http/Controllers/Cooperative/PosReportController.php:19
* @route '/cooperative/pos/reports'
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

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
export const exportCsv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportCsv.url(options),
    method: 'get',
})

exportCsv.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.url = (options?: RouteQueryOptions) => {
    return exportCsv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportCsv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportCsv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
const exportCsvForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsvForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:41
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsvForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportCsv.form = exportCsvForm

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
export const exportPdf = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
})

exportPdf.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
exportPdf.url = (options?: RouteQueryOptions) => {
    return exportPdf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
exportPdf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportPdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
exportPdf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportPdf.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
const exportPdfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
exportPdfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportPdf.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportPdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:51
* @route '/cooperative/pos/reports/export.pdf'
*/
exportPdfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportPdf.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportPdf.form = exportPdfForm

const PosReportController = { index, exportCsv, exportPdf }

export default PosReportController