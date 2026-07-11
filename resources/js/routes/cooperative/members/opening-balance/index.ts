import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::show
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:25
* @route '/cooperative/members/{member}/opening-balance'
*/
export const show = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/{member}/opening-balance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::show
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:25
* @route '/cooperative/members/{member}/opening-balance'
*/
show.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::show
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:25
* @route '/cooperative/members/{member}/opening-balance'
*/
show.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::show
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:25
* @route '/cooperative/members/{member}/opening-balance'
*/
show.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::preview
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:104
* @route '/cooperative/members/{member}/opening-balance/preview'
*/
export const preview = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/opening-balance/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::preview
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:104
* @route '/cooperative/members/{member}/opening-balance/preview'
*/
preview.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return preview.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::preview
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:104
* @route '/cooperative/members/{member}/opening-balance/preview'
*/
preview.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::store
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:117
* @route '/cooperative/members/{member}/opening-balance/draft'
*/
export const store = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/opening-balance/draft',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::store
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:117
* @route '/cooperative/members/{member}/opening-balance/draft'
*/
store.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::store
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:117
* @route '/cooperative/members/{member}/opening-balance/draft'
*/
store.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

const openingBalance = {
    show: Object.assign(show, show),
    preview: Object.assign(preview, preview),
    store: Object.assign(store, store),
}

export default openingBalance