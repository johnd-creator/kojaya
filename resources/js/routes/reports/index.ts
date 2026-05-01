import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
export const consolidatedStats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedStats.url(options),
    method: 'get',
})

consolidatedStats.definition = {
    methods: ["get","head"],
    url: '/api/reports/consolidated-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
consolidatedStats.url = (options?: RouteQueryOptions) => {
    return consolidatedStats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
consolidatedStats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedStats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
consolidatedStats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: consolidatedStats.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
const consolidatedStatsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedStats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
consolidatedStatsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedStats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedStats
* @see app/Http/Controllers/ReportController.php:196
* @route '/api/reports/consolidated-stats'
*/
consolidatedStatsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedStats.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

consolidatedStats.form = consolidatedStatsForm

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
export const consolidatedPayroll = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedPayroll.url(options),
    method: 'get',
})

consolidatedPayroll.definition = {
    methods: ["get","head"],
    url: '/api/reports/consolidated-payroll',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
consolidatedPayroll.url = (options?: RouteQueryOptions) => {
    return consolidatedPayroll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
consolidatedPayroll.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedPayroll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
consolidatedPayroll.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: consolidatedPayroll.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
const consolidatedPayrollForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedPayroll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
consolidatedPayrollForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedPayroll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedPayroll
* @see app/Http/Controllers/ReportController.php:210
* @route '/api/reports/consolidated-payroll'
*/
consolidatedPayrollForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedPayroll.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

consolidatedPayroll.form = consolidatedPayrollForm

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
export const consolidatedAttendance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedAttendance.url(options),
    method: 'get',
})

consolidatedAttendance.definition = {
    methods: ["get","head"],
    url: '/api/reports/consolidated-attendance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
consolidatedAttendance.url = (options?: RouteQueryOptions) => {
    return consolidatedAttendance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
consolidatedAttendance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: consolidatedAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
consolidatedAttendance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: consolidatedAttendance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
const consolidatedAttendanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
consolidatedAttendanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::consolidatedAttendance
* @see app/Http/Controllers/ReportController.php:234
* @route '/api/reports/consolidated-attendance'
*/
consolidatedAttendanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: consolidatedAttendance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

consolidatedAttendance.form = consolidatedAttendanceForm

const reports = {
    consolidatedStats: Object.assign(consolidatedStats, consolidatedStats),
    consolidatedPayroll: Object.assign(consolidatedPayroll, consolidatedPayroll),
    consolidatedAttendance: Object.assign(consolidatedAttendance, consolidatedAttendance),
}

export default reports