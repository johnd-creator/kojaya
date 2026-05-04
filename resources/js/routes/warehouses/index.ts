import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
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
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::index
* @see app/Http/Controllers/WarehouseController.php:12
* @route '/warehouses'
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
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
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
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::create
* @see app/Http/Controllers/WarehouseController.php:29
* @route '/warehouses/create'
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
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:38
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
* @see app/Http/Controllers/WarehouseController.php:38
* @route '/warehouses'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:38
* @route '/warehouses'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:38
* @route '/warehouses'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::store
* @see app/Http/Controllers/WarehouseController.php:38
* @route '/warehouses'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:45
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
* @see app/Http/Controllers/WarehouseController.php:45
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
* @see app/Http/Controllers/WarehouseController.php:45
* @route '/warehouses/{warehouse}'
*/
show.get = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:45
* @route '/warehouses/{warehouse}'
*/
show.head = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:45
* @route '/warehouses/{warehouse}'
*/
const showForm = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:45
* @route '/warehouses/{warehouse}'
*/
showForm.get = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::show
* @see app/Http/Controllers/WarehouseController.php:45
* @route '/warehouses/{warehouse}'
*/
showForm.head = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
const editForm = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
editForm.get = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WarehouseController::edit
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}/edit'
*/
editForm.head = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
const updateForm = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
updateForm.put = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::update
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
updateForm.patch = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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

/**
* @see \App\Http\Controllers\WarehouseController::destroy
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
const destroyForm = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WarehouseController::destroy
* @see app/Http/Controllers/WarehouseController.php:0
* @route '/warehouses/{warehouse}'
*/
destroyForm.delete = (args: { warehouse: string | number } | [warehouse: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const warehouses = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default warehouses