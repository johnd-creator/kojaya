import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/counts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/counts/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::store
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:38
* @route '/cooperative/pos/inventory/counts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/inventory/counts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::store
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:38
* @route '/cooperative/pos/inventory/counts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::store
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:38
* @route '/cooperative/pos/inventory/counts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
export const show = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/counts/{count}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
show.url = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { count: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { count: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            count: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        count: typeof args.count === 'object'
        ? args.count.id
        : args.count,
    }

    return show.definition.url
            .replace('{count}', parsedArgs.count.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
show.get = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
show.head = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::submit
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:60
* @route '/cooperative/pos/inventory/counts/{count}/submit'
*/
export const submit = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/cooperative/pos/inventory/counts/{count}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::submit
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:60
* @route '/cooperative/pos/inventory/counts/{count}/submit'
*/
submit.url = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { count: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { count: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            count: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        count: typeof args.count === 'object'
        ? args.count.id
        : args.count,
    }

    return submit.definition.url
            .replace('{count}', parsedArgs.count.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::submit
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:60
* @route '/cooperative/pos/inventory/counts/{count}/submit'
*/
submit.post = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::approve
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:67
* @route '/cooperative/pos/inventory/counts/{count}/approve'
*/
export const approve = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/cooperative/pos/inventory/counts/{count}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::approve
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:67
* @route '/cooperative/pos/inventory/counts/{count}/approve'
*/
approve.url = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { count: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { count: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            count: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        count: typeof args.count === 'object'
        ? args.count.id
        : args.count,
    }

    return approve.definition.url
            .replace('{count}', parsedArgs.count.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::approve
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:67
* @route '/cooperative/pos/inventory/counts/{count}/approve'
*/
approve.post = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

const counts = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    submit: Object.assign(submit, submit),
    approve: Object.assign(approve, approve),
}

export default counts