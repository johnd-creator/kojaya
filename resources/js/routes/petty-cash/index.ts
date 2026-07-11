import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import transactions from './transactions'
/**
* @see \App\Http\Controllers\PettyCashAccountController::index
* @see app/Http/Controllers/PettyCashAccountController.php:12
* @route '/petty-cash'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/petty-cash',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::index
* @see app/Http/Controllers/PettyCashAccountController.php:12
* @route '/petty-cash'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::index
* @see app/Http/Controllers/PettyCashAccountController.php:12
* @route '/petty-cash'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::index
* @see app/Http/Controllers/PettyCashAccountController.php:12
* @route '/petty-cash'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::create
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/petty-cash/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::create
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::create
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::create
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::store
* @see app/Http/Controllers/PettyCashAccountController.php:28
* @route '/petty-cash'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/petty-cash',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::store
* @see app/Http/Controllers/PettyCashAccountController.php:28
* @route '/petty-cash'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::store
* @see app/Http/Controllers/PettyCashAccountController.php:28
* @route '/petty-cash'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::show
* @see app/Http/Controllers/PettyCashAccountController.php:38
* @route '/petty-cash/{petty_cash}'
*/
export const show = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/petty-cash/{petty_cash}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::show
* @see app/Http/Controllers/PettyCashAccountController.php:38
* @route '/petty-cash/{petty_cash}'
*/
show.url = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petty_cash: args }
    }

    if (Array.isArray(args)) {
        args = {
            petty_cash: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        petty_cash: args.petty_cash,
    }

    return show.definition.url
            .replace('{petty_cash}', parsedArgs.petty_cash.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::show
* @see app/Http/Controllers/PettyCashAccountController.php:38
* @route '/petty-cash/{petty_cash}'
*/
show.get = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::show
* @see app/Http/Controllers/PettyCashAccountController.php:38
* @route '/petty-cash/{petty_cash}'
*/
show.head = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::edit
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/{petty_cash}/edit'
*/
export const edit = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/petty-cash/{petty_cash}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::edit
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/{petty_cash}/edit'
*/
edit.url = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petty_cash: args }
    }

    if (Array.isArray(args)) {
        args = {
            petty_cash: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        petty_cash: args.petty_cash,
    }

    return edit.definition.url
            .replace('{petty_cash}', parsedArgs.petty_cash.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::edit
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/{petty_cash}/edit'
*/
edit.get = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::edit
* @see app/Http/Controllers/PettyCashAccountController.php:0
* @route '/petty-cash/{petty_cash}/edit'
*/
edit.head = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::update
* @see app/Http/Controllers/PettyCashAccountController.php:50
* @route '/petty-cash/{petty_cash}'
*/
export const update = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/petty-cash/{petty_cash}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::update
* @see app/Http/Controllers/PettyCashAccountController.php:50
* @route '/petty-cash/{petty_cash}'
*/
update.url = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petty_cash: args }
    }

    if (Array.isArray(args)) {
        args = {
            petty_cash: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        petty_cash: args.petty_cash,
    }

    return update.definition.url
            .replace('{petty_cash}', parsedArgs.petty_cash.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::update
* @see app/Http/Controllers/PettyCashAccountController.php:50
* @route '/petty-cash/{petty_cash}'
*/
update.put = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::update
* @see app/Http/Controllers/PettyCashAccountController.php:50
* @route '/petty-cash/{petty_cash}'
*/
update.patch = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\PettyCashAccountController::destroy
* @see app/Http/Controllers/PettyCashAccountController.php:62
* @route '/petty-cash/{petty_cash}'
*/
export const destroy = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/petty-cash/{petty_cash}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\PettyCashAccountController::destroy
* @see app/Http/Controllers/PettyCashAccountController.php:62
* @route '/petty-cash/{petty_cash}'
*/
destroy.url = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { petty_cash: args }
    }

    if (Array.isArray(args)) {
        args = {
            petty_cash: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        petty_cash: args.petty_cash,
    }

    return destroy.definition.url
            .replace('{petty_cash}', parsedArgs.petty_cash.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PettyCashAccountController::destroy
* @see app/Http/Controllers/PettyCashAccountController.php:62
* @route '/petty-cash/{petty_cash}'
*/
destroy.delete = (args: { petty_cash: string | number } | [petty_cash: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const pettyCash = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    transactions: Object.assign(transactions, transactions),
}

export default pettyCash