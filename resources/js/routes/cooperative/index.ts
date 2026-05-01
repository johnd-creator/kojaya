import members from './members'
import dues from './dues'
import payments from './payments'
import pos from './pos'
import reports from './reports'

const cooperative = {
    members: Object.assign(members, members),
    dues: Object.assign(dues, dues),
    payments: Object.assign(payments, payments),
    pos: Object.assign(pos, pos),
    reports: Object.assign(reports, reports),
}

export default cooperative