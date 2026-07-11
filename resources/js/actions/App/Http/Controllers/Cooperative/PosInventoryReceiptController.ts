import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::index
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:19
* @route '/cooperative/pos/inventory/receipts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/receipts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::index
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:19
* @route '/cooperative/pos/inventory/receipts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::index
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:19
* @route '/cooperative/pos/inventory/receipts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::index
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:19
* @route '/cooperative/pos/inventory/receipts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::create
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:32
* @route '/cooperative/pos/inventory/receipts/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/inventory/receipts/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::create
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:32
* @route '/cooperative/pos/inventory/receipts/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::create
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:32
* @route '/cooperative/pos/inventory/receipts/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::create
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:32
* @route '/cooperative/pos/inventory/receipts/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::store
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:40
* @route '/cooperative/pos/inventory/receipts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/inventory/receipts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::store
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:40
* @route '/cooperative/pos/inventory/receipts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosInventoryReceiptController::store
* @see app/Http/Controllers/Cooperative/PosInventoryReceiptController.php:40
* @route '/cooperative/pos/inventory/receipts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const PosInventoryReceiptController = { index, create, store }

export default PosInventoryReceiptController