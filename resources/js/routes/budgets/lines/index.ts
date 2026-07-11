import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
export const store = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/budgets/{budget}/lines',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
store.url = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
store.post = (args: { budget: string | number | { id: string | number } } | [budget: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
export const update = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/budgets/{budget}/lines/{line}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
update.url = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            budget: args[0],
            line: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
        line: typeof args.line === 'object'
        ? args.line.id
        : args.line,
    }

    return update.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace('{line}', parsedArgs.line.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
update.put = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
export const destroy = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/budgets/{budget}/lines/{line}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
destroy.url = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            budget: args[0],
            line: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        budget: typeof args.budget === 'object'
        ? args.budget.id
        : args.budget,
        line: typeof args.line === 'object'
        ? args.line.id
        : args.line,
    }

    return destroy.definition.url
            .replace('{budget}', parsedArgs.budget.toString())
            .replace('{line}', parsedArgs.line.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
destroy.delete = (args: { budget: string | number | { id: string | number }, line: string | number | { id: string | number } } | [budget: string | number | { id: string | number }, line: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const lines = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default lines