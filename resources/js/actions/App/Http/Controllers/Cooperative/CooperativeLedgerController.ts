import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:16
* @route '/cooperative/ledger'
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

const CooperativeLedgerController = { index }

export default CooperativeLedgerController