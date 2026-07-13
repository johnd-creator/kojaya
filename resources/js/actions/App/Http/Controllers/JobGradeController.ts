import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:23
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
* @see app/Http/Controllers/JobGradeController.php:23
* @route '/job-grades'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\JobGradeController::store
* @see app/Http/Controllers/JobGradeController.php:23
* @route '/job-grades'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

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
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:32
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
* @see app/Http/Controllers/JobGradeController.php:32
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
* @see app/Http/Controllers/JobGradeController.php:32
* @route '/job-grades/{job_grade}'
*/
update.put = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\JobGradeController::update
* @see app/Http/Controllers/JobGradeController.php:32
* @route '/job-grades/{job_grade}'
*/
update.patch = (args: { job_grade: string | number } | [job_grade: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

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

const JobGradeController = { index, create, store, show, edit, update, destroy }

export default JobGradeController