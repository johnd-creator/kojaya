import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import thrBf742c from './thr'
/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/payrolls',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:34
* @route '/payrolls'
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
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
export const show = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/payrolls/{payroll}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
show.url = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payroll: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { payroll: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            payroll: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        payroll: typeof args.payroll === 'object'
        ? args.payroll.id
        : args.payroll,
    }

    return show.definition.url
            .replace('{payroll}', parsedArgs.payroll.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
show.get = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
show.head = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
const showForm = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
showForm.get = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:72
* @route '/payrolls/{payroll}'
*/
showForm.head = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:79
* @route '/payrolls/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/payrolls/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:79
* @route '/payrolls/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:79
* @route '/payrolls/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:79
* @route '/payrolls/generate'
*/
const generateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:79
* @route '/payrolls/generate'
*/
generateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

generate.form = generateForm

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
export const downloadPdf = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

downloadPdf.definition = {
    methods: ["get","head"],
    url: '/payrolls/{payroll}/download-pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.url = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payroll: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { payroll: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            payroll: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        payroll: typeof args.payroll === 'object'
        ? args.payroll.id
        : args.payroll,
    }

    return downloadPdf.definition.url
            .replace('{payroll}', parsedArgs.payroll.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.get = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.head = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadPdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
const downloadPdfForm = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdfForm.get = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:165
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdfForm.head = (args: { payroll: string | number | { id: string | number } } | [payroll: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

downloadPdf.form = downloadPdfForm

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
export const thr = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thr.url(options),
    method: 'get',
})

thr.definition = {
    methods: ["get","head"],
    url: '/payrolls/thr',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
thr.url = (options?: RouteQueryOptions) => {
    return thr.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
thr.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thr.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
thr.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thr.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
const thrForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thr.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
thrForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thr.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::thr
* @see app/Http/Controllers/PayrollController.php:187
* @route '/payrolls/thr'
*/
thrForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thr.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

thr.form = thrForm

/**
* @see \App\Http\Controllers\PayrollController::submitApproval
* @see app/Http/Controllers/PayrollController.php:329
* @route '/payrolls/submit-for-approval'
*/
export const submitApproval = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitApproval.url(options),
    method: 'post',
})

submitApproval.definition = {
    methods: ["post"],
    url: '/payrolls/submit-for-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::submitApproval
* @see app/Http/Controllers/PayrollController.php:329
* @route '/payrolls/submit-for-approval'
*/
submitApproval.url = (options?: RouteQueryOptions) => {
    return submitApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::submitApproval
* @see app/Http/Controllers/PayrollController.php:329
* @route '/payrolls/submit-for-approval'
*/
submitApproval.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitApproval.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::submitApproval
* @see app/Http/Controllers/PayrollController.php:329
* @route '/payrolls/submit-for-approval'
*/
const submitApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitApproval.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::submitApproval
* @see app/Http/Controllers/PayrollController.php:329
* @route '/payrolls/submit-for-approval'
*/
submitApprovalForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitApproval.url(options),
    method: 'post',
})

submitApproval.form = submitApprovalForm

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
export const exportBank = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportBank.url(args, options),
    method: 'get',
})

exportBank.definition = {
    methods: ["get","head"],
    url: '/payrolls/export/{batch}/bank-transfer',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBank.url = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: args.batch,
    }

    return exportBank.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBank.get = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportBank.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBank.head = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportBank.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
const exportBankForm = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportBank.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBankForm.get = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportBank.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBank
* @see app/Http/Controllers/PayrollController.php:366
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBankForm.head = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportBank.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportBank.form = exportBankForm

const payrolls = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    generate: Object.assign(generate, generate),
    downloadPdf: Object.assign(downloadPdf, downloadPdf),
    thr: Object.assign(thr, thrBf742c),
    submitApproval: Object.assign(submitApproval, submitApproval),
    exportBank: Object.assign(exportBank, exportBank),
}

export default payrolls