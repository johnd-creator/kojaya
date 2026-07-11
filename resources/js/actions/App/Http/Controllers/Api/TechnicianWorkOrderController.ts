import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:29
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:29
* @route '/api/technician/work-orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:29
* @route '/api/technician/work-orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::index
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:29
* @route '/api/technician/work-orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:57
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:57
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:57
* @route '/api/technician/work-orders/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::show
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:57
* @route '/api/technician/work-orders/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::start
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:70
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:70
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:70
* @route '/api/technician/work-orders/{id}/start'
*/
start.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::complete
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:99
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:99
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:99
* @route '/api/technician/work-orders/{id}/complete'
*/
complete.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::updateChecklist
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:140
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:140
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
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:140
* @route '/api/technician/work-orders/{id}/checklists/{checklistId}'
*/
updateChecklist.post = (args: { id: string | number, checklistId: string | number } | [id: string | number, checklistId: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateChecklist.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storeAttachment
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:167
* @route '/api/technician/work-orders/{id}/attachments'
*/
export const storeAttachment = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAttachment.url(args, options),
    method: 'post',
})

storeAttachment.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/attachments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storeAttachment
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:167
* @route '/api/technician/work-orders/{id}/attachments'
*/
storeAttachment.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return storeAttachment.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storeAttachment
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:167
* @route '/api/technician/work-orders/{id}/attachments'
*/
storeAttachment.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAttachment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storePart
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:199
* @route '/api/technician/work-orders/{id}/parts'
*/
export const storePart = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePart.url(args, options),
    method: 'post',
})

storePart.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/parts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storePart
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:199
* @route '/api/technician/work-orders/{id}/parts'
*/
storePart.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return storePart.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::storePart
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:199
* @route '/api/technician/work-orders/{id}/parts'
*/
storePart.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePart.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::sync
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:215
* @route '/api/technician/work-orders/{id}/sync'
*/
export const sync = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sync.url(args, options),
    method: 'post',
})

sync.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/sync',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::sync
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:215
* @route '/api/technician/work-orders/{id}/sync'
*/
sync.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return sync.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::sync
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:215
* @route '/api/technician/work-orders/{id}/sync'
*/
sync.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sync.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::timeline
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:303
* @route '/api/technician/work-orders/{id}/timeline'
*/
export const timeline = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: timeline.url(args, options),
    method: 'get',
})

timeline.definition = {
    methods: ["get","head"],
    url: '/api/technician/work-orders/{id}/timeline',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::timeline
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:303
* @route '/api/technician/work-orders/{id}/timeline'
*/
timeline.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return timeline.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::timeline
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:303
* @route '/api/technician/work-orders/{id}/timeline'
*/
timeline.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: timeline.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::timeline
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:303
* @route '/api/technician/work-orders/{id}/timeline'
*/
timeline.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: timeline.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::escalate
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:317
* @route '/api/technician/work-orders/{id}/escalate'
*/
export const escalate = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: escalate.url(args, options),
    method: 'post',
})

escalate.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/escalate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::escalate
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:317
* @route '/api/technician/work-orders/{id}/escalate'
*/
escalate.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return escalate.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::escalate
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:317
* @route '/api/technician/work-orders/{id}/escalate'
*/
escalate.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: escalate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::reopen
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:339
* @route '/api/technician/work-orders/{id}/reopen'
*/
export const reopen = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopen.url(args, options),
    method: 'post',
})

reopen.definition = {
    methods: ["post"],
    url: '/api/technician/work-orders/{id}/reopen',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::reopen
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:339
* @route '/api/technician/work-orders/{id}/reopen'
*/
reopen.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reopen.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TechnicianWorkOrderController::reopen
* @see app/Http/Controllers/Api/TechnicianWorkOrderController.php:339
* @route '/api/technician/work-orders/{id}/reopen'
*/
reopen.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopen.url(args, options),
    method: 'post',
})

const TechnicianWorkOrderController = { index, show, start, complete, updateChecklist, storeAttachment, storePart, sync, timeline, escalate, reopen }

export default TechnicianWorkOrderController