import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import attendance from './attendance'
import profile937a89 from './profile'
/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
export const geofence = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

geofence.definition = {
    methods: ["get","head"],
    url: '/ess/geofence',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
geofence.url = (options?: RouteQueryOptions) => {
    return geofence.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
geofence.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
geofence.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: geofence.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
const geofenceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
geofenceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:335
* @route '/ess/geofence'
*/
geofenceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

geofence.form = geofenceForm

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/ess',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::dashboard
* @see app/Http/Controllers/EssPortalController.php:16
* @route '/ess'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/ess/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::profile
* @see app/Http/Controllers/EssPortalController.php:51
* @route '/ess/profile'
*/
profileForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
export const payslips = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslips.url(options),
    method: 'get',
})

payslips.definition = {
    methods: ["get","head"],
    url: '/ess/payslips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
payslips.url = (options?: RouteQueryOptions) => {
    return payslips.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
payslips.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
payslips.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslips.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
const payslipsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
payslipsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::payslips
* @see app/Http/Controllers/EssPortalController.php:85
* @route '/ess/payslips'
*/
payslipsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslips.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payslips.form = payslipsForm

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
export const compliance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compliance.url(options),
    method: 'get',
})

compliance.definition = {
    methods: ["get","head"],
    url: '/ess/compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
compliance.url = (options?: RouteQueryOptions) => {
    return compliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
compliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
compliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: compliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
const complianceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
complianceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EssPortalController::compliance
* @see app/Http/Controllers/EssPortalController.php:101
* @route '/ess/compliance'
*/
complianceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: compliance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

compliance.form = complianceForm

const ess = {
    attendance: Object.assign(attendance, attendance),
    geofence: Object.assign(geofence, geofence),
    dashboard: Object.assign(dashboard, dashboard),
    profile: Object.assign(profile, profile937a89),
    payslips: Object.assign(payslips, payslips),
    compliance: Object.assign(compliance, compliance),
}

export default ess