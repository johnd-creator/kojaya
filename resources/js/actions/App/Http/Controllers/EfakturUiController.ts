import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/efaktur',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:35
* @route '/finance/efaktur/submit'
*/
export const submitPage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: submitPage.url(options),
    method: 'get',
})

submitPage.definition = {
    methods: ["get","head"],
    url: '/finance/efaktur/submit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:35
* @route '/finance/efaktur/submit'
*/
submitPage.url = (options?: RouteQueryOptions) => {
    return submitPage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:35
* @route '/finance/efaktur/submit'
*/
submitPage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: submitPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:35
* @route '/finance/efaktur/submit'
*/
submitPage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: submitPage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:48
* @route '/finance/efaktur/status'
*/
export const status = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/finance/efaktur/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:48
* @route '/finance/efaktur/status'
*/
status.url = (options?: RouteQueryOptions) => {
    return status.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:48
* @route '/finance/efaktur/status'
*/
status.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:48
* @route '/finance/efaktur/status'
*/
status.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(options),
    method: 'head',
})

const EfakturUiController = { index, submitPage, status }

export default EfakturUiController