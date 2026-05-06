import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::index
* @see app/Http/Controllers/WorkShiftController.php:12
* @route '/work-shifts'
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
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::create
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/create'
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
* @see \App\Http\Controllers\WorkShiftController::store
* @see app/Http/Controllers/WorkShiftController.php:23
* @route '/work-shifts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WorkShiftController::store
* @see app/Http/Controllers/WorkShiftController.php:23
* @route '/work-shifts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

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
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
const showForm = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
showForm.get = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::show
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
showForm.head = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
const editForm = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
editForm.get = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkShiftController::edit
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}/edit'
*/
editForm.head = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

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
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
const updateForm = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
updateForm.put = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WorkShiftController::update
* @see app/Http/Controllers/WorkShiftController.php:32
* @route '/work-shifts/{work_shift}'
*/
updateForm.patch = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

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

/**
* @see \App\Http\Controllers\WorkShiftController::destroy
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
const destroyForm = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WorkShiftController::destroy
* @see app/Http/Controllers/WorkShiftController.php:0
* @route '/work-shifts/{work_shift}'
*/
destroyForm.delete = (args: { work_shift: string | number } | [work_shift: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const WorkShiftController = { index, create, store, show, edit, update, destroy }

export default WorkShiftController