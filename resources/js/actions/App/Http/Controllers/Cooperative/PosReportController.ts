import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
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

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
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
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.url = (options?: RouteQueryOptions) => {
    return exportCsv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportCsv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::exportCsv
* @see app/Http/Controllers/Cooperative/PosReportController.php:50
* @route '/cooperative/pos/reports/export.csv'
*/
exportCsv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportCsv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueuePdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
export const enqueuePdf = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueuePdf.url(options),
    method: 'post',
})

enqueuePdf.definition = {
    methods: ["post"],
    url: '/cooperative/pos/reports/export.pdf',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueuePdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
enqueuePdf.url = (options?: RouteQueryOptions) => {
    return enqueuePdf.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueuePdf
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
enqueuePdf.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueuePdf.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfStatus
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
export const pdfStatus = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdfStatus.url(args, options),
    method: 'get',
})

pdfStatus.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf/jobs/{job}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfStatus
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
pdfStatus.url = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'uuid' in args) {
        args = { job: args.uuid }
    }

    if (Array.isArray(args)) {
        args = {
            job: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job: typeof args.job === 'object'
        ? args.job.uuid
        : args.job,
    }

    return pdfStatus.definition.url
            .replace('{job}', parsedArgs.job.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfStatus
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
pdfStatus.get = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdfStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfStatus
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
pdfStatus.head = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pdfStatus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfDownload
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
export const pdfDownload = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdfDownload.url(args, options),
    method: 'get',
})

pdfDownload.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf/jobs/{job}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfDownload
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
pdfDownload.url = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'uuid' in args) {
        args = { job: args.uuid }
    }

    if (Array.isArray(args)) {
        args = {
            job: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job: typeof args.job === 'object'
        ? args.job.uuid
        : args.job,
    }

    return pdfDownload.definition.url
            .replace('{job}', parsedArgs.job.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfDownload
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
pdfDownload.get = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pdfDownload.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::pdfDownload
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
pdfDownload.head = (args: { job: string | number | { uuid: string | number } } | [job: string | number | { uuid: string | number } ] | string | number | { uuid: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pdfDownload.url(args, options),
    method: 'head',
})

const PosReportController = { index, exportCsv, enqueuePdf, pdfStatus, pdfDownload }

export default PosReportController