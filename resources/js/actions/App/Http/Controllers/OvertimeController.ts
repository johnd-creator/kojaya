import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/overtime',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::index
* @see app/Http/Controllers/OvertimeController.php:18
* @route '/overtime'
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
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/overtime/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OvertimeController::create
* @see app/Http/Controllers/OvertimeController.php:66
* @route '/overtime/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\OvertimeController::store
* @see app/Http/Controllers/OvertimeController.php:77
* @route '/overtime'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/overtime',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OvertimeController::store
* @see app/Http/Controllers/OvertimeController.php:77
* @route '/overtime'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::store
* @see app/Http/Controllers/OvertimeController.php:77
* @route '/overtime'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::store
* @see app/Http/Controllers/OvertimeController.php:77
* @route '/overtime'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::store
* @see app/Http/Controllers/OvertimeController.php:77
* @route '/overtime'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\OvertimeController::destroy
* @see app/Http/Controllers/OvertimeController.php:152
* @route '/overtime/{overtime}'
*/
export const destroy = (args: { overtime: string | number } | [overtime: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/overtime/{overtime}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\OvertimeController::destroy
* @see app/Http/Controllers/OvertimeController.php:152
* @route '/overtime/{overtime}'
*/
destroy.url = (args: { overtime: string | number } | [overtime: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { overtime: args }
    }

    if (Array.isArray(args)) {
        args = {
            overtime: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        overtime: args.overtime,
    }

    return destroy.definition.url
            .replace('{overtime}', parsedArgs.overtime.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::destroy
* @see app/Http/Controllers/OvertimeController.php:152
* @route '/overtime/{overtime}'
*/
destroy.delete = (args: { overtime: string | number } | [overtime: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\OvertimeController::destroy
* @see app/Http/Controllers/OvertimeController.php:152
* @route '/overtime/{overtime}'
*/
const destroyForm = (args: { overtime: string | number } | [overtime: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::destroy
* @see app/Http/Controllers/OvertimeController.php:152
* @route '/overtime/{overtime}'
*/
destroyForm.delete = (args: { overtime: string | number } | [overtime: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\OvertimeController::approve
* @see app/Http/Controllers/OvertimeController.php:119
* @route '/overtime/{overtimeRequest}/approve'
*/
export const approve = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/overtime/{overtimeRequest}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OvertimeController::approve
* @see app/Http/Controllers/OvertimeController.php:119
* @route '/overtime/{overtimeRequest}/approve'
*/
approve.url = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { overtimeRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { overtimeRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            overtimeRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        overtimeRequest: typeof args.overtimeRequest === 'object'
        ? args.overtimeRequest.id
        : args.overtimeRequest,
    }

    return approve.definition.url
            .replace('{overtimeRequest}', parsedArgs.overtimeRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::approve
* @see app/Http/Controllers/OvertimeController.php:119
* @route '/overtime/{overtimeRequest}/approve'
*/
approve.post = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::approve
* @see app/Http/Controllers/OvertimeController.php:119
* @route '/overtime/{overtimeRequest}/approve'
*/
const approveForm = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::approve
* @see app/Http/Controllers/OvertimeController.php:119
* @route '/overtime/{overtimeRequest}/approve'
*/
approveForm.post = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\OvertimeController::reject
* @see app/Http/Controllers/OvertimeController.php:136
* @route '/overtime/{overtimeRequest}/reject'
*/
export const reject = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/overtime/{overtimeRequest}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OvertimeController::reject
* @see app/Http/Controllers/OvertimeController.php:136
* @route '/overtime/{overtimeRequest}/reject'
*/
reject.url = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { overtimeRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { overtimeRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            overtimeRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        overtimeRequest: typeof args.overtimeRequest === 'object'
        ? args.overtimeRequest.id
        : args.overtimeRequest,
    }

    return reject.definition.url
            .replace('{overtimeRequest}', parsedArgs.overtimeRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OvertimeController::reject
* @see app/Http/Controllers/OvertimeController.php:136
* @route '/overtime/{overtimeRequest}/reject'
*/
reject.post = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::reject
* @see app/Http/Controllers/OvertimeController.php:136
* @route '/overtime/{overtimeRequest}/reject'
*/
const rejectForm = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OvertimeController::reject
* @see app/Http/Controllers/OvertimeController.php:136
* @route '/overtime/{overtimeRequest}/reject'
*/
rejectForm.post = (args: { overtimeRequest: string | number | { id: string | number } } | [overtimeRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

const OvertimeController = { index, create, store, destroy, approve, reject }

export default OvertimeController