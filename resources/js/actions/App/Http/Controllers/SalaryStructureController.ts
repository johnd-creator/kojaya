import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/salary-structures',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::index
* @see app/Http/Controllers/SalaryStructureController.php:17
* @route '/salary-structures'
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
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/salary-structures/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::create
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/create'
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
* @see \App\Http\Controllers\SalaryStructureController::store
* @see app/Http/Controllers/SalaryStructureController.php:41
* @route '/salary-structures'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/salary-structures',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::store
* @see app/Http/Controllers/SalaryStructureController.php:41
* @route '/salary-structures'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::store
* @see app/Http/Controllers/SalaryStructureController.php:41
* @route '/salary-structures'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::store
* @see app/Http/Controllers/SalaryStructureController.php:41
* @route '/salary-structures'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::store
* @see app/Http/Controllers/SalaryStructureController.php:41
* @route '/salary-structures'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
export const show = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/salary-structures/{salary_structure}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
show.url = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { salary_structure: args }
    }

    if (Array.isArray(args)) {
        args = {
            salary_structure: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        salary_structure: args.salary_structure,
    }

    return show.definition.url
            .replace('{salary_structure}', parsedArgs.salary_structure.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
show.get = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
show.head = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
const showForm = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
showForm.get = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::show
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}'
*/
showForm.head = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
export const edit = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/salary-structures/{salary_structure}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
edit.url = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { salary_structure: args }
    }

    if (Array.isArray(args)) {
        args = {
            salary_structure: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        salary_structure: args.salary_structure,
    }

    return edit.definition.url
            .replace('{salary_structure}', parsedArgs.salary_structure.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
edit.get = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
edit.head = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
const editForm = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
editForm.get = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::edit
* @see app/Http/Controllers/SalaryStructureController.php:0
* @route '/salary-structures/{salary_structure}/edit'
*/
editForm.head = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
export const update = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/salary-structures/{salary_structure}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
update.url = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { salary_structure: args }
    }

    if (Array.isArray(args)) {
        args = {
            salary_structure: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        salary_structure: args.salary_structure,
    }

    return update.definition.url
            .replace('{salary_structure}', parsedArgs.salary_structure.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
update.put = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
update.patch = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
const updateForm = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
updateForm.put = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::update
* @see app/Http/Controllers/SalaryStructureController.php:68
* @route '/salary-structures/{salary_structure}'
*/
updateForm.patch = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\SalaryStructureController::destroy
* @see app/Http/Controllers/SalaryStructureController.php:97
* @route '/salary-structures/{salary_structure}'
*/
export const destroy = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/salary-structures/{salary_structure}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SalaryStructureController::destroy
* @see app/Http/Controllers/SalaryStructureController.php:97
* @route '/salary-structures/{salary_structure}'
*/
destroy.url = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { salary_structure: args }
    }

    if (Array.isArray(args)) {
        args = {
            salary_structure: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        salary_structure: args.salary_structure,
    }

    return destroy.definition.url
            .replace('{salary_structure}', parsedArgs.salary_structure.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SalaryStructureController::destroy
* @see app/Http/Controllers/SalaryStructureController.php:97
* @route '/salary-structures/{salary_structure}'
*/
destroy.delete = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::destroy
* @see app/Http/Controllers/SalaryStructureController.php:97
* @route '/salary-structures/{salary_structure}'
*/
const destroyForm = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SalaryStructureController::destroy
* @see app/Http/Controllers/SalaryStructureController.php:97
* @route '/salary-structures/{salary_structure}'
*/
destroyForm.delete = (args: { salary_structure: string | number } | [salary_structure: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const SalaryStructureController = { index, create, store, show, edit, update, destroy }

export default SalaryStructureController