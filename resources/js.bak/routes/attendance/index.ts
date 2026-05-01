import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
export const selfService = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: selfService.url(options),
    method: 'get',
})

selfService.definition = {
    methods: ["get","head"],
    url: '/attendance/self-service',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
selfService.url = (options?: RouteQueryOptions) => {
    return selfService.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
selfService.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
selfService.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: selfService.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:110
* @route '/attendance/check-in'
*/
export const checkIn = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

checkIn.definition = {
    methods: ["post"],
    url: '/attendance/check-in',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:110
* @route '/attendance/check-in'
*/
checkIn.url = (options?: RouteQueryOptions) => {
    return checkIn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:110
* @route '/attendance/check-in'
*/
checkIn.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkIn.url(options),
    method: 'post',
})

const attendance = {
    selfService: Object.assign(selfService, selfService),
    checkIn: Object.assign(checkIn, checkIn),
}

export default attendance