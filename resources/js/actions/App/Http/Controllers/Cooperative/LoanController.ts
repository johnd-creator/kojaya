import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
export const calculator = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: calculator.url(options),
    method: 'get',
})

calculator.definition = {
    methods: ["get","head"],
    url: '/cooperative/loans/calculator',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
calculator.url = (options?: RouteQueryOptions) => {
    return calculator.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
calculator.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: calculator.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
calculator.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: calculator.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
const calculatorForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: calculator.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
calculatorForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: calculator.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::calculator
* @see app/Http/Controllers/Cooperative/LoanController.php:185
* @route '/cooperative/loans/calculator'
*/
calculatorForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: calculator.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

calculator.form = calculatorForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/loans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::index
* @see app/Http/Controllers/Cooperative/LoanController.php:27
* @route '/cooperative/loans'
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
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/loans/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::create
* @see app/Http/Controllers/Cooperative/LoanController.php:80
* @route '/cooperative/loans/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::store
* @see app/Http/Controllers/Cooperative/LoanController.php:93
* @route '/cooperative/loans'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/loans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::store
* @see app/Http/Controllers/Cooperative/LoanController.php:93
* @route '/cooperative/loans'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::store
* @see app/Http/Controllers/Cooperative/LoanController.php:93
* @route '/cooperative/loans'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::store
* @see app/Http/Controllers/Cooperative/LoanController.php:93
* @route '/cooperative/loans'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::store
* @see app/Http/Controllers/Cooperative/LoanController.php:93
* @route '/cooperative/loans'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
export const show = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/loans/{loan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
show.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return show.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
show.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
show.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
const showForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
showForm.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::show
* @see app/Http/Controllers/Cooperative/LoanController.php:109
* @route '/cooperative/loans/{loan}'
*/
showForm.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Cooperative\LoanController::review
* @see app/Http/Controllers/Cooperative/LoanController.php:140
* @route '/cooperative/loans/{loan}/review'
*/
export const review = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
})

review.definition = {
    methods: ["post"],
    url: '/cooperative/loans/{loan}/review',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::review
* @see app/Http/Controllers/Cooperative/LoanController.php:140
* @route '/cooperative/loans/{loan}/review'
*/
review.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return review.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::review
* @see app/Http/Controllers/Cooperative/LoanController.php:140
* @route '/cooperative/loans/{loan}/review'
*/
review.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: review.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::review
* @see app/Http/Controllers/Cooperative/LoanController.php:140
* @route '/cooperative/loans/{loan}/review'
*/
const reviewForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::review
* @see app/Http/Controllers/Cooperative/LoanController.php:140
* @route '/cooperative/loans/{loan}/review'
*/
reviewForm.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: review.url(args, options),
    method: 'post',
})

review.form = reviewForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::approve
* @see app/Http/Controllers/Cooperative/LoanController.php:149
* @route '/cooperative/loans/{loan}/approve'
*/
export const approve = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/cooperative/loans/{loan}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::approve
* @see app/Http/Controllers/Cooperative/LoanController.php:149
* @route '/cooperative/loans/{loan}/approve'
*/
approve.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return approve.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::approve
* @see app/Http/Controllers/Cooperative/LoanController.php:149
* @route '/cooperative/loans/{loan}/approve'
*/
approve.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::approve
* @see app/Http/Controllers/Cooperative/LoanController.php:149
* @route '/cooperative/loans/{loan}/approve'
*/
const approveForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::approve
* @see app/Http/Controllers/Cooperative/LoanController.php:149
* @route '/cooperative/loans/{loan}/approve'
*/
approveForm.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(args, options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::reject
* @see app/Http/Controllers/Cooperative/LoanController.php:158
* @route '/cooperative/loans/{loan}/reject'
*/
export const reject = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/cooperative/loans/{loan}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::reject
* @see app/Http/Controllers/Cooperative/LoanController.php:158
* @route '/cooperative/loans/{loan}/reject'
*/
reject.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return reject.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::reject
* @see app/Http/Controllers/Cooperative/LoanController.php:158
* @route '/cooperative/loans/{loan}/reject'
*/
reject.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::reject
* @see app/Http/Controllers/Cooperative/LoanController.php:158
* @route '/cooperative/loans/{loan}/reject'
*/
const rejectForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::reject
* @see app/Http/Controllers/Cooperative/LoanController.php:158
* @route '/cooperative/loans/{loan}/reject'
*/
rejectForm.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(args, options),
    method: 'post',
})

reject.form = rejectForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::disburse
* @see app/Http/Controllers/Cooperative/LoanController.php:167
* @route '/cooperative/loans/{loan}/disburse'
*/
export const disburse = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disburse.url(args, options),
    method: 'post',
})

disburse.definition = {
    methods: ["post"],
    url: '/cooperative/loans/{loan}/disburse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::disburse
* @see app/Http/Controllers/Cooperative/LoanController.php:167
* @route '/cooperative/loans/{loan}/disburse'
*/
disburse.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return disburse.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::disburse
* @see app/Http/Controllers/Cooperative/LoanController.php:167
* @route '/cooperative/loans/{loan}/disburse'
*/
disburse.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: disburse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::disburse
* @see app/Http/Controllers/Cooperative/LoanController.php:167
* @route '/cooperative/loans/{loan}/disburse'
*/
const disburseForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: disburse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::disburse
* @see app/Http/Controllers/Cooperative/LoanController.php:167
* @route '/cooperative/loans/{loan}/disburse'
*/
disburseForm.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: disburse.url(args, options),
    method: 'post',
})

disburse.form = disburseForm

/**
* @see \App\Http\Controllers\Cooperative\LoanController::pay
* @see app/Http/Controllers/Cooperative/LoanController.php:176
* @route '/cooperative/loans/{loan}/payments'
*/
export const pay = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

pay.definition = {
    methods: ["post"],
    url: '/cooperative/loans/{loan}/payments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanController::pay
* @see app/Http/Controllers/Cooperative/LoanController.php:176
* @route '/cooperative/loans/{loan}/payments'
*/
pay.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return pay.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanController::pay
* @see app/Http/Controllers/Cooperative/LoanController.php:176
* @route '/cooperative/loans/{loan}/payments'
*/
pay.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::pay
* @see app/Http/Controllers/Cooperative/LoanController.php:176
* @route '/cooperative/loans/{loan}/payments'
*/
const payForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanController::pay
* @see app/Http/Controllers/Cooperative/LoanController.php:176
* @route '/cooperative/loans/{loan}/payments'
*/
payForm.post = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

pay.form = payForm

const LoanController = { calculator, index, create, store, show, review, approve, reject, disburse, pay }

export default LoanController