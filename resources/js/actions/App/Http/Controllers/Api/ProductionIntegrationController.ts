import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::paymentWebhook
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:65
* @route '/api/payments/webhook'
*/
export const paymentWebhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: paymentWebhook.url(options),
    method: 'post',
})

paymentWebhook.definition = {
    methods: ["post"],
    url: '/api/payments/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::paymentWebhook
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:65
* @route '/api/payments/webhook'
*/
paymentWebhook.url = (options?: RouteQueryOptions) => {
    return paymentWebhook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::paymentWebhook
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:65
* @route '/api/payments/webhook'
*/
paymentWebhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: paymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::paymentWebhook
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:65
* @route '/api/payments/webhook'
*/
const paymentWebhookForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: paymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::paymentWebhook
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:65
* @route '/api/payments/webhook'
*/
paymentWebhookForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: paymentWebhook.url(options),
    method: 'post',
})

paymentWebhook.form = paymentWebhookForm

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::registerDevice
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:22
* @route '/api/devices/push-token'
*/
export const registerDevice = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: registerDevice.url(options),
    method: 'post',
})

registerDevice.definition = {
    methods: ["post"],
    url: '/api/devices/push-token',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::registerDevice
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:22
* @route '/api/devices/push-token'
*/
registerDevice.url = (options?: RouteQueryOptions) => {
    return registerDevice.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::registerDevice
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:22
* @route '/api/devices/push-token'
*/
registerDevice.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: registerDevice.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::registerDevice
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:22
* @route '/api/devices/push-token'
*/
const registerDeviceForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: registerDevice.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::registerDevice
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:22
* @route '/api/devices/push-token'
*/
registerDeviceForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: registerDevice.url(options),
    method: 'post',
})

registerDevice.form = registerDeviceForm

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::createPaymentCharge
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:43
* @route '/api/payments/charge'
*/
export const createPaymentCharge = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentCharge.url(options),
    method: 'post',
})

createPaymentCharge.definition = {
    methods: ["post"],
    url: '/api/payments/charge',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::createPaymentCharge
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:43
* @route '/api/payments/charge'
*/
createPaymentCharge.url = (options?: RouteQueryOptions) => {
    return createPaymentCharge.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::createPaymentCharge
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:43
* @route '/api/payments/charge'
*/
createPaymentCharge.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentCharge.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::createPaymentCharge
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:43
* @route '/api/payments/charge'
*/
const createPaymentChargeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createPaymentCharge.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::createPaymentCharge
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:43
* @route '/api/payments/charge'
*/
createPaymentChargeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: createPaymentCharge.url(options),
    method: 'post',
})

createPaymentCharge.form = createPaymentChargeForm

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
export const monitoring = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitoring.url(options),
    method: 'get',
})

monitoring.definition = {
    methods: ["get","head"],
    url: '/api/monitoring/health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
monitoring.url = (options?: RouteQueryOptions) => {
    return monitoring.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
monitoring.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitoring.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
monitoring.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitoring.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
const monitoringForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitoring.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
monitoringForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitoring.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\ProductionIntegrationController::monitoring
* @see app/Http/Controllers/Api/ProductionIntegrationController.php:121
* @route '/api/monitoring/health'
*/
monitoringForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitoring.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

monitoring.form = monitoringForm

const ProductionIntegrationController = { paymentWebhook, registerDevice, createPaymentCharge, monitoring }

export default ProductionIntegrationController