import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:224
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
* @see app/Http/Controllers/PayrollController.php:224
* @route '/payrolls/thr/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:224
* @route '/payrolls/thr/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:224
* @route '/payrolls/thr/preview'
*/
const previewForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::preview
* @see app/Http/Controllers/PayrollController.php:224
* @route '/payrolls/thr/preview'
*/
previewForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preview.url(options),
    method: 'post',
})

preview.form = previewForm

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:272
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
* @see app/Http/Controllers/PayrollController.php:272
* @route '/payrolls/thr/generate'
*/
generate.url = (options?: RouteQueryOptions) => {
    return generate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:272
* @route '/payrolls/thr/generate'
*/
generate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:272
* @route '/payrolls/thr/generate'
*/
const generateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PayrollController::generate
* @see app/Http/Controllers/PayrollController.php:272
* @route '/payrolls/thr/generate'
*/
generateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: generate.url(options),
    method: 'post',
})

generate.form = generateForm

const thr = {
    preview: Object.assign(preview, preview),
    generate: Object.assign(generate, generate),
}

export default thr