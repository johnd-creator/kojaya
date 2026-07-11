import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:36
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
* @see app/Http/Controllers/Api/EssController.php:36
* @route '/api/ess/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:36
* @route '/api/ess/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::dashboard
* @see app/Http/Controllers/Api/EssController.php:36
* @route '/api/ess/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:84
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
* @see app/Http/Controllers/Api/EssController.php:84
* @route '/api/ess/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:84
* @route '/api/ess/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::profile
* @see app/Http/Controllers/Api/EssController.php:84
* @route '/api/ess/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:94
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
* @see app/Http/Controllers/Api/EssController.php:94
* @route '/api/ess/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::updateProfile
* @see app/Http/Controllers/Api/EssController.php:94
* @route '/api/ess/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:119
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
* @see app/Http/Controllers/Api/EssController.php:119
* @route '/api/ess/attendance/today'
*/
todayAttendance.url = (options?: RouteQueryOptions) => {
    return todayAttendance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:119
* @route '/api/ess/attendance/today'
*/
todayAttendance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: todayAttendance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::todayAttendance
* @see app/Http/Controllers/Api/EssController.php:119
* @route '/api/ess/attendance/today'
*/
todayAttendance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: todayAttendance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:131
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
* @see app/Http/Controllers/Api/EssController.php:131
* @route '/api/ess/attendance/history'
*/
attendanceHistory.url = (options?: RouteQueryOptions) => {
    return attendanceHistory.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:131
* @route '/api/ess/attendance/history'
*/
attendanceHistory.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: attendanceHistory.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::attendanceHistory
* @see app/Http/Controllers/Api/EssController.php:131
* @route '/api/ess/attendance/history'
*/
attendanceHistory.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: attendanceHistory.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:141
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
* @see app/Http/Controllers/Api/EssController.php:141
* @route '/api/ess/attendance/check-in'
*/
checkIn.url = (options?: RouteQueryOptions) => {
    return checkIn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::checkIn
* @see app/Http/Controllers/Api/EssController.php:141
* @route '/api/ess/attendance/check-in'
*/
checkIn.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:183
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
* @see app/Http/Controllers/Api/EssController.php:183
* @route '/api/ess/attendance/check-out'
*/
checkOut.url = (options?: RouteQueryOptions) => {
    return checkOut.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::checkOut
* @see app/Http/Controllers/Api/EssController.php:183
* @route '/api/ess/attendance/check-out'
*/
checkOut.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:260
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
* @see app/Http/Controllers/Api/EssController.php:260
* @route '/api/ess/attendance/correction'
*/
requestAttendanceCorrection.url = (options?: RouteQueryOptions) => {
    return requestAttendanceCorrection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::requestAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:260
* @route '/api/ess/attendance/correction'
*/
requestAttendanceCorrection.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestAttendanceCorrection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:270
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
export const approveAttendanceCorrection = (args: { attendanceCorrection: number | { id: number } } | [attendanceCorrection: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

approveAttendanceCorrection.definition = {
    methods: ["post"],
    url: '/api/ess/attendance/corrections/{attendanceCorrection}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\EssController::approveAttendanceCorrection
* @see app/Http/Controllers/Api/EssController.php:270
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
approveAttendanceCorrection.url = (args: { attendanceCorrection: number | { id: number } } | [attendanceCorrection: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/EssController.php:270
* @route '/api/ess/attendance/corrections/{attendanceCorrection}/approve'
*/
approveAttendanceCorrection.post = (args: { attendanceCorrection: number | { id: number } } | [attendanceCorrection: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveAttendanceCorrection.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:494
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
* @see app/Http/Controllers/Api/EssController.php:494
* @route '/api/ess/geofence'
*/
geofence.url = (options?: RouteQueryOptions) => {
    return geofence.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:494
* @route '/api/ess/geofence'
*/
geofence.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::geofence
* @see app/Http/Controllers/Api/EssController.php:494
* @route '/api/ess/geofence'
*/
geofence.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: geofence.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:223
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
* @see app/Http/Controllers/Api/EssController.php:223
* @route '/api/ess/shift-roster'
*/
shiftRoster.url = (options?: RouteQueryOptions) => {
    return shiftRoster.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:223
* @route '/api/ess/shift-roster'
*/
shiftRoster.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shiftRoster.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::shiftRoster
* @see app/Http/Controllers/Api/EssController.php:223
* @route '/api/ess/shift-roster'
*/
shiftRoster.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shiftRoster.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:240
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
* @see app/Http/Controllers/Api/EssController.php:240
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.url = (options?: RouteQueryOptions) => {
    return thrEntitlement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:240
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thrEntitlement.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::thrEntitlement
* @see app/Http/Controllers/Api/EssController.php:240
* @route '/api/ess/thr/entitlement'
*/
thrEntitlement.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thrEntitlement.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:284
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
* @see app/Http/Controllers/Api/EssController.php:284
* @route '/api/ess/leaves'
*/
leaves.url = (options?: RouteQueryOptions) => {
    return leaves.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:284
* @route '/api/ess/leaves'
*/
leaves.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaves.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::leaves
* @see app/Http/Controllers/Api/EssController.php:284
* @route '/api/ess/leaves'
*/
leaves.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: leaves.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:298
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
* @see app/Http/Controllers/Api/EssController.php:298
* @route '/api/ess/leaves'
*/
storeLeave.url = (options?: RouteQueryOptions) => {
    return storeLeave.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeLeave
* @see app/Http/Controllers/Api/EssController.php:298
* @route '/api/ess/leaves'
*/
storeLeave.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLeave.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::cancelLeave
* @see app/Http/Controllers/Api/EssController.php:329
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
* @see app/Http/Controllers/Api/EssController.php:329
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
* @see app/Http/Controllers/Api/EssController.php:329
* @route '/api/ess/leaves/{leave}/cancel'
*/
cancelLeave.post = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelLeave.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:348
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
* @see app/Http/Controllers/Api/EssController.php:348
* @route '/api/ess/overtime'
*/
overtime.url = (options?: RouteQueryOptions) => {
    return overtime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:348
* @route '/api/ess/overtime'
*/
overtime.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: overtime.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::overtime
* @see app/Http/Controllers/Api/EssController.php:348
* @route '/api/ess/overtime'
*/
overtime.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: overtime.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:359
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
* @see app/Http/Controllers/Api/EssController.php:359
* @route '/api/ess/overtime'
*/
storeOvertime.url = (options?: RouteQueryOptions) => {
    return storeOvertime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeOvertime
* @see app/Http/Controllers/Api/EssController.php:359
* @route '/api/ess/overtime'
*/
storeOvertime.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeOvertime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:401
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
* @see app/Http/Controllers/Api/EssController.php:401
* @route '/api/ess/reimbursements'
*/
reimbursements.url = (options?: RouteQueryOptions) => {
    return reimbursements.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:401
* @route '/api/ess/reimbursements'
*/
reimbursements.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reimbursements.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::reimbursements
* @see app/Http/Controllers/Api/EssController.php:401
* @route '/api/ess/reimbursements'
*/
reimbursements.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reimbursements.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:410
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
* @see app/Http/Controllers/Api/EssController.php:410
* @route '/api/ess/reimbursements'
*/
storeReimbursement.url = (options?: RouteQueryOptions) => {
    return storeReimbursement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::storeReimbursement
* @see app/Http/Controllers/Api/EssController.php:410
* @route '/api/ess/reimbursements'
*/
storeReimbursement.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeReimbursement.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:447
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
* @see app/Http/Controllers/Api/EssController.php:447
* @route '/api/ess/payslips'
*/
payslips.url = (options?: RouteQueryOptions) => {
    return payslips.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:447
* @route '/api/ess/payslips'
*/
payslips.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payslips.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::payslips
* @see app/Http/Controllers/Api/EssController.php:447
* @route '/api/ess/payslips'
*/
payslips.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payslips.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:459
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
* @see app/Http/Controllers/Api/EssController.php:459
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
* @see app/Http/Controllers/Api/EssController.php:459
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslip.get = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPayslip.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::downloadPayslip
* @see app/Http/Controllers/Api/EssController.php:459
* @route '/api/ess/payslips/{payroll}/download'
*/
downloadPayslip.head = (args: { payroll: number | { id: number } } | [payroll: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadPayslip.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:474
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
* @see app/Http/Controllers/Api/EssController.php:474
* @route '/api/ess/compliance'
*/
compliance.url = (options?: RouteQueryOptions) => {
    return compliance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:474
* @route '/api/ess/compliance'
*/
compliance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compliance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::compliance
* @see app/Http/Controllers/Api/EssController.php:474
* @route '/api/ess/compliance'
*/
compliance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: compliance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:486
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
* @see app/Http/Controllers/Api/EssController.php:486
* @route '/api/ess/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:486
* @route '/api/ess/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\EssController::notifications
* @see app/Http/Controllers/Api/EssController.php:486
* @route '/api/ess/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

const EssController = { dashboard, profile, updateProfile, todayAttendance, attendanceHistory, checkIn, checkOut, requestAttendanceCorrection, approveAttendanceCorrection, geofence, shiftRoster, thrEntitlement, leaves, storeLeave, cancelLeave, overtime, storeOvertime, reimbursements, storeReimbursement, payslips, downloadPayslip, compliance, notifications }

export default EssController