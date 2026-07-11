import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
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

const transfers = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
}

export default transfers