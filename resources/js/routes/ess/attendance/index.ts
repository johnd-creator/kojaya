import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:235
* @route '/ess/attendance/check-in'
*/
export const checkIn = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

checkIn.definition = {
    methods: ["post"],
    url: '/ess/attendance/check-in',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:235
* @route '/ess/attendance/check-in'
*/
checkIn.url = (options?: RouteQueryOptions) => {
    return checkIn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:235
* @route '/ess/attendance/check-in'
*/
checkIn.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:235
* @route '/ess/attendance/check-in'
*/
const checkInForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:235
* @route '/ess/attendance/check-in'
*/
checkInForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

checkIn.form = checkInForm

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:287
* @route '/ess/attendance/check-out'
*/
export const checkOut = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

checkOut.definition = {
    methods: ["post"],
    url: '/ess/attendance/check-out',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:287
* @route '/ess/attendance/check-out'
*/
checkOut.url = (options?: RouteQueryOptions) => {
    return checkOut.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:287
* @route '/ess/attendance/check-out'
*/
checkOut.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:287
* @route '/ess/attendance/check-out'
*/
const checkOutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:287
* @route '/ess/attendance/check-out'
*/
checkOutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

checkOut.form = checkOutForm

const attendance = {
    checkIn: Object.assign(checkIn, checkIn),
    checkOut: Object.assign(checkOut, checkOut),
}

export default attendance