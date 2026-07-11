import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueue
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
export const enqueue = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueue.url(options),
    method: 'post',
})

enqueue.definition = {
    methods: ["post"],
    url: '/cooperative/pos/reports/export.pdf',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueue
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
enqueue.url = (options?: RouteQueryOptions) => {
    return enqueue.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueue
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
enqueue.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: enqueue.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueue
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
const enqueueForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: enqueue.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::enqueue
* @see app/Http/Controllers/Cooperative/PosReportController.php:60
* @route '/cooperative/pos/reports/export.pdf'
*/
enqueueForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: enqueue.url(options),
    method: 'post',
})

enqueue.form = enqueueForm

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
export const status = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf/jobs/{job}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
status.url = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{job}', parsedArgs.job.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
status.get = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
status.head = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
const statusForm = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
statusForm.get = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::status
* @see app/Http/Controllers/Cooperative/PosReportController.php:98
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/status'
*/
statusForm.head = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

status.form = statusForm

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
export const download = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/reports/export.pdf/jobs/{job}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
download.url = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions) => {
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

    return download.definition.url
            .replace('{job}', parsedArgs.job.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
download.get = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
download.head = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
const downloadForm = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
downloadForm.get = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosReportController::download
* @see app/Http/Controllers/Cooperative/PosReportController.php:117
* @route '/cooperative/pos/reports/export.pdf/jobs/{job}/download'
*/
downloadForm.head = (args: { job: string | { uuid: string } } | [job: string | { uuid: string } ] | string | { uuid: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

download.form = downloadForm

const pdf = {
    enqueue: Object.assign(enqueue, enqueue),
    status: Object.assign(status, status),
    download: Object.assign(download, download),
}

export default pdf