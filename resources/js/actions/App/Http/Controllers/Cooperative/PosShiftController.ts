import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/shifts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::index
* @see app/Http/Controllers/Cooperative/PosShiftController.php:19
* @route '/cooperative/pos/shifts'
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
* @see \App\Http\Controllers\Cooperative\PosShiftController::open
* @see app/Http/Controllers/Cooperative/PosShiftController.php:35
* @route '/cooperative/pos/shifts/open'
*/
export const open = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: open.url(options),
    method: 'post',
})

open.definition = {
    methods: ["post"],
    url: '/cooperative/pos/shifts/open',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::open
* @see app/Http/Controllers/Cooperative/PosShiftController.php:35
* @route '/cooperative/pos/shifts/open'
*/
open.url = (options?: RouteQueryOptions) => {
    return open.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::open
* @see app/Http/Controllers/Cooperative/PosShiftController.php:35
* @route '/cooperative/pos/shifts/open'
*/
open.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: open.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::open
* @see app/Http/Controllers/Cooperative/PosShiftController.php:35
* @route '/cooperative/pos/shifts/open'
*/
const openForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: open.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::open
* @see app/Http/Controllers/Cooperative/PosShiftController.php:35
* @route '/cooperative/pos/shifts/open'
*/
openForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: open.url(options),
    method: 'post',
})

open.form = openForm

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::close
* @see app/Http/Controllers/Cooperative/PosShiftController.php:48
* @route '/cooperative/pos/shifts/{shift}/close'
*/
export const close = (args: { shift: number | { id: number } } | [shift: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/cooperative/pos/shifts/{shift}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::close
* @see app/Http/Controllers/Cooperative/PosShiftController.php:48
* @route '/cooperative/pos/shifts/{shift}/close'
*/
close.url = (args: { shift: number | { id: number } } | [shift: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { shift: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { shift: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            shift: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        shift: typeof args.shift === 'object'
        ? args.shift.id
        : args.shift,
    }

    return close.definition.url
            .replace('{shift}', parsedArgs.shift.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::close
* @see app/Http/Controllers/Cooperative/PosShiftController.php:48
* @route '/cooperative/pos/shifts/{shift}/close'
*/
close.post = (args: { shift: number | { id: number } } | [shift: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::close
* @see app/Http/Controllers/Cooperative/PosShiftController.php:48
* @route '/cooperative/pos/shifts/{shift}/close'
*/
const closeForm = (args: { shift: number | { id: number } } | [shift: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosShiftController::close
* @see app/Http/Controllers/Cooperative/PosShiftController.php:48
* @route '/cooperative/pos/shifts/{shift}/close'
*/
closeForm.post = (args: { shift: number | { id: number } } | [shift: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

close.form = closeForm

const PosShiftController = { index, open, close }

export default PosShiftController