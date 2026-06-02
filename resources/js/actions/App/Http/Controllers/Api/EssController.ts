import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/api/ess/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:33
* @route '/api/ess/dashboard'
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
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/api/ess/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:81
* @route '/api/ess/profile'
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
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:91
* @route '/api/ess/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/ess/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:91
* @route '/api/ess/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:91
* @route '/api/ess/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:91
* @route '/api/ess/profile'
*/
const updateProfileForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:91
* @route '/api/ess/profile'
*/
updateProfileForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateProfile.form = updateProfileForm

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
export const todayAttendance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: todayAttendance.url(options),
    method: 'get',
})

todayAttendance.definition = {
    methods: ["get","head"],
    url: '/api/ess/attendance/today',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
todayAttendance.url = (options?: RouteQueryOptions) => {
    return todayAttendance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
todayAttendance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: todayAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
todayAttendance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: todayAttendance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
const todayAttendanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: todayAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
todayAttendanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: todayAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:116
* @route '/api/ess/attendance/today'
*/
todayAttendanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: todayAttendance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

todayAttendance.form = todayAttendanceForm

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
export const attendanceHistory = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: attendanceHistory.url(options),
    method: 'get',
})

attendanceHistory.definition = {
    methods: ["get","head"],
    url: '/api/ess/attendance/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
attendanceHistory.url = (options?: RouteQueryOptions) => {
    return attendanceHistory.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
attendanceHistory.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: attendanceHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
attendanceHistory.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: attendanceHistory.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
const attendanceHistoryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
attendanceHistoryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:128
* @route '/api/ess/attendance/history'
*/
attendanceHistoryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: attendanceHistory.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

attendanceHistory.form = attendanceHistoryForm

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:138
* @route '/api/ess/attendance/check-in'
*/
export const checkIn = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

checkIn.definition = {
    methods: ["post"],
    url: '/api/ess/attendance/check-in',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:138
* @route '/api/ess/attendance/check-in'
*/
checkIn.url = (options?: RouteQueryOptions) => {
    return checkIn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:138
* @route '/api/ess/attendance/check-in'
*/
checkIn.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:138
* @route '/api/ess/attendance/check-in'
*/
const checkInForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:138
* @route '/api/ess/attendance/check-in'
*/
checkInForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

checkIn.form = checkInForm

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:180
* @route '/api/ess/attendance/check-out'
*/
export const checkOut = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

checkOut.definition = {
    methods: ["post"],
    url: '/api/ess/attendance/check-out',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:180
* @route '/api/ess/attendance/check-out'
*/
checkOut.url = (options?: RouteQueryOptions) => {
    return checkOut.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:180
* @route '/api/ess/attendance/check-out'
*/
checkOut.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:180
* @route '/api/ess/attendance/check-out'
*/
const checkOutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:180
* @route '/api/ess/attendance/check-out'
*/
checkOutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

checkOut.form = checkOutForm

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:257
* @route '/api/ess/attendance/correction'
*/
export const requestAttendanceCorrection = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestAttendanceCorrection.url(options),
    method: 'post',
})

requestAttendanceCorrection.definition = {
    methods: ["post"],
    url: '/api/ess/attendance/correction',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:257
* @route '/api/ess/attendance/correction'
*/
requestAttendanceCorrection.url = (options?: RouteQueryOptions) => {
    return requestAttendanceCorrection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:257
* @route '/api/ess/attendance/correction'
*/
requestAttendanceCorrection.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestAttendanceCorrection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:257
* @route '/api/ess/attendance/correction'
*/
const requestAttendanceCorrectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestAttendanceCorrection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:257
* @route '/api/ess/attendance/correction'
*/
requestAttendanceCorrectionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestAttendanceCorrection.url(options),
    method: 'post',
})

requestAttendanceCorrection.form = requestAttendanceCorrectionForm

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:267
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
export const approveAttendanceCorrection = (args: { attendanceCorrection: string | number | { id: string | number } } | [attendanceCorrection: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

approveAttendanceCorrection.definition = {
    methods: ["post"],
    url: '/api/ess/attendance/corrections/{attendanceCorrection}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:267
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
approveAttendanceCorrection.url = (args: { attendanceCorrection: string | number | { id: string | number } } | [attendanceCorrection: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { attendanceCorrection: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { attendanceCorrection: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            attendanceCorrection: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        attendanceCorrection: typeof args.attendanceCorrection === 'object'
        ? args.attendanceCorrection.id
        : args.attendanceCorrection,
    }

    return approveAttendanceCorrection.definition.url
            .replace('{attendanceCorrection}', parsedArgs.attendanceCorrection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:267
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
approveAttendanceCorrection.post = (args: { attendanceCorrection: string | number | { id: string | number } } | [attendanceCorrection: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:267
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
const approveAttendanceCorrectionForm = (args: { attendanceCorrection: string | number | { id: string | number } } | [attendanceCorrection: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:267
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
approveAttendanceCorrectionForm.post = (args: { attendanceCorrection: string | number | { id: string | number } } | [attendanceCorrection: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

approveAttendanceCorrection.form = approveAttendanceCorrectionForm

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
export const geofence = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

geofence.definition = {
    methods: ["get","head"],
    url: '/api/ess/geofence',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
geofence.url = (options?: RouteQueryOptions) => {
    return geofence.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
geofence.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
geofence.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: geofence.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
const geofenceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
*/
geofenceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:491
* @route '/api/ess/geofence'
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
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
export const shiftRoster = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shiftRoster.url(options),
    method: 'get',
})

shiftRoster.definition = {
    methods: ["get","head"],
    url: '/api/ess/shift-roster',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
shiftRoster.url = (options?: RouteQueryOptions) => {
    return shiftRoster.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
shiftRoster.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shiftRoster.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
shiftRoster.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shiftRoster.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
const shiftRosterForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shiftRoster.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
shiftRosterForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shiftRoster.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:220
* @route '/api/ess/shift-roster'
*/
shiftRosterForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shiftRoster.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

shiftRoster.form = shiftRosterForm

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
export const thrEntitlement = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thrEntitlement.url(options),
    method: 'get',
})

thrEntitlement.definition = {
    methods: ["get","head"],
    url: '/api/ess/thr/entitlement',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.url = (options?: RouteQueryOptions) => {
    return thrEntitlement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thrEntitlement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thrEntitlement.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
const thrEntitlementForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thrEntitlement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
thrEntitlementForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thrEntitlement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:237
* @route '/api/ess/thr/entitlement'
*/
thrEntitlementForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thrEntitlement.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

thrEntitlement.form = thrEntitlementForm

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
export const leaves = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaves.url(options),
    method: 'get',
})

leaves.definition = {
    methods: ["get","head"],
    url: '/api/ess/leaves',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
leaves.url = (options?: RouteQueryOptions) => {
    return leaves.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
leaves.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaves.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
leaves.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: leaves.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
const leavesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaves.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
leavesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaves.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:281
* @route '/api/ess/leaves'
*/
leavesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: leaves.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

leaves.form = leavesForm

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:295
* @route '/api/ess/leaves'
*/
export const storeLeave = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLeave.url(options),
    method: 'post',
})

storeLeave.definition = {
    methods: ["post"],
    url: '/api/ess/leaves',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:295
* @route '/api/ess/leaves'
*/
storeLeave.url = (options?: RouteQueryOptions) => {
    return storeLeave.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:295
* @route '/api/ess/leaves'
*/
storeLeave.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLeave.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:295
* @route '/api/ess/leaves'
*/
const storeLeaveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLeave.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:295
* @route '/api/ess/leaves'
*/
storeLeaveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLeave.url(options),
    method: 'post',
})

storeLeave.form = storeLeaveForm

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:326
* @route '/api/ess/leaves/{leave}/cancel'
*/
export const cancelLeave = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelLeave.url(args, options),
    method: 'post',
})

cancelLeave.definition = {
    methods: ["post"],
    url: '/api/ess/leaves/{leave}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:326
* @route '/api/ess/leaves/{leave}/cancel'
*/
cancelLeave.url = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { leave: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { leave: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            leave: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        leave: typeof args.leave === 'object'
        ? args.leave.id
        : args.leave,
    }

    return cancelLeave.definition.url
            .replace('{leave}', parsedArgs.leave.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:326
* @route '/api/ess/leaves/{leave}/cancel'
*/
cancelLeave.post = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelLeave.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:326
* @route '/api/ess/leaves/{leave}/cancel'
*/
const cancelLeaveForm = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelLeave.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:326
* @route '/api/ess/leaves/{leave}/cancel'
*/
cancelLeaveForm.post = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelLeave.url(args, options),
    method: 'post',
})

cancelLeave.form = cancelLeaveForm

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
export const overtime = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: overtime.url(options),
    method: 'get',
})

overtime.definition = {
    methods: ["get","head"],
    url: '/api/ess/overtime',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
overtime.url = (options?: RouteQueryOptions) => {
    return overtime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
overtime.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: overtime.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
overtime.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: overtime.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
const overtimeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: overtime.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
overtimeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: overtime.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:345
* @route '/api/ess/overtime'
*/
overtimeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: overtime.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

overtime.form = overtimeForm

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:356
* @route '/api/ess/overtime'
*/
export const storeOvertime = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeOvertime.url(options),
    method: 'post',
})

storeOvertime.definition = {
    methods: ["post"],
    url: '/api/ess/overtime',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:356
* @route '/api/ess/overtime'
*/
storeOvertime.url = (options?: RouteQueryOptions) => {
    return storeOvertime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:356
* @route '/api/ess/overtime'
*/
storeOvertime.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeOvertime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:356
* @route '/api/ess/overtime'
*/
const storeOvertimeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeOvertime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:356
* @route '/api/ess/overtime'
*/
storeOvertimeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeOvertime.url(options),
    method: 'post',
})

storeOvertime.form = storeOvertimeForm

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
export const reimbursements = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reimbursements.url(options),
    method: 'get',
})

reimbursements.definition = {
    methods: ["get","head"],
    url: '/api/ess/reimbursements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
reimbursements.url = (options?: RouteQueryOptions) => {
    return reimbursements.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
reimbursements.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reimbursements.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
reimbursements.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reimbursements.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
const reimbursementsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reimbursements.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
reimbursementsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reimbursements.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:398
* @route '/api/ess/reimbursements'
*/
reimbursementsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reimbursements.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reimbursements.form = reimbursementsForm

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:407
* @route '/api/ess/reimbursements'
*/
export const storeReimbursement = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeReimbursement.url(options),
    method: 'post',
})

storeReimbursement.definition = {
    methods: ["post"],
    url: '/api/ess/reimbursements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:407
* @route '/api/ess/reimbursements'
*/
storeReimbursement.url = (options?: RouteQueryOptions) => {
    return storeReimbursement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:407
* @route '/api/ess/reimbursements'
*/
storeReimbursement.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeReimbursement.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:407
* @route '/api/ess/reimbursements'
*/
const storeReimbursementForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeReimbursement.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:407
* @route '/api/ess/reimbursements'
*/
storeReimbursementForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeReimbursement.url(options),
    method: 'post',
})

storeReimbursement.form = storeReimbursementForm

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
export const payslips = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslips.url(options),
    method: 'get',
})

payslips.definition = {
    methods: ["get","head"],
    url: '/api/ess/payslips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
payslips.url = (options?: RouteQueryOptions) => {
    return payslips.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
payslips.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
payslips.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslips.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
const payslipsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
*/
payslipsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:444
* @route '/api/ess/payslips'
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
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
export const downloadPayslip = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPayslip.url(args, options),
    method: 'get',
})

downloadPayslip.definition = {
    methods: ["get","head"],
    url: '/api/ess/payslips/{payroll}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslip.url = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return downloadPayslip.definition.url
            .replace('{payroll}', parsedArgs.payroll.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslip.get = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPayslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslip.head = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadPayslip.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
const downloadPayslipForm = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPayslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslipForm.get = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPayslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:456
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslipForm.head = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPayslip.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

downloadPayslip.form = downloadPayslipForm

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
export const compliance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compliance.url(options),
    method: 'get',
})

compliance.definition = {
    methods: ["get","head"],
    url: '/api/ess/compliance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
compliance.url = (options?: RouteQueryOptions) => {
    return compliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
compliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
compliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: compliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
const complianceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
*/
complianceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:471
* @route '/api/ess/compliance'
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

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
export const notifications = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

notifications.definition = {
    methods: ["get","head"],
    url: '/api/ess/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
const notificationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
notificationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:483
* @route '/api/ess/notifications'
*/
notificationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notifications.form = notificationsForm

const EssController = { dashboard, profile, updateProfile, todayAttendance, attendanceHistory, checkIn, checkOut, requestAttendanceCorrection, approveAttendanceCorrection, geofence, shiftRoster, thrEntitlement, leaves, storeLeave, cancelLeave, overtime, storeOvertime, reimbursements, storeReimbursement, payslips, downloadPayslip, compliance, notifications }

export default EssController