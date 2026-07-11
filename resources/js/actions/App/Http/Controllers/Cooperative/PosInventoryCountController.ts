import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
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
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::index
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:18
* @route '/cooperative/pos/inventory/counts'
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
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::create
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:31
* @route '/cooperative/pos/inventory/counts/create'
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
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::store
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:38
* @route '/cooperative/pos/inventory/counts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::store
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:38
* @route '/cooperative/pos/inventory/counts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

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
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
const showForm = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
showForm.get = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::show
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:51
* @route '/cooperative/pos/inventory/counts/{count}'
*/
showForm.head = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::submit
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:60
* @route '/cooperative/pos/inventory/counts/{count}/submit'
*/
const submitForm = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::submit
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:60
* @route '/cooperative/pos/inventory/counts/{count}/submit'
*/
submitForm.post = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(args, options),
    method: 'post',
})

submit.form = submitForm

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

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::approve
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:67
* @route '/cooperative/pos/inventory/counts/{count}/approve'
*/
const approveForm = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryCountController::approve
* @see app/Http/Controllers/Cooperative/PosInventoryCountController.php:67
* @route '/cooperative/pos/inventory/counts/{count}/approve'
*/
approveForm.post = (args: { count: number | { id: number } } | [count: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

const PosInventoryCountController = { index, create, store, show, submit, approve }

export default PosInventoryCountController