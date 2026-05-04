import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/loans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::index
* @see app/Http/Controllers/Api/V1/LoanApiController.php:18
* @route '/api/v1/loans'
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
* @see \App\Http\Controllers\Api\V1\LoanApiController::apply
* @see app/Http/Controllers/Api/V1/LoanApiController.php:34
* @route '/api/v1/loans/apply'
*/
export const apply = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apply.url(options),
    method: 'post',
})

apply.definition = {
    methods: ["post"],
    url: '/api/v1/loans/apply',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::apply
* @see app/Http/Controllers/Api/V1/LoanApiController.php:34
* @route '/api/v1/loans/apply'
*/
apply.url = (options?: RouteQueryOptions) => {
    return apply.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::apply
* @see app/Http/Controllers/Api/V1/LoanApiController.php:34
* @route '/api/v1/loans/apply'
*/
apply.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apply.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::apply
* @see app/Http/Controllers/Api/V1/LoanApiController.php:34
* @route '/api/v1/loans/apply'
*/
const applyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: apply.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::apply
* @see app/Http/Controllers/Api/V1/LoanApiController.php:34
* @route '/api/v1/loans/apply'
*/
applyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: apply.url(options),
    method: 'post',
})

apply.form = applyForm

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
export const show = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/loans/{loan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
show.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return show.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
show.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
show.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
const showForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
showForm.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::show
* @see app/Http/Controllers/Api/V1/LoanApiController.php:48
* @route '/api/v1/loans/{loan}'
*/
showForm.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::calculator
* @see app/Http/Controllers/Api/V1/LoanApiController.php:61
* @route '/api/v1/loans/calculator'
*/
export const calculator = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: calculator.url(options),
    method: 'post',
})

calculator.definition = {
    methods: ["post"],
    url: '/api/v1/loans/calculator',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::calculator
* @see app/Http/Controllers/Api/V1/LoanApiController.php:61
* @route '/api/v1/loans/calculator'
*/
calculator.url = (options?: RouteQueryOptions) => {
    return calculator.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::calculator
* @see app/Http/Controllers/Api/V1/LoanApiController.php:61
* @route '/api/v1/loans/calculator'
*/
calculator.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: calculator.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::calculator
* @see app/Http/Controllers/Api/V1/LoanApiController.php:61
* @route '/api/v1/loans/calculator'
*/
const calculatorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: calculator.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\LoanApiController::calculator
* @see app/Http/Controllers/Api/V1/LoanApiController.php:61
* @route '/api/v1/loans/calculator'
*/
calculatorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: calculator.url(options),
    method: 'post',
})

calculator.form = calculatorForm

const LoanApiController = { index, apply, show, calculator }

export default LoanApiController