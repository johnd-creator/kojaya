import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:18
* @route '/leaves/self-service'
*/
export const selfService = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: selfService.url(options),
    method: 'get',
})

selfService.definition = {
    methods: ["get","head"],
    url: '/leaves/self-service',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:18
* @route '/leaves/self-service'
*/
selfService.url = (options?: RouteQueryOptions) => {
    return selfService.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:18
* @route '/leaves/self-service'
*/
selfService.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:18
* @route '/leaves/self-service'
*/
selfService.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: selfService.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:42
* @route '/leaves/self-service'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/leaves/self-service',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:42
* @route '/leaves/self-service'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:42
* @route '/leaves/self-service'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:98
* @route '/leaves'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/leaves',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:98
* @route '/leaves'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:98
* @route '/leaves'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:98
* @route '/leaves'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:118
* @route '/leaves/{leave}/status'
*/
export const updateStatus = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

updateStatus.definition = {
    methods: ["put"],
    url: '/leaves/{leave}/status',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:118
* @route '/leaves/{leave}/status'
*/
updateStatus.url = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateStatus.definition.url
            .replace('{leave}', parsedArgs.leave.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:118
* @route '/leaves/{leave}/status'
*/
updateStatus.put = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

const leaves = {
    selfService: Object.assign(selfService, selfService),
    store: Object.assign(store, store),
    index: Object.assign(index, index),
    updateStatus: Object.assign(updateStatus, updateStatus),
}

export default leaves