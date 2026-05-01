import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
export const certificateCompliance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateCompliance.url(options),
    method: 'get',
})

certificateCompliance.definition = {
    methods: ["get","head"],
    url: '/api/reports/certificate-compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.url = (options?: RouteQueryOptions) => {
    return certificateCompliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificateCompliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
const certificateComplianceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
certificateComplianceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:13
* @route '/api/reports/certificate-compliance'
*/
certificateComplianceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

certificateCompliance.form = certificateComplianceForm

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
export const mcuCompliance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcuCompliance.url(options),
    method: 'get',
})

mcuCompliance.definition = {
    methods: ["get","head"],
    url: '/api/reports/mcu-compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.url = (options?: RouteQueryOptions) => {
    return mcuCompliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcuCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mcuCompliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
const mcuComplianceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
mcuComplianceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:49
* @route '/api/reports/mcu-compliance'
*/
mcuComplianceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

mcuCompliance.form = mcuComplianceForm

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
export const nonCompliantEmployees = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: nonCompliantEmployees.url(options),
    method: 'get',
})

nonCompliantEmployees.definition = {
    methods: ["get","head"],
    url: '/api/reports/non-compliant-employees',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.url = (options?: RouteQueryOptions) => {
    return nonCompliantEmployees.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: nonCompliantEmployees.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: nonCompliantEmployees.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
const nonCompliantEmployeesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: nonCompliantEmployees.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployeesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: nonCompliantEmployees.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:87
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployeesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: nonCompliantEmployees.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

nonCompliantEmployees.form = nonCompliantEmployeesForm

const ComplianceReportController = { certificateCompliance, mcuCompliance, nonCompliantEmployees }

export default ComplianceReportController