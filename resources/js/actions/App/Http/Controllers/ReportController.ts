import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
export const index = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
index.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return index.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
index.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
index.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
const indexForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
indexForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:21
* @route '/projects/{project}/api/reports'
*/
indexForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
export const payslip = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslip.url(args, options),
    method: 'get',
})

payslip.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/payslip/{employeeId}/{period}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
payslip.url = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            employeeId: args[1],
            period: args[2],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
        employeeId: args.employeeId,
        period: args.period,
    }

    return payslip.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
payslip.get = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
payslip.head = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslip.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
const payslipForm = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
payslipForm.get = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payslip
* @see app/Http/Controllers/ReportController.php:78
* @route '/projects/{project}/api/reports/payslip/{employeeId}/{period}'
*/
payslipForm.head = (args: { project: string | number, employeeId: string | number, period: string | number } | [project: string | number, employeeId: string | number, period: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
export const payrollSummary = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payrollSummary.url(args, options),
    method: 'get',
})

payrollSummary.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/payroll-summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
payrollSummary.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return payrollSummary.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
payrollSummary.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payrollSummary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
payrollSummary.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payrollSummary.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
const payrollSummaryForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollSummary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
payrollSummaryForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollSummary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollSummary
* @see app/Http/Controllers/ReportController.php:100
* @route '/projects/{project}/api/reports/payroll-summary'
*/
payrollSummaryForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollSummary.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payrollSummary.form = payrollSummaryForm

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
export const payrollDetail = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payrollDetail.url(args, options),
    method: 'get',
})

payrollDetail.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/payroll-detail',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
payrollDetail.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return payrollDetail.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
payrollDetail.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payrollDetail.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
payrollDetail.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payrollDetail.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
const payrollDetailForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollDetail.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
payrollDetailForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollDetail.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::payrollDetail
* @see app/Http/Controllers/ReportController.php:116
* @route '/projects/{project}/api/reports/payroll-detail'
*/
payrollDetailForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payrollDetail.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payrollDetail.form = payrollDetailForm

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
export const attendanceReport = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: attendanceReport.url(args, options),
    method: 'get',
})

attendanceReport.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/attendance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
attendanceReport.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return attendanceReport.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
attendanceReport.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: attendanceReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
attendanceReport.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: attendanceReport.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
const attendanceReportForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
attendanceReportForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::attendanceReport
* @see app/Http/Controllers/ReportController.php:132
* @route '/projects/{project}/api/reports/attendance'
*/
attendanceReportForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceReport.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

attendanceReport.form = attendanceReportForm

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
export const leaveReport = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaveReport.url(args, options),
    method: 'get',
})

leaveReport.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/leave',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
leaveReport.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return leaveReport.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
leaveReport.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaveReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
leaveReport.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: leaveReport.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
const leaveReportForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaveReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
leaveReportForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaveReport.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::leaveReport
* @see app/Http/Controllers/ReportController.php:147
* @route '/projects/{project}/api/reports/leave'
*/
leaveReportForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaveReport.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

leaveReport.form = leaveReportForm

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
export const certificateCompliance = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateCompliance.url(args, options),
    method: 'get',
})

certificateCompliance.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/certificate-compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
certificateCompliance.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return certificateCompliance.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
certificateCompliance.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
certificateCompliance.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificateCompliance.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
const certificateComplianceForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
certificateComplianceForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::certificateCompliance
* @see app/Http/Controllers/ReportController.php:163
* @route '/projects/{project}/api/reports/certificate-compliance'
*/
certificateComplianceForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateCompliance.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

certificateCompliance.form = certificateComplianceForm

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
export const mcuCompliance = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcuCompliance.url(args, options),
    method: 'get',
})

mcuCompliance.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/api/reports/mcu-compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
mcuCompliance.url = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: args.project,
    }

    return mcuCompliance.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
mcuCompliance.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mcuCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
mcuCompliance.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mcuCompliance.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
const mcuComplianceForm = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
mcuComplianceForm.get = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::mcuCompliance
* @see app/Http/Controllers/ReportController.php:178
* @route '/projects/{project}/api/reports/mcu-compliance'
*/
mcuComplianceForm.head = (args: { project: string | number } | [project: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mcuCompliance.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

mcuCompliance.form = mcuComplianceForm

const ReportController = { consolidatedStats, consolidatedPayroll, consolidatedAttendance, index, payslip, payrollSummary, payrollDetail, attendanceReport, leaveReport, certificateCompliance, mcuCompliance }

export default ReportController