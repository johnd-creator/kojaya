import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:16
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
* @see app/Http/Controllers/ComplianceReportController.php:16
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.url = (options?: RouteQueryOptions) => {
    return certificateCompliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:16
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::certificateCompliance
* @see app/Http/Controllers/ComplianceReportController.php:16
* @route '/api/reports/certificate-compliance'
*/
certificateCompliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificateCompliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:52
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
* @see app/Http/Controllers/ComplianceReportController.php:52
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.url = (options?: RouteQueryOptions) => {
    return mcuCompliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:52
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcuCompliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::mcuCompliance
* @see app/Http/Controllers/ComplianceReportController.php:52
* @route '/api/reports/mcu-compliance'
*/
mcuCompliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mcuCompliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:90
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
* @see app/Http/Controllers/ComplianceReportController.php:90
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.url = (options?: RouteQueryOptions) => {
    return nonCompliantEmployees.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:90
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: nonCompliantEmployees.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ComplianceReportController::nonCompliantEmployees
* @see app/Http/Controllers/ComplianceReportController.php:90
* @route '/api/reports/non-compliant-employees'
*/
nonCompliantEmployees.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: nonCompliantEmployees.url(options),
    method: 'head',
})

const ComplianceReportController = { certificateCompliance, mcuCompliance, nonCompliantEmployees }

export default ComplianceReportController