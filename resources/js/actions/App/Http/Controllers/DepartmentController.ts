import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DepartmentController::index
* @see app/Http/Controllers/DepartmentController.php:14
* @route '/departments'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/departments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DepartmentController::index
* @see app/Http/Controllers/DepartmentController.php:14
* @route '/departments'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::index
* @see app/Http/Controllers/DepartmentController.php:14
* @route '/departments'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DepartmentController::index
* @see app/Http/Controllers/DepartmentController.php:14
* @route '/departments'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DepartmentController::create
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/departments/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DepartmentController::create
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::create
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DepartmentController::create
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DepartmentController::store
* @see app/Http/Controllers/DepartmentController.php:39
* @route '/departments'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/departments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DepartmentController::store
* @see app/Http/Controllers/DepartmentController.php:39
* @route '/departments'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::store
* @see app/Http/Controllers/DepartmentController.php:39
* @route '/departments'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DepartmentController::show
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}'
*/
export const show = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/departments/{department}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DepartmentController::show
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}'
*/
show.url = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { department: args }
    }

    if (Array.isArray(args)) {
        args = {
            department: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        department: args.department,
    }

    return show.definition.url
            .replace('{department}', parsedArgs.department.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::show
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}'
*/
show.get = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DepartmentController::show
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}'
*/
show.head = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DepartmentController::edit
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}/edit'
*/
export const edit = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/departments/{department}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DepartmentController::edit
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}/edit'
*/
edit.url = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { department: args }
    }

    if (Array.isArray(args)) {
        args = {
            department: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        department: args.department,
    }

    return edit.definition.url
            .replace('{department}', parsedArgs.department.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::edit
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}/edit'
*/
edit.get = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DepartmentController::edit
* @see app/Http/Controllers/DepartmentController.php:0
* @route '/departments/{department}/edit'
*/
edit.head = (args: { department: string | number } | [department: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DepartmentController::update
* @see app/Http/Controllers/DepartmentController.php:48
* @route '/departments/{department}'
*/
export const update = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/departments/{department}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\DepartmentController::update
* @see app/Http/Controllers/DepartmentController.php:48
* @route '/departments/{department}'
*/
update.url = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { department: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { department: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            department: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        department: typeof args.department === 'object'
        ? args.department.id
        : args.department,
    }

    return update.definition.url
            .replace('{department}', parsedArgs.department.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::update
* @see app/Http/Controllers/DepartmentController.php:48
* @route '/departments/{department}'
*/
update.put = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DepartmentController::update
* @see app/Http/Controllers/DepartmentController.php:48
* @route '/departments/{department}'
*/
update.patch = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DepartmentController::destroy
* @see app/Http/Controllers/DepartmentController.php:57
* @route '/departments/{department}'
*/
export const destroy = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/departments/{department}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DepartmentController::destroy
* @see app/Http/Controllers/DepartmentController.php:57
* @route '/departments/{department}'
*/
destroy.url = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { department: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { department: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            department: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        department: typeof args.department === 'object'
        ? args.department.id
        : args.department,
    }

    return destroy.definition.url
            .replace('{department}', parsedArgs.department.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DepartmentController::destroy
* @see app/Http/Controllers/DepartmentController.php:57
* @route '/departments/{department}'
*/
destroy.delete = (args: { department: number | { id: number } } | [department: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const DepartmentController = { index, create, store, show, edit, update, destroy }

export default DepartmentController