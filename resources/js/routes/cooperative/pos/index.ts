import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
import transactions from './transactions'
import voidRequests from './void-requests'
import returns from './returns'
import credit from './credit'
import coffeeOrders from './coffee-orders'
import shu from './shu'
import reports from './reports'
import shifts from './shifts'
import closings from './closings'
import inventory from './inventory'
/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const pos = {
    index: Object.assign(index, index),
    transactions: Object.assign(transactions, transactions),
    voidRequests: Object.assign(voidRequests, voidRequests),
    returns: Object.assign(returns, returns),
    credit: Object.assign(credit, credit),
    coffeeOrders: Object.assign(coffeeOrders, coffeeOrders),
    shu: Object.assign(shu, shu),
    reports: Object.assign(reports, reports),
    shifts: Object.assign(shifts, shifts),
    closings: Object.assign(closings, closings),
    inventory: Object.assign(inventory, inventory),
}

export default pos