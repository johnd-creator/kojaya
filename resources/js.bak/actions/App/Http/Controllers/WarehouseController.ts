import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:11
* @route '/warehouses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/warehouses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:11
* @route '/warehouses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:11
* @route '/warehouses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:11
* @route '/warehouses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:28
* @route '/warehouses/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/warehouses/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:28
* @route '/warehouses/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:28
* @route '/warehouses/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:28
* @route '/warehouses/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:37
* @route '/warehouses'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/warehouses',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:37
* @route '/warehouses'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:37
* @route '/warehouses'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:52
* @route '/warehouses/{warehouse}'
*/
export const show = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/warehouses/{warehouse}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:52
* @route '/warehouses/{warehouse}'
*/
show.url = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { warehouse: args }
    }

    if (Array.isArray(args)) {
        args = {
            warehouse: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        warehouse: args.warehouse,
    }

    return show.definition.url
            .replace('{warehouse}', parsedArgs.warehouse.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:52
* @route '/warehouses/{warehouse}'
*/
show.get = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:52
* @route '/warehouses/{warehouse}'
*/
show.head = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
export const edit = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/warehouses/{warehouse}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
edit.url = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { warehouse: args }
    }

    if (Array.isArray(args)) {
        args = {
            warehouse: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        warehouse: args.warehouse,
    }

    return edit.definition.url
            .replace('{warehouse}', parsedArgs.warehouse.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
edit.get = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
edit.head = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
export const update = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/warehouses/{warehouse}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
update.url = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { warehouse: args }
    }

    if (Array.isArray(args)) {
        args = {
            warehouse: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        warehouse: args.warehouse,
    }

    return update.definition.url
            .replace('{warehouse}', parsedArgs.warehouse.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
update.put = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
update.patch = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\WarehouseController::destroy
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
export const destroy = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/warehouses/{warehouse}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\WarehouseController::destroy
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
destroy.url = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { warehouse: args }
    }

    if (Array.isArray(args)) {
        args = {
            warehouse: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        warehouse: args.warehouse,
    }

    return destroy.definition.url
            .replace('{warehouse}', parsedArgs.warehouse.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::destroy
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
destroy.delete = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const WarehouseController = { index, create, store, show, edit, update, destroy }

export default WarehouseController