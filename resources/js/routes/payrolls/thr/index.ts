import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/payrolls/thr/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:148
* @route '/payrolls/thr/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
export const generate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

generate.definition = {
    methods: ["post"],
    url: '/payrolls/thr/generate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:166
* @route '/payrolls/thr/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

const thr = {
    preview: Object.assign(preview, preview),
    generate: Object.assign(generate, generate),
}

export default thr