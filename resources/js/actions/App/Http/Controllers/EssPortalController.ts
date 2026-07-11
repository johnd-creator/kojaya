import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\EssPortalController::updateProfile
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/ess/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EssPortalController::updateProfile
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EssPortalController::updateProfile
* @see app/Http/Controllers/EssPortalController.php:63
* @route '/ess/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

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

const EssPortalController = { dashboard, profile, updateProfile, payslips, compliance }

export default EssPortalController