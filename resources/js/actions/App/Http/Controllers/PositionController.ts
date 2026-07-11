import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:15
* @route '/positions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/positions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:15
* @route '/positions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:15
* @route '/positions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::index
* @see app/Http/Controllers/PositionController.php:15
* @route '/positions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::create
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/positions/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::create
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::create
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::create
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:38
* @route '/positions'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/positions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:38
* @route '/positions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::store
* @see app/Http/Controllers/PositionController.php:38
* @route '/positions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}'
*/
export const show = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/positions/{position}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}'
*/
show.url = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: args.position,
    }

    return show.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}'
*/
show.get = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::show
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}'
*/
show.head = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::edit
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}/edit'
*/
export const edit = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/positions/{position}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PositionController::edit
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}/edit'
*/
edit.url = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: args.position,
    }

    return edit.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::edit
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}/edit'
*/
edit.get = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PositionController::edit
* @see app/Http/Controllers/PositionController.php:0
* @route '/positions/{position}/edit'
*/
edit.head = (args: { position: string | number } | [position: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:47
* @route '/positions/{position}'
*/
export const update = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/positions/{position}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:47
* @route '/positions/{position}'
*/
update.url = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { position: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return update.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:47
* @route '/positions/{position}'
*/
update.put = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\PositionController::update
* @see app/Http/Controllers/PositionController.php:47
* @route '/positions/{position}'
*/
update.patch = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
export const destroy = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/positions/{position}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
destroy.url = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { position: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { position: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            position: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        position: typeof args.position === 'object'
        ? args.position.id
        : args.position,
    }

    return destroy.definition.url
            .replace('{position}', parsedArgs.position.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PositionController::destroy
* @see app/Http/Controllers/PositionController.php:56
* @route '/positions/{position}'
*/
destroy.delete = (args: { position: number | { id: number } } | [position: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const PositionController = { index, create, store, show, edit, update, destroy }

export default PositionController