import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::post
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:141
* @route '/cooperative/opening-balances/{batch}/post'
*/
export const post = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(args, options),
    method: 'post',
})

post.definition = {
    methods: ["post"],
    url: '/cooperative/opening-balances/{batch}/post',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::post
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:141
* @route '/cooperative/opening-balances/{batch}/post'
*/
post.url = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { batch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: typeof args.batch === 'object'
        ? args.batch.id
        : args.batch,
    }

    return post.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::post
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:141
* @route '/cooperative/opening-balances/{batch}/post'
*/
post.post = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::post
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:141
* @route '/cooperative/opening-balances/{batch}/post'
*/
const postForm = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::post
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:141
* @route '/cooperative/opening-balances/{batch}/post'
*/
postForm.post = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(args, options),
    method: 'post',
})

post.form = postForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::voidMethod
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:156
* @route '/cooperative/opening-balances/{batch}/void'
*/
export const voidMethod = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: voidMethod.url(args, options),
    method: 'post',
})

voidMethod.definition = {
    methods: ["post"],
    url: '/cooperative/opening-balances/{batch}/void',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::voidMethod
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:156
* @route '/cooperative/opening-balances/{batch}/void'
*/
voidMethod.url = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { batch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: typeof args.batch === 'object'
        ? args.batch.id
        : args.batch,
    }

    return voidMethod.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::voidMethod
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:156
* @route '/cooperative/opening-balances/{batch}/void'
*/
voidMethod.post = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: voidMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::voidMethod
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:156
* @route '/cooperative/opening-balances/{batch}/void'
*/
const voidMethodForm = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: voidMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::voidMethod
* @see app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php:156
* @route '/cooperative/opening-balances/{batch}/void'
*/
voidMethodForm.post = (args: { batch: number | { id: number } } | [batch: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: voidMethod.url(args, options),
    method: 'post',
})

voidMethod.form = voidMethodForm

const openingBalances = {
    post: Object.assign(post, post),
    void: Object.assign(voidMethod, voidMethod),
}

export default openingBalances