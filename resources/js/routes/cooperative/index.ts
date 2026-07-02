import members from './members'
import openingBalances from './opening-balances'
import dues from './dues'
import payments from './payments'
import ledger from './ledger'
import savings from './savings'
import loanTypes from './loan-types'
import loans from './loans'
import points from './points'
import rewards from './rewards'
import redemptions from './redemptions'
import shu from './shu'
import pos from './pos'
import posCategories from './pos-categories'
import posProducts from './pos-products'
import reports from './reports'
import operator from './operator'

const cooperative = {
    members: Object.assign(members, members),
    openingBalances: Object.assign(openingBalances, openingBalances),
    dues: Object.assign(dues, dues),
    payments: Object.assign(payments, payments),
    ledger: Object.assign(ledger, ledger),
    savings: Object.assign(savings, savings),
    loanTypes: Object.assign(loanTypes, loanTypes),
    loans: Object.assign(loans, loans),
    points: Object.assign(points, points),
    rewards: Object.assign(rewards, rewards),
    redemptions: Object.assign(redemptions, redemptions),
    shu: Object.assign(shu, shu),
    pos: Object.assign(pos, pos),
    posCategories: Object.assign(posCategories, posCategories),
    posProducts: Object.assign(posProducts, posProducts),
    reports: Object.assign(reports, reports),
    operator: Object.assign(operator, operator),
}

export default cooperative