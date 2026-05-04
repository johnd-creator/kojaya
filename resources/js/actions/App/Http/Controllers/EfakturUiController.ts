import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::index
* @see app/Http/Controllers/EfakturUiController.php:14
* @route '/finance/efaktur'
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
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
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
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
submitPage.url = (options?: RouteQueryOptions) => {
    return submitPage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
submitPage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: submitPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
submitPage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: submitPage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
const submitPageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: submitPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
submitPageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: submitPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::submitPage
* @see app/Http/Controllers/EfakturUiController.php:33
* @route '/finance/efaktur/submit'
*/
submitPageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: submitPage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

submitPage.form = submitPageForm

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
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
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
status.url = (options?: RouteQueryOptions) => {
    return status.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
status.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
status.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
const statusForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
statusForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EfakturUiController::status
* @see app/Http/Controllers/EfakturUiController.php:44
* @route '/finance/efaktur/status'
*/
statusForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

status.form = statusForm

const EfakturUiController = { index, submitPage, status }

export default EfakturUiController