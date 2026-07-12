import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:19
* @route '/budgets'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/budgets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:19
* @route '/budgets'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:19
* @route '/budgets'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:19
* @route '/budgets'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:57
* @route '/budgets'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/budgets',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:57
* @route '/budgets'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:57
* @route '/budgets'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:89
* @route '/budgets/{budget}'
*/
export const show = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:89
* @route '/budgets/{budget}'
*/
show.url = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { budget: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { budget: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            budget: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
    }

    return show.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:89
* @route '/budgets/{budget}'
*/
show.get = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:89
* @route '/budgets/{budget}'
*/
show.head = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:111
* @route '/budgets/{budget}'
*/
export const update = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:111
* @route '/budgets/{budget}'
*/
update.url = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { budget: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { budget: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            budget: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
    }

    return update.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:111
* @route '/budgets/{budget}'
*/
update.put = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:111
* @route '/budgets/{budget}'
*/
update.patch = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:150
* @route '/budgets/{budget}'
*/
export const destroy = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:150
* @route '/budgets/{budget}'
*/
destroy.url = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { budget: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { budget: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            budget: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
    }

    return destroy.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:150
* @route '/budgets/{budget}'
*/
destroy.delete = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:163
* @route '/budgets/{budget}/import'
*/
export const importMethod = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
})

importMethod.definition = {
    methods: ["post"],
    url: '/budgets/{budget}/import',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:163
* @route '/budgets/{budget}/import'
*/
importMethod.url = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { budget: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { budget: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            budget: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
    }

    return importMethod.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:163
* @route '/budgets/{budget}/import'
*/
importMethod.post = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
})

const BudgetController = { index, store, show, update, destroy, importMethod, import: importMethod }

export default BudgetController