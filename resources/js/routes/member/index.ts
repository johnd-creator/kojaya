import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import loans1385a5 from './loans'
import rewardsA1df06 from './rewards'
import profile937a89 from './profile'
/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/member',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:24
* @route '/member'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
export const savings = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savings.url(options),
    method: 'get',
})

savings.definition = {
    methods: ["get","head"],
    url: '/member/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
savings.url = (options?: RouteQueryOptions) => {
    return savings.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
savings.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
savings.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savings.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
const savingsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
savingsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:55
* @route '/member/savings'
*/
savingsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

savings.form = savingsForm

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
export const loans = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

loans.definition = {
    methods: ["get","head"],
    url: '/member/loans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
loans.url = (options?: RouteQueryOptions) => {
    return loans.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
loans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
loans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loans.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
const loansForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
loansForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:71
* @route '/member/loans'
*/
loansForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

loans.form = loansForm

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
export const points = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: points.url(options),
    method: 'get',
})

points.definition = {
    methods: ["get","head"],
    url: '/member/points',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
points.url = (options?: RouteQueryOptions) => {
    return points.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
points.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
points.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: points.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
const pointsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
pointsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:99
* @route '/member/points'
*/
pointsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

points.form = pointsForm

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
export const rewards = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewards.url(options),
    method: 'get',
})

rewards.definition = {
    methods: ["get","head"],
    url: '/member/rewards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
rewards.url = (options?: RouteQueryOptions) => {
    return rewards.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
rewards.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
rewards.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rewards.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
const rewardsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
rewardsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:110
* @route '/member/rewards'
*/
rewardsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

rewards.form = rewardsForm

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/member/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:142
* @route '/member/transactions'
*/
transactionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

transactions.form = transactionsForm

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/member/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:156
* @route '/member/profile'
*/
profileForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
export const notifications = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

notifications.definition = {
    methods: ["get","head"],
    url: '/member/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
const notificationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
notificationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:186
* @route '/member/notifications'
*/
notificationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notifications.form = notificationsForm

const member = {
    dashboard: Object.assign(dashboard, dashboard),
    savings: Object.assign(savings, savings),
    loans: Object.assign(loans, loans1385a5),
    points: Object.assign(points, points),
    rewards: Object.assign(rewards, rewardsA1df06),
    transactions: Object.assign(transactions, transactions),
    profile: Object.assign(profile, profile937a89),
    notifications: Object.assign(notifications, notifications),
}

export default member