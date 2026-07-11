import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/work-shifts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/work-shifts/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkShiftController::store
* @see app/Http/Controllers/WorkShiftController.php:23
* @route '/work-shifts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/work-shifts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WorkShiftController::store
* @see app/Http/Controllers/WorkShiftController.php:23
* @route '/work-shifts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::store
* @see app/Http/Controllers/WorkShiftController.php:23
* @route '/work-shifts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
export const show = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/work-shifts/{work_shift}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
show.url = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { work_shift: args }
    }

    if (Array.isArray(args)) {
        args = {
            work_shift: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        work_shift: args.work_shift,
    }

    return show.definition.url
            .replace('{work_shift}', parsedArgs.work_shift.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
show.get = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
show.head = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
export const edit = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/work-shifts/{work_shift}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
edit.url = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { work_shift: args }
    }

    if (Array.isArray(args)) {
        args = {
            work_shift: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        work_shift: args.work_shift,
    }

    return edit.definition.url
            .replace('{work_shift}', parsedArgs.work_shift.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
edit.get = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
edit.head = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
export const update = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/work-shifts/{work_shift}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
update.url = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { work_shift: args }
    }

    if (Array.isArray(args)) {
        args = {
            work_shift: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        work_shift: args.work_shift,
    }

    return update.definition.url
            .replace('{work_shift}', parsedArgs.work_shift.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
update.put = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
update.patch = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\WorkShiftController::destroy
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
export const destroy = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/work-shifts/{work_shift}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\WorkShiftController::destroy
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
destroy.url = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { work_shift: args }
    }

    if (Array.isArray(args)) {
        args = {
            work_shift: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        work_shift: args.work_shift,
    }

    return destroy.definition.url
            .replace('{work_shift}', parsedArgs.work_shift.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkShiftController::destroy
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
destroy.delete = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const workShifts = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default workShifts