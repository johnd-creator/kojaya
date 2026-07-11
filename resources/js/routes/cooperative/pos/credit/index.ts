import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
export const create = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/members/{member}/credit/pay',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
create.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return create.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
create.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
create.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
const createForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
createForm.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::create
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:17
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
createForm.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::store
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:29
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
export const store = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/members/{member}/credit/pay',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::store
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:29
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
store.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return store.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::store
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:29
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
store.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::store
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:29
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
const storeForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosMemberCreditController::store
* @see app/Http/Controllers/Cooperative/PosMemberCreditController.php:29
* @route '/cooperative/pos/members/{member}/credit/pay'
*/
storeForm.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const credit = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
}

export default credit