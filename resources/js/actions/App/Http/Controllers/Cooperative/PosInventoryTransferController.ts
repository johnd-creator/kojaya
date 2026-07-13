import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/transfers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::index
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:18
* @route '/cooperative/pos/inventory/transfers'
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
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/transfers/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::create
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:31
* @route '/cooperative/pos/inventory/transfers/create'
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
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::store
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:38
* @route '/cooperative/pos/inventory/transfers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/inventory/transfers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::store
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:38
* @route '/cooperative/pos/inventory/transfers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::store
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:38
* @route '/cooperative/pos/inventory/transfers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::store
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:38
* @route '/cooperative/pos/inventory/transfers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryTransferController::store
* @see app/Http/Controllers/Cooperative/PosInventoryTransferController.php:38
* @route '/cooperative/pos/inventory/transfers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const PosInventoryTransferController = { index, create, store }

export default PosInventoryTransferController