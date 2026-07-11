import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
export const store = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
store.url = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
store.post = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
const storeForm = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetLineController::store
* @see app/Http/Controllers/BudgetLineController.php:17
* @route '/budgets/{budget}/lines'
*/
storeForm.post = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
export const update = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
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
update.url = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions) => {
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
update.put = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
const updateForm = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetLineController::update
* @see app/Http/Controllers/BudgetLineController.php:41
* @route '/budgets/{budget}/lines/{line}'
*/
updateForm.put = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
export const destroy = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
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
destroy.url = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions) => {
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
destroy.delete = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
const destroyForm = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetLineController::destroy
* @see app/Http/Controllers/BudgetLineController.php:66
* @route '/budgets/{budget}/lines/{line}'
*/
destroyForm.delete = (args: { budget: string | { id: string }, line: string | { id: string } } | [budget: string | { id: string }, line: string | { id: string } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const lines = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default lines