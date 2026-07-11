import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/shift-rosters',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::index
* @see app/Http/Controllers/ShiftRosterController.php:17
* @route '/shift-rosters'
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
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/shift-rosters/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::create
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/create'
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
* @see \App\Http\Controllers\ShiftRosterController::store
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/shift-rosters',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::store
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::store
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::store
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::store
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
export const show = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/shift-rosters/{shift_roster}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
show.url = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { shift_roster: args }
    }

    if (Array.isArray(args)) {
        args = {
            shift_roster: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        shift_roster: args.shift_roster,
    }

    return show.definition.url
            .replace('{shift_roster}', parsedArgs.shift_roster.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
show.get = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
show.head = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
const showForm = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
showForm.get = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::show
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
showForm.head = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
export const edit = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/shift-rosters/{shift_roster}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
edit.url = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { shift_roster: args }
    }

    if (Array.isArray(args)) {
        args = {
            shift_roster: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        shift_roster: args.shift_roster,
    }

    return edit.definition.url
            .replace('{shift_roster}', parsedArgs.shift_roster.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
edit.get = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
edit.head = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
const editForm = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
editForm.get = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::edit
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}/edit'
*/
editForm.head = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
export const update = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/shift-rosters/{shift_roster}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
update.url = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { shift_roster: args }
    }

    if (Array.isArray(args)) {
        args = {
            shift_roster: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        shift_roster: args.shift_roster,
    }

    return update.definition.url
            .replace('{shift_roster}', parsedArgs.shift_roster.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
update.put = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
update.patch = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
const updateForm = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
updateForm.put = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::update
* @see app/Http/Controllers/ShiftRosterController.php:48
* @route '/shift-rosters/{shift_roster}'
*/
updateForm.patch = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\ShiftRosterController::destroy
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
export const destroy = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/shift-rosters/{shift_roster}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::destroy
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
destroy.url = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { shift_roster: args }
    }

    if (Array.isArray(args)) {
        args = {
            shift_roster: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        shift_roster: args.shift_roster,
    }

    return destroy.definition.url
            .replace('{shift_roster}', parsedArgs.shift_roster.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::destroy
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
destroy.delete = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::destroy
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
const destroyForm = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::destroy
* @see app/Http/Controllers/ShiftRosterController.php:0
* @route '/shift-rosters/{shift_roster}'
*/
destroyForm.delete = (args: { shift_roster: string | number } | [shift_roster: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\ShiftRosterController::generate
* @see app/Http/Controllers/ShiftRosterController.php:63
* @route '/shift-rosters/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/shift-rosters/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ShiftRosterController::generate
* @see app/Http/Controllers/ShiftRosterController.php:63
* @route '/shift-rosters/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ShiftRosterController::generate
* @see app/Http/Controllers/ShiftRosterController.php:63
* @route '/shift-rosters/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::generate
* @see app/Http/Controllers/ShiftRosterController.php:63
* @route '/shift-rosters/generate'
*/
const generateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ShiftRosterController::generate
* @see app/Http/Controllers/ShiftRosterController.php:63
* @route '/shift-rosters/generate'
*/
generateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

generate.form = generateForm

const shiftRosters = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    generate: Object.assign(generate, generate),
}

export default shiftRosters