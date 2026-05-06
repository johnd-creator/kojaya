import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
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
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::index
* @see app/Http/Controllers/SparePartController.php:14
* @route '/spare-parts'
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
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
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
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::create
* @see app/Http/Controllers/SparePartController.php:56
* @route '/spare-parts/create'
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
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:67
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
* @see app/Http/Controllers/SparePartController.php:67
* @route '/spare-parts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:67
* @route '/spare-parts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:67
* @route '/spare-parts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::store
* @see app/Http/Controllers/SparePartController.php:67
* @route '/spare-parts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:76
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
* @see app/Http/Controllers/SparePartController.php:76
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
* @see app/Http/Controllers/SparePartController.php:76
* @route '/spare-parts/{spare_part}'
*/
show.get = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:76
* @route '/spare-parts/{spare_part}'
*/
show.head = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:76
* @route '/spare-parts/{spare_part}'
*/
const showForm = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:76
* @route '/spare-parts/{spare_part}'
*/
showForm.get = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::show
* @see app/Http/Controllers/SparePartController.php:76
* @route '/spare-parts/{spare_part}'
*/
showForm.head = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
const editForm = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
editForm.get = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SparePartController::edit
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}/edit'
*/
editForm.head = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
const updateForm = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
updateForm.put = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::update
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
updateForm.patch = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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

/**
* @see \App\Http\Controllers\SparePartController::destroy
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
const destroyForm = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::destroy
* @see app/Http/Controllers/SparePartController.php:0
* @route '/spare-parts/{spare_part}'
*/
destroyForm.delete = (args: { spare_part: string | number } | [spare_part: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\SparePartController::updateStock
* @see app/Http/Controllers/SparePartController.php:87
* @route '/spare-parts/{id}/stock'
*/
export const updateStock = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateStock.url(args, options),
    method: 'post',
})

updateStock.definition = {
    methods: ["post"],
    url: '/spare-parts/{id}/stock',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SparePartController::updateStock
* @see app/Http/Controllers/SparePartController.php:87
* @route '/spare-parts/{id}/stock'
*/
updateStock.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return updateStock.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SparePartController::updateStock
* @see app/Http/Controllers/SparePartController.php:87
* @route '/spare-parts/{id}/stock'
*/
updateStock.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateStock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::updateStock
* @see app/Http/Controllers/SparePartController.php:87
* @route '/spare-parts/{id}/stock'
*/
const updateStockForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SparePartController::updateStock
* @see app/Http/Controllers/SparePartController.php:87
* @route '/spare-parts/{id}/stock'
*/
updateStockForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStock.url(args, options),
    method: 'post',
})

updateStock.form = updateStockForm

const spareParts = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    updateStock: Object.assign(updateStock, updateStock),
}

export default spareParts