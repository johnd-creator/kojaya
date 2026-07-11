import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:31
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
* @see app/Http/Controllers/PayrollController.php:31
* @route '/payrolls'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:31
* @route '/payrolls'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::index
* @see app/Http/Controllers/PayrollController.php:31
* @route '/payrolls'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:69
* @route '/payrolls/{payroll}'
*/
export const show = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/payrolls/{payroll}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:69
* @route '/payrolls/{payroll}'
*/
show.url = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/PayrollController.php:69
* @route '/payrolls/{payroll}'
*/
show.get = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::show
* @see app/Http/Controllers/PayrollController.php:69
* @route '/payrolls/{payroll}'
*/
show.head = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:76
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
* @see app/Http/Controllers/PayrollController.php:76
* @route '/payrolls/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:76
* @route '/payrolls/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:89
* @route '/payrolls/{payroll}/download-pdf'
*/
export const downloadPdf = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

downloadPdf.definition = {
    methods: ["get","head"],
    url: '/payrolls/{payroll}/download-pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:89
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.url = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/PayrollController.php:89
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.get = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::downloadPdf
* @see app/Http/Controllers/PayrollController.php:89
* @route '/payrolls/{payroll}/download-pdf'
*/
downloadPdf.head = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadPdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::thrIndex
* @see app/Http/Controllers/PayrollController.php:111
* @route '/payrolls/thr'
*/
export const thrIndex = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thrIndex.url(options),
    method: 'get',
})

thrIndex.definition = {
    methods: ["get","head"],
    url: '/payrolls/thr',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::thrIndex
* @see app/Http/Controllers/PayrollController.php:111
* @route '/payrolls/thr'
*/
thrIndex.url = (options?: RouteQueryOptions) => {
    return thrIndex.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::thrIndex
* @see app/Http/Controllers/PayrollController.php:111
* @route '/payrolls/thr'
*/
thrIndex.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thrIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::thrIndex
* @see app/Http/Controllers/PayrollController.php:111
* @route '/payrolls/thr'
*/
thrIndex.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thrIndex.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PayrollController::previewThr
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
export const previewThr = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewThr.url(options),
    method: 'post',
})

previewThr.definition = {
    methods: ["post"],
    url: '/payrolls/thr/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::previewThr
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
previewThr.url = (options?: RouteQueryOptions) => {
    return previewThr.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::previewThr
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
previewThr.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewThr.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generateThr
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
export const generateThr = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generateThr.url(options),
    method: 'post',
})

generateThr.definition = {
    methods: ["post"],
    url: '/payrolls/thr/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::generateThr
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
generateThr.url = (options?: RouteQueryOptions) => {
    return generateThr.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::generateThr
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
generateThr.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generateThr.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::submitForApproval
* @see app/Http/Controllers/PayrollController.php:195
* @route '/payrolls/submit-for-approval'
*/
export const submitForApproval = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitForApproval.url(options),
    method: 'post',
})

submitForApproval.definition = {
    methods: ["post"],
    url: '/payrolls/submit-for-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::submitForApproval
* @see app/Http/Controllers/PayrollController.php:195
* @route '/payrolls/submit-for-approval'
*/
submitForApproval.url = (options?: RouteQueryOptions) => {
    return submitForApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::submitForApproval
* @see app/Http/Controllers/PayrollController.php:195
* @route '/payrolls/submit-for-approval'
*/
submitForApproval.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitForApproval.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBankTransfer
* @see app/Http/Controllers/PayrollController.php:232
* @route '/payrolls/export/{batch}/bank-transfer'
*/
export const exportBankTransfer = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportBankTransfer.url(args, options),
    method: 'get',
})

exportBankTransfer.definition = {
    methods: ["get","head"],
    url: '/payrolls/export/{batch}/bank-transfer',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PayrollController::exportBankTransfer
* @see app/Http/Controllers/PayrollController.php:232
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBankTransfer.url = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return exportBankTransfer.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::exportBankTransfer
* @see app/Http/Controllers/PayrollController.php:232
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBankTransfer.get = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportBankTransfer.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PayrollController::exportBankTransfer
* @see app/Http/Controllers/PayrollController.php:232
* @route '/payrolls/export/{batch}/bank-transfer'
*/
exportBankTransfer.head = (args: { batch: string | number } | [batch: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportBankTransfer.url(args, options),
    method: 'head',
})

const PayrollController = { index, show, generate, downloadPdf, thrIndex, previewThr, generateThr, submitForApproval, exportBankTransfer }

export default PayrollController