import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MedicalCheckupController::index
* @see app/Http/Controllers/MedicalCheckupController.php:16
* @route '/api/employees/{employeeId}/mcu'
*/
export const index = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/employees/{employeeId}/mcu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::index
* @see app/Http/Controllers/MedicalCheckupController.php:16
* @route '/api/employees/{employeeId}/mcu'
*/
index.url = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { employeeId: args }
    }

    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
    }

    return index.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::index
* @see app/Http/Controllers/MedicalCheckupController.php:16
* @route '/api/employees/{employeeId}/mcu'
*/
index.get = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::index
* @see app/Http/Controllers/MedicalCheckupController.php:16
* @route '/api/employees/{employeeId}/mcu'
*/
index.head = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::store
* @see app/Http/Controllers/MedicalCheckupController.php:26
* @route '/api/employees/{employeeId}/mcu'
*/
export const store = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/employees/{employeeId}/mcu',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::store
* @see app/Http/Controllers/MedicalCheckupController.php:26
* @route '/api/employees/{employeeId}/mcu'
*/
store.url = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { employeeId: args }
    }

    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
    }

    return store.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::store
* @see app/Http/Controllers/MedicalCheckupController.php:26
* @route '/api/employees/{employeeId}/mcu'
*/
store.post = (args: { employeeId: string | number } | [employeeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::show
* @see app/Http/Controllers/MedicalCheckupController.php:35
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
export const show = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/employees/{employeeId}/mcu/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::show
* @see app/Http/Controllers/MedicalCheckupController.php:35
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
show.url = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
            id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
        id: args.id,
    }

    return show.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::show
* @see app/Http/Controllers/MedicalCheckupController.php:35
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
show.get = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::show
* @see app/Http/Controllers/MedicalCheckupController.php:35
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
show.head = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::update
* @see app/Http/Controllers/MedicalCheckupController.php:43
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
export const update = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/employees/{employeeId}/mcu/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::update
* @see app/Http/Controllers/MedicalCheckupController.php:43
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
update.url = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
            id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
        id: args.id,
    }

    return update.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::update
* @see app/Http/Controllers/MedicalCheckupController.php:43
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
update.put = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::destroy
* @see app/Http/Controllers/MedicalCheckupController.php:53
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
export const destroy = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/employees/{employeeId}/mcu/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::destroy
* @see app/Http/Controllers/MedicalCheckupController.php:53
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
destroy.url = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
            id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
        id: args.id,
    }

    return destroy.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::destroy
* @see app/Http/Controllers/MedicalCheckupController.php:53
* @route '/api/employees/{employeeId}/mcu/{id}'
*/
destroy.delete = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\MedicalCheckupController::uploadDocument
* @see app/Http/Controllers/MedicalCheckupController.php:71
* @route '/api/employees/{employeeId}/mcu/{id}/upload'
*/
export const uploadDocument = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadDocument.url(args, options),
    method: 'post',
})

uploadDocument.definition = {
    methods: ["post"],
    url: '/api/employees/{employeeId}/mcu/{id}/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MedicalCheckupController::uploadDocument
* @see app/Http/Controllers/MedicalCheckupController.php:71
* @route '/api/employees/{employeeId}/mcu/{id}/upload'
*/
uploadDocument.url = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employeeId: args[0],
            id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employeeId: args.employeeId,
        id: args.id,
    }

    return uploadDocument.definition.url
            .replace('{employeeId}', parsedArgs.employeeId.toString())
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalCheckupController::uploadDocument
* @see app/Http/Controllers/MedicalCheckupController.php:71
* @route '/api/employees/{employeeId}/mcu/{id}/upload'
*/
uploadDocument.post = (args: { employeeId: string | number, id: string | number } | [employeeId: string | number, id: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadDocument.url(args, options),
    method: 'post',
})

const MedicalCheckupController = { index, store, show, update, destroy, uploadDocument }

export default MedicalCheckupController