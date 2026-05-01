import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
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
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::index
* @see app/Http/Controllers/BudgetController.php:28
* @route '/budgets'
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
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:66
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
* @see app/Http/Controllers/BudgetController.php:66
* @route '/budgets'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:66
* @route '/budgets'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:66
* @route '/budgets'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::store
* @see app/Http/Controllers/BudgetController.php:66
* @route '/budgets'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
export const show = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
show.url = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
show.get = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
show.head = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
const showForm = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
showForm.get = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BudgetController::show
* @see app/Http/Controllers/BudgetController.php:103
* @route '/budgets/{budget}'
*/
showForm.head = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
export const update = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
update.url = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
update.put = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
update.patch = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
const updateForm = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
updateForm.put = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::update
* @see app/Http/Controllers/BudgetController.php:125
* @route '/budgets/{budget}'
*/
updateForm.patch = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:169
* @route '/budgets/{budget}'
*/
export const destroy = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/budgets/{budget}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:169
* @route '/budgets/{budget}'
*/
destroy.url = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/BudgetController.php:169
* @route '/budgets/{budget}'
*/
destroy.delete = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:169
* @route '/budgets/{budget}'
*/
const destroyForm = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::destroy
* @see app/Http/Controllers/BudgetController.php:169
* @route '/budgets/{budget}'
*/
destroyForm.delete = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:182
* @route '/budgets/{budget}/import'
*/
export const importMethod = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
})

importMethod.definition = {
    methods: ["post"],
    url: '/budgets/{budget}/import',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:182
* @route '/budgets/{budget}/import'
*/
importMethod.url = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/BudgetController.php:182
* @route '/budgets/{budget}/import'
*/
importMethod.post = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: importMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:182
* @route '/budgets/{budget}/import'
*/
const importMethodForm = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BudgetController::importMethod
* @see app/Http/Controllers/BudgetController.php:182
* @route '/budgets/{budget}/import'
*/
importMethodForm.post = (args: { budget: string | { id: string } } | [budget: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: importMethod.url(args, options),
    method: 'post',
})

importMethod.form = importMethodForm

const BudgetController = { index, store, show, update, destroy, importMethod, import: importMethod }

export default BudgetController