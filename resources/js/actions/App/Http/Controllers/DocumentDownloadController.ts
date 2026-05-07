import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:14
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
* @see app/Http/Controllers/DocumentDownloadController.php:14
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
* @see app/Http/Controllers/DocumentDownloadController.php:14
* @route '/download/payslip/{id}'
*/
payslip.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:14
* @route '/download/payslip/{id}'
*/
payslip.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslip.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:14
* @route '/download/payslip/{id}'
*/
const payslipForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:14
* @route '/download/payslip/{id}'
*/
payslipForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::payslip
* @see app/Http/Controllers/DocumentDownloadController.php:14
* @route '/download/payslip/{id}'
*/
payslipForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslip.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payslip.form = payslipForm

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
export const medicalCheckup = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: medicalCheckup.url(args, options),
    method: 'get',
})

medicalCheckup.definition = {
    methods: ["get","head"],
    url: '/download/mcu/{mcu}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
medicalCheckup.url = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return medicalCheckup.definition.url
            .replace('{mcu}', parsedArgs.mcu.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
medicalCheckup.get = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: medicalCheckup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
medicalCheckup.head = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: medicalCheckup.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
const medicalCheckupForm = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: medicalCheckup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
medicalCheckupForm.get = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: medicalCheckup.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::medicalCheckup
* @see app/Http/Controllers/DocumentDownloadController.php:45
* @route '/download/mcu/{mcu}'
*/
medicalCheckupForm.head = (args: { mcu: number | { id: number } } | [mcu: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: medicalCheckup.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

medicalCheckup.form = medicalCheckupForm

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
export const certificate = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

certificate.definition = {
    methods: ["get","head"],
    url: '/download/certificate/{employee}/{certificate}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.url = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.get = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
certificate.head = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificate.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
const certificateForm = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
certificateForm.get = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::certificate
* @see app/Http/Controllers/DocumentDownloadController.php:70
* @route '/download/certificate/{employee}/{certificate}'
*/
certificateForm.head = (args: { employee: number | { id: number }, certificate: number | { id: number } } | [employee: number | { id: number }, certificate: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificate.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

certificate.form = certificateForm

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:99
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
* @see app/Http/Controllers/DocumentDownloadController.php:99
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
* @see app/Http/Controllers/DocumentDownloadController.php:99
* @route '/download/kyc/{memberId}/{documentId}'
*/
kyc.get = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: kyc.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:99
* @route '/download/kyc/{memberId}/{documentId}'
*/
kyc.head = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: kyc.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:99
* @route '/download/kyc/{memberId}/{documentId}'
*/
const kycForm = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: kyc.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:99
* @route '/download/kyc/{memberId}/{documentId}'
*/
kycForm.get = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: kyc.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DocumentDownloadController::kyc
* @see app/Http/Controllers/DocumentDownloadController.php:99
* @route '/download/kyc/{memberId}/{documentId}'
*/
kycForm.head = (args: { memberId: string | number, documentId: string | number } | [memberId: string | number, documentId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: kyc.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

kyc.form = kycForm

const DocumentDownloadController = { payslip, medicalCheckup, certificate, kyc }

export default DocumentDownloadController