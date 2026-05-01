import members from './members'
import dues from './dues'
import payments from './payments'
import ledger from './ledger'
import shu from './shu'
import pos from './pos'
import posCategories from './pos-categories'
import posProducts from './pos-products'
import reports from './reports'

const cooperative = {
    members: Object.assign(members, members),
    dues: Object.assign(dues, dues),
    payments: Object.assign(payments, payments),
    ledger: Object.assign(ledger, ledger),
    shu: Object.assign(shu, shu),
    pos: Object.assign(pos, pos),
    posCategories: Object.assign(posCategories, posCategories),
    posProducts: Object.assign(posProducts, posProducts),
    reports: Object.assign(reports, reports),
}

export default cooperative