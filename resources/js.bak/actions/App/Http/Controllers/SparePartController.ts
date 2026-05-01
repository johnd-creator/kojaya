import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:12
* @route '/spare-parts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/spare-parts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:12
* @route '/spare-parts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:12
* @route '/spare-parts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:12
* @route '/spare-parts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:52
* @route '/spare-parts/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/spare-parts/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:52
* @route '/spare-parts/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:52
* @route '/spare-parts/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:52
* @route '/spare-parts/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:61
* @route '/spare-parts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/spare-parts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:61
* @route '/spare-parts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:61
* @route '/spare-parts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:80
* @route '/spare-parts/{spare_part}'
*/
export const show = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/spare-parts/{spare_part}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:80
* @route '/spare-parts/{spare_part}'
*/
show.url = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spare_part: args }
    }

    if (Array.isArray(args)) {
        args = {
            spare_part: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        spare_part: args.spare_part,
    }

    return show.definition.url
            .replace('{spare_part}', parsedArgs.spare_part.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:80
* @route '/spare-parts/{spare_part}'
*/
show.get = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:80
* @route '/spare-parts/{spare_part}'
*/
show.head = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
export const edit = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/spare-parts/{spare_part}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
edit.url = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spare_part: args }
    }

    if (Array.isArray(args)) {
        args = {
            spare_part: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        spare_part: args.spare_part,
    }

    return edit.definition.url
            .replace('{spare_part}', parsedArgs.spare_part.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
edit.get = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
edit.head = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
export const update = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/spare-parts/{spare_part}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
update.url = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spare_part: args }
    }

    if (Array.isArray(args)) {
        args = {
            spare_part: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        spare_part: args.spare_part,
    }

    return update.definition.url
            .replace('{spare_part}', parsedArgs.spare_part.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
update.put = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
update.patch = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SparePartController::destroy
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
export const destroy = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/spare-parts/{spare_part}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SparePartController::destroy
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
destroy.url = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { spare_part: args }
    }

    if (Array.isArray(args)) {
        args = {
            spare_part: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        spare_part: args.spare_part,
    }

    return destroy.definition.url
            .replace('{spare_part}', parsedArgs.spare_part.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::destroy
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
destroy.delete = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const SparePartController = { index, create, store, show, edit, update, destroy }

export default SparePartController