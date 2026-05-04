import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/dues',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::index
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:20
* @route '/cooperative/dues'
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
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:39
* @route '/cooperative/dues/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/cooperative/dues/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:39
* @route '/cooperative/dues/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:39
* @route '/cooperative/dues/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:39
* @route '/cooperative/dues/generate'
*/
const generateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::generate
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:39
* @route '/cooperative/dues/generate'
*/
generateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

generate.form = generateForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:46
* @route '/cooperative/dues/mark-paid'
*/
export const markPaid = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markPaid.url(options),
    method: 'post',
})

markPaid.definition = {
    methods: ["post"],
    url: '/cooperative/dues/mark-paid',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:46
* @route '/cooperative/dues/mark-paid'
*/
markPaid.url = (options?: RouteQueryOptions) => {
    return markPaid.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:46
* @route '/cooperative/dues/mark-paid'
*/
markPaid.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markPaid.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:46
* @route '/cooperative/dues/mark-paid'
*/
const markPaidForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markPaid.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeDuesController::markPaid
* @see app/Http/Controllers/Cooperative/CooperativeDuesController.php:46
* @route '/cooperative/dues/mark-paid'
*/
markPaidForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markPaid.url(options),
    method: 'post',
})

markPaid.form = markPaidForm

const CooperativeDuesController = { index, generate, markPaid }

export default CooperativeDuesController