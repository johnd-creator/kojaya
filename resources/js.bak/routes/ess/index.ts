import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import attendance from './attendance'
/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
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
* @see app/Http/Controllers/AttendanceController.php:353
* @route '/ess/geofence'
*/
geofence.url = (options?: RouteQueryOptions) => {
    return geofence.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
* @route '/ess/geofence'
*/
geofence.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
* @route '/ess/geofence'
*/
geofence.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: geofence.url(options),
    method: 'head',
})

const ess = {
    attendance: Object.assign(attendance, attendance),
    geofence: Object.assign(geofence, geofence),
}

export default ess