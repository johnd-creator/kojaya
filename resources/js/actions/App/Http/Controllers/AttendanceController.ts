import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
const selfServiceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
selfServiceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::selfService
* @see app/Http/Controllers/AttendanceController.php:84
* @route '/attendance/self-service'
*/
selfServiceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: selfService.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

selfService.form = selfServiceForm

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

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:110
* @route '/attendance/check-in'
*/
const checkInForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkIn
* @see app/Http/Controllers/AttendanceController.php:110
* @route '/attendance/check-in'
*/
checkInForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkIn.url(options),
    method: 'post',
})

checkIn.form = checkInForm

/**
* @see \App\Http\Controllers\AttendanceController::checkInApi
* @see app/Http/Controllers/AttendanceController.php:243
* @route '/ess/attendance/check-in'
*/
export const checkInApi = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkInApi.url(options),
    method: 'post',
})

checkInApi.definition = {
    methods: ["post"],
    url: '/ess/attendance/check-in',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkInApi
* @see app/Http/Controllers/AttendanceController.php:243
* @route '/ess/attendance/check-in'
*/
checkInApi.url = (options?: RouteQueryOptions) => {
    return checkInApi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkInApi
* @see app/Http/Controllers/AttendanceController.php:243
* @route '/ess/attendance/check-in'
*/
checkInApi.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkInApi.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkInApi
* @see app/Http/Controllers/AttendanceController.php:243
* @route '/ess/attendance/check-in'
*/
const checkInApiForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkInApi.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkInApi
* @see app/Http/Controllers/AttendanceController.php:243
* @route '/ess/attendance/check-in'
*/
checkInApiForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkInApi.url(options),
    method: 'post',
})

checkInApi.form = checkInApiForm

/**
* @see \App\Http\Controllers\AttendanceController::checkOutApi
* @see app/Http/Controllers/AttendanceController.php:300
* @route '/ess/attendance/check-out'
*/
export const checkOutApi = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOutApi.url(options),
    method: 'post',
})

checkOutApi.definition = {
    methods: ["post"],
    url: '/ess/attendance/check-out',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkOutApi
* @see app/Http/Controllers/AttendanceController.php:300
* @route '/ess/attendance/check-out'
*/
checkOutApi.url = (options?: RouteQueryOptions) => {
    return checkOutApi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkOutApi
* @see app/Http/Controllers/AttendanceController.php:300
* @route '/ess/attendance/check-out'
*/
checkOutApi.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOutApi.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOutApi
* @see app/Http/Controllers/AttendanceController.php:300
* @route '/ess/attendance/check-out'
*/
const checkOutApiForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOutApi.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOutApi
* @see app/Http/Controllers/AttendanceController.php:300
* @route '/ess/attendance/check-out'
*/
checkOutApiForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOutApi.url(options),
    method: 'post',
})

checkOutApi.form = checkOutApiForm

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

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
* @route '/ess/geofence'
*/
const geofenceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
* @route '/ess/geofence'
*/
geofenceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: geofence.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::geofence
* @see app/Http/Controllers/AttendanceController.php:353
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
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:197
* @route '/attendance/check-out'
*/
export const checkOut = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

checkOut.definition = {
    methods: ["post"],
    url: '/attendance/check-out',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:197
* @route '/attendance/check-out'
*/
checkOut.url = (options?: RouteQueryOptions) => {
    return checkOut.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:197
* @route '/attendance/check-out'
*/
checkOut.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:197
* @route '/attendance/check-out'
*/
const checkOutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::checkOut
* @see app/Http/Controllers/AttendanceController.php:197
* @route '/attendance/check-out'
*/
checkOutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkOut.url(options),
    method: 'post',
})

checkOut.form = checkOutForm

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/attendances',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AttendanceController::index
* @see app/Http/Controllers/AttendanceController.php:14
* @route '/attendances'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\AttendanceController::store
* @see app/Http/Controllers/AttendanceController.php:64
* @route '/attendances'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/attendances',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AttendanceController::store
* @see app/Http/Controllers/AttendanceController.php:64
* @route '/attendances'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AttendanceController::store
* @see app/Http/Controllers/AttendanceController.php:64
* @route '/attendances'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::store
* @see app/Http/Controllers/AttendanceController.php:64
* @route '/attendances'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AttendanceController::store
* @see app/Http/Controllers/AttendanceController.php:64
* @route '/attendances'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const AttendanceController = { selfService, checkIn, checkInApi, checkOutApi, geofence, checkOut, index, store }

export default AttendanceController