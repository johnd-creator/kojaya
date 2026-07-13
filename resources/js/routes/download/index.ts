import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:15
* @route '/download/payslip/{id}'
*/
export const payslip = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslip.url(args, options),
    method: 'get',
})

payslip.definition = {
    methods: ["get","head"],
    url: '/download/payslip/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:15
* @route '/download/payslip/{id}'
*/
payslip.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return payslip.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:15
* @route '/download/payslip/{id}'
*/
payslip.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:15
* @route '/download/payslip/{id}'
*/
payslip.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslip.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::mcu
* @see app/Http/Controllers/DocumentDownloadController.php:46
* @route '/download/mcu/{mcu}'
*/
export const mcu = (args: { mcu: string | number | { id: string | number } } | [mcu: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcu.url(args, options),
    method: 'get',
})

mcu.definition = {
    methods: ["get","head"],
    url: '/download/mcu/{mcu}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::mcu
* @see app/Http/Controllers/DocumentDownloadController.php:46
* @route '/download/mcu/{mcu}'
*/
mcu.url = (args: { mcu: string | number | { id: string | number } } | [mcu: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mcu: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { mcu: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            mcu: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        mcu: typeof args.mcu === 'object'
        ? args.mcu.id
        : args.mcu,
    }

    return mcu.definition.url
            .replace('{mcu}', parsedArgs.mcu.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::mcu
* @see app/Http/Controllers/DocumentDownloadController.php:46
* @route '/download/mcu/{mcu}'
*/
mcu.get = (args: { mcu: string | number | { id: string | number } } | [mcu: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcu.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::mcu
* @see app/Http/Controllers/DocumentDownloadController.php:46
* @route '/download/mcu/{mcu}'
*/
mcu.head = (args: { mcu: string | number | { id: string | number } } | [mcu: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mcu.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:71
* @route '/download/certificate/{employee}/{certificate}'
*/
export const certificate = (args: { employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } } | [employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

certificate.definition = {
    methods: ["get","head"],
    url: '/download/certificate/{employee}/{certificate}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:71
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.url = (args: { employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } } | [employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employee: args[0],
            certificate: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employee: typeof args.employee === 'object'
        ? args.employee.id
        : args.employee,
        certificate: typeof args.certificate === 'object'
        ? args.certificate.id
        : args.certificate,
    }

    return certificate.definition.url
            .replace('{employee}', parsedArgs.employee.toString())
            .replace('{certificate}', parsedArgs.certificate.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:71
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.get = (args: { employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } } | [employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:71
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.head = (args: { employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } } | [employee: string | number | { id: string | number }, certificate: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificate.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:100
* @route '/download/kyc/{memberId}/{documentId}'
*/
export const kyc = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: kyc.url(args, options),
    method: 'get',
})

kyc.definition = {
    methods: ["get","head"],
    url: '/download/kyc/{memberId}/{documentId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:100
* @route '/download/kyc/{memberId}/{documentId}'
*/
kyc.url = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            memberId: args[0],
            documentId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        memberId: args.memberId,
        documentId: args.documentId,
    }

    return kyc.definition.url
            .replace('{memberId}', parsedArgs.memberId.toString())
            .replace('{documentId}', parsedArgs.documentId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:100
* @route '/download/kyc/{memberId}/{documentId}'
*/
kyc.get = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: kyc.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:100
* @route '/download/kyc/{memberId}/{documentId}'
*/
kyc.head = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: kyc.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::cooperativeReceipt
* @see app/Http/Controllers/DocumentDownloadController.php:128
* @route '/download/cooperative-receipts/{receipt}'
*/
export const cooperativeReceipt = (args: { receipt: string | number | { id: string | number } } | [receipt: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cooperativeReceipt.url(args, options),
    method: 'get',
})

cooperativeReceipt.definition = {
    methods: ["get","head"],
    url: '/download/cooperative-receipts/{receipt}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::cooperativeReceipt
* @see app/Http/Controllers/DocumentDownloadController.php:128
* @route '/download/cooperative-receipts/{receipt}'
*/
cooperativeReceipt.url = (args: { receipt: string | number | { id: string | number } } | [receipt: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { receipt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { receipt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            receipt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        receipt: typeof args.receipt === 'object'
        ? args.receipt.id
        : args.receipt,
    }

    return cooperativeReceipt.definition.url
            .replace('{receipt}', parsedArgs.receipt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::cooperativeReceipt
* @see app/Http/Controllers/DocumentDownloadController.php:128
* @route '/download/cooperative-receipts/{receipt}'
*/
cooperativeReceipt.get = (args: { receipt: string | number | { id: string | number } } | [receipt: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cooperativeReceipt.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::cooperativeReceipt
* @see app/Http/Controllers/DocumentDownloadController.php:128
* @route '/download/cooperative-receipts/{receipt}'
*/
cooperativeReceipt.head = (args: { receipt: string | number | { id: string | number } } | [receipt: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cooperativeReceipt.url(args, options),
    method: 'head',
})

const download = {
    payslip: Object.assign(payslip, payslip),
    mcu: Object.assign(mcu, mcu),
    certificate: Object.assign(certificate, certificate),
    kyc: Object.assign(kyc, kyc),
    cooperativeReceipt: Object.assign(cooperativeReceipt, cooperativeReceipt),
}

export default download