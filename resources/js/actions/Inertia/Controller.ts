import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
const Controllerd3f40fab60887a2723ab34bfa72648a2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerd3f40fab60887a2723ab34bfa72648a2.url(options),
    method: 'get',
})

Controllerd3f40fab60887a2723ab34bfa72648a2.definition = {
    methods: ["get","head"],
    url: '/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
Controllerd3f40fab60887a2723ab34bfa72648a2.url = (options?: RouteQueryOptions) => {
    return Controllerd3f40fab60887a2723ab34bfa72648a2.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
Controllerd3f40fab60887a2723ab34bfa72648a2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerd3f40fab60887a2723ab34bfa72648a2.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
Controllerd3f40fab60887a2723ab34bfa72648a2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerd3f40fab60887a2723ab34bfa72648a2.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
const Controllerd3f40fab60887a2723ab34bfa72648a2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerd3f40fab60887a2723ab34bfa72648a2.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
Controllerd3f40fab60887a2723ab34bfa72648a2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerd3f40fab60887a2723ab34bfa72648a2.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/notifications'
*/
Controllerd3f40fab60887a2723ab34bfa72648a2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerd3f40fab60887a2723ab34bfa72648a2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controllerd3f40fab60887a2723ab34bfa72648a2.form = Controllerd3f40fab60887a2723ab34bfa72648a2Form
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
const Controller52626741eceb371e0367fc15292c29b9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller52626741eceb371e0367fc15292c29b9.url(options),
    method: 'get',
})

Controller52626741eceb371e0367fc15292c29b9.definition = {
    methods: ["get","head"],
    url: '/audit-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
Controller52626741eceb371e0367fc15292c29b9.url = (options?: RouteQueryOptions) => {
    return Controller52626741eceb371e0367fc15292c29b9.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
Controller52626741eceb371e0367fc15292c29b9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller52626741eceb371e0367fc15292c29b9.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
Controller52626741eceb371e0367fc15292c29b9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller52626741eceb371e0367fc15292c29b9.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
const Controller52626741eceb371e0367fc15292c29b9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller52626741eceb371e0367fc15292c29b9.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
Controller52626741eceb371e0367fc15292c29b9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller52626741eceb371e0367fc15292c29b9.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/audit-logs'
*/
Controller52626741eceb371e0367fc15292c29b9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller52626741eceb371e0367fc15292c29b9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controller52626741eceb371e0367fc15292c29b9.form = Controller52626741eceb371e0367fc15292c29b9Form
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url = (options?: RouteQueryOptions) => {
    return Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.form = Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
const Controllerbc6b23a7c0258ca1187f91ecb466160a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbc6b23a7c0258ca1187f91ecb466160a.url(options),
    method: 'get',
})

Controllerbc6b23a7c0258ca1187f91ecb466160a.definition = {
    methods: ["get","head"],
    url: '/settings/components',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
Controllerbc6b23a7c0258ca1187f91ecb466160a.url = (options?: RouteQueryOptions) => {
    return Controllerbc6b23a7c0258ca1187f91ecb466160a.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
Controllerbc6b23a7c0258ca1187f91ecb466160a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbc6b23a7c0258ca1187f91ecb466160a.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
Controllerbc6b23a7c0258ca1187f91ecb466160a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerbc6b23a7c0258ca1187f91ecb466160a.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
const Controllerbc6b23a7c0258ca1187f91ecb466160aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerbc6b23a7c0258ca1187f91ecb466160a.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
Controllerbc6b23a7c0258ca1187f91ecb466160aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerbc6b23a7c0258ca1187f91ecb466160a.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
Controllerbc6b23a7c0258ca1187f91ecb466160aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerbc6b23a7c0258ca1187f91ecb466160a.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controllerbc6b23a7c0258ca1187f91ecb466160a.form = Controllerbc6b23a7c0258ca1187f91ecb466160aForm

const Controller = {
    '/notifications': Controllerd3f40fab60887a2723ab34bfa72648a2,
    '/audit-logs': Controller52626741eceb371e0367fc15292c29b9,
    '/settings/appearance': Controllere19ee86e9cf603ce1a59a1ec5d21dec5,
    '/settings/components': Controllerbc6b23a7c0258ca1187f91ecb466160a,
}

export default Controller