import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::index
* @see app/Http/Controllers/ProjectController.php:18
* @route '/projects'
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
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/projects/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::create
* @see app/Http/Controllers/ProjectController.php:61
* @route '/projects/create'
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
* @see \App\Http\Controllers\ProjectController::store
* @see app/Http/Controllers/ProjectController.php:69
* @route '/projects'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/projects',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectController::store
* @see app/Http/Controllers/ProjectController.php:69
* @route '/projects'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::store
* @see app/Http/Controllers/ProjectController.php:69
* @route '/projects'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::store
* @see app/Http/Controllers/ProjectController.php:69
* @route '/projects'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::store
* @see app/Http/Controllers/ProjectController.php:69
* @route '/projects'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
export const show = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/projects/{project}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
show.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return show.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
show.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
show.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
const showForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
showForm.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::show
* @see app/Http/Controllers/ProjectController.php:81
* @route '/projects/{project}'
*/
showForm.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
export const edit = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
edit.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return edit.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
edit.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
edit.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
const editForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
editForm.get = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectController::edit
* @see app/Http/Controllers/ProjectController.php:109
* @route '/projects/{project}/edit'
*/
editForm.head = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\ProjectController::update
* @see app/Http/Controllers/ProjectController.php:120
* @route '/projects/{project}'
*/
export const update = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/projects/{project}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ProjectController::update
* @see app/Http/Controllers/ProjectController.php:120
* @route '/projects/{project}'
*/
update.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return update.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::update
* @see app/Http/Controllers/ProjectController.php:120
* @route '/projects/{project}'
*/
update.put = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectController::update
* @see app/Http/Controllers/ProjectController.php:120
* @route '/projects/{project}'
*/
const updateForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::update
* @see app/Http/Controllers/ProjectController.php:120
* @route '/projects/{project}'
*/
updateForm.put = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\ProjectController::destroy
* @see app/Http/Controllers/ProjectController.php:129
* @route '/projects/{project}'
*/
export const destroy = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/projects/{project}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectController::destroy
* @see app/Http/Controllers/ProjectController.php:129
* @route '/projects/{project}'
*/
destroy.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return destroy.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::destroy
* @see app/Http/Controllers/ProjectController.php:129
* @route '/projects/{project}'
*/
destroy.delete = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectController::destroy
* @see app/Http/Controllers/ProjectController.php:129
* @route '/projects/{project}'
*/
const destroyForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::destroy
* @see app/Http/Controllers/ProjectController.php:129
* @route '/projects/{project}'
*/
destroyForm.delete = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\ProjectController::updateProgress
* @see app/Http/Controllers/ProjectController.php:136
* @route '/projects/{project}/progress'
*/
export const updateProgress = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

updateProgress.definition = {
    methods: ["post"],
    url: '/projects/{project}/progress',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectController::updateProgress
* @see app/Http/Controllers/ProjectController.php:136
* @route '/projects/{project}/progress'
*/
updateProgress.url = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return updateProgress.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectController::updateProgress
* @see app/Http/Controllers/ProjectController.php:136
* @route '/projects/{project}/progress'
*/
updateProgress.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::updateProgress
* @see app/Http/Controllers/ProjectController.php:136
* @route '/projects/{project}/progress'
*/
const updateProgressForm = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProgress.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectController::updateProgress
* @see app/Http/Controllers/ProjectController.php:136
* @route '/projects/{project}/progress'
*/
updateProgressForm.post = (args: { project: string | { id: string } } | [project: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProgress.url(args, options),
    method: 'post',
})

updateProgress.form = updateProgressForm

const ProjectController = { index, create, store, show, edit, update, destroy, updateProgress }

export default ProjectController