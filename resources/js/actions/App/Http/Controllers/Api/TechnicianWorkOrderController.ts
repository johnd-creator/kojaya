import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/technician/work-orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:14
* @route '/api/technician/work-orders'
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
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/technician/work-orders/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:31
* @route '/api/technician/work-orders/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:44
* @route '/api/technician/work-orders/{id}/start'
*/
export const start = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:44
* @route '/api/technician/work-orders/{id}/start'
*/
start.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return start.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:44
* @route '/api/technician/work-orders/{id}/start'
*/
start.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:44
* @route '/api/technician/work-orders/{id}/start'
*/
const startForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:44
* @route '/api/technician/work-orders/{id}/start'
*/
startForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:61
* @route '/api/technician/work-orders/{id}/complete'
*/
export const complete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

complete.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/complete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:61
* @route '/api/technician/work-orders/{id}/complete'
*/
complete.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return complete.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:61
* @route '/api/technician/work-orders/{id}/complete'
*/
complete.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:61
* @route '/api/technician/work-orders/{id}/complete'
*/
const completeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:61
* @route '/api/technician/work-orders/{id}/complete'
*/
completeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

complete.form = completeForm

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:89
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
export const updateChecklist = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateChecklist.url(args, options),
    method: 'post',
})

updateChecklist.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/checklists/{checklistId}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:89
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
updateChecklist.url = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            id: args[0],
            checklistId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
        checklistId: args.checklistId,
    }

    return updateChecklist.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace('{checklistId}', parsedArgs.checklistId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:89
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
updateChecklist.post = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateChecklist.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:89
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
const updateChecklistForm = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateChecklist.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:89
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
updateChecklistForm.post = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateChecklist.url(args, options),
    method: 'post',
})

updateChecklist.form = updateChecklistForm

const TechnicianWorkOrderController = { index, show, start, complete, updateChecklist }

export default TechnicianWorkOrderController