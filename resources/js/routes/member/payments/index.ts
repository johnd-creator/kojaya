import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:413
* @route '/member/payments/proof'
*/
export const proof = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proof.url(options),
    method: 'post',
})

proof.definition = {
    methods: ["post"],
    url: '/member/payments/proof',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:413
* @route '/member/payments/proof'
*/
proof.url = (options?: RouteQueryOptions) => {
    return proof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:413
* @route '/member/payments/proof'
*/
proof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:413
* @route '/member/payments/proof'
*/
const proofForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: proof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::proof
* @see app/Http/Controllers/MemberPortalController.php:413
* @route '/member/payments/proof'
*/
proofForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: proof.url(options),
    method: 'post',
})

proof.form = proofForm

const payments = {
    proof: Object.assign(proof, proof),
}

export default payments