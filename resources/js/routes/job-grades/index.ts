import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/job-grades',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::index
* @see app/Http/Controllers/JobGradeController.php:12
* @route '/job-grades'
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
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/job-grades/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::create
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/create'
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
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:21
* @route '/job-grades'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/job-grades',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:21
* @route '/job-grades'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:21
* @route '/job-grades'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:21
* @route '/job-grades'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:21
* @route '/job-grades'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
export const show = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/job-grades/{job_grade}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
show.url = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job_grade: args }
    }

    if (Array.isArray(args)) {
        args = {
            job_grade: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job_grade: args.job_grade,
    }

    return show.definition.url
            .replace('{job_grade}', parsedArgs.job_grade.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
show.get = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
show.head = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
const showForm = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
showForm.get = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::show
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
showForm.head = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
export const edit = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/job-grades/{job_grade}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
edit.url = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job_grade: args }
    }

    if (Array.isArray(args)) {
        args = {
            job_grade: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job_grade: args.job_grade,
    }

    return edit.definition.url
            .replace('{job_grade}', parsedArgs.job_grade.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
edit.get = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
edit.head = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
const editForm = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
editForm.get = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\JobGradeController::edit
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}/edit'
*/
editForm.head = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
export const update = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/job-grades/{job_grade}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
update.url = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job_grade: args }
    }

    if (Array.isArray(args)) {
        args = {
            job_grade: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job_grade: args.job_grade,
    }

    return update.definition.url
            .replace('{job_grade}', parsedArgs.job_grade.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
update.put = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
update.patch = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
const updateForm = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
updateForm.put = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:28
* @route '/job-grades/{job_grade}'
*/
updateForm.patch = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\JobGradeController::destroy
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
export const destroy = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/job-grades/{job_grade}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\JobGradeController::destroy
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
destroy.url = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { job_grade: args }
    }

    if (Array.isArray(args)) {
        args = {
            job_grade: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        job_grade: args.job_grade,
    }

    return destroy.definition.url
            .replace('{job_grade}', parsedArgs.job_grade.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::destroy
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
destroy.delete = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\JobGradeController::destroy
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
const destroyForm = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\JobGradeController::destroy
* @see app/Http/Controllers/JobGradeController.php:0
* @route '/job-grades/{job_grade}'
*/
destroyForm.delete = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const jobGrades = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default jobGrades