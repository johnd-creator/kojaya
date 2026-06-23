import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
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
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
*/
selfService.url = (options?: RouteQueryOptions) => {
    return selfService.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
*/
selfService.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
*/
selfService.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: selfService.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
*/
const selfServiceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
*/
selfServiceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: selfService.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::selfService
* @see app/Http/Controllers/LeaveController.php:21
* @route '/leaves/self-service'
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
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:45
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
* @see app/Http/Controllers/LeaveController.php:45
* @route '/leaves/self-service'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:45
* @route '/leaves/self-service'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:45
* @route '/leaves/self-service'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeaveController::store
* @see app/Http/Controllers/LeaveController.php:45
* @route '/leaves/self-service'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
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
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeaveController::index
* @see app/Http/Controllers/LeaveController.php:95
* @route '/leaves'
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
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:116
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
* @see app/Http/Controllers/LeaveController.php:116
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
* @see app/Http/Controllers/LeaveController.php:116
* @route '/leaves/{leave}/status'
*/
updateStatus.put = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:116
* @route '/leaves/{leave}/status'
*/
const updateStatusForm = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeaveController::updateStatus
* @see app/Http/Controllers/LeaveController.php:116
* @route '/leaves/{leave}/status'
*/
updateStatusForm.put = (args: { leave: number | { id: number } } | [leave: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateStatus.form = updateStatusForm

const LeaveController = { selfService, store, index, updateStatus }

export default LeaveController