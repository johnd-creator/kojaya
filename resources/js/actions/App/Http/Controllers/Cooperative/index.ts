import CooperativeReportController from './CooperativeReportController'
import CooperativeMemberController from './CooperativeMemberController'
import CooperativeDuesController from './CooperativeDuesController'
import CooperativePaymentController from './CooperativePaymentController'
import PosRegisterController from './PosRegisterController'

const Cooperative = {
    CooperativeReportController: Object.assign(CooperativeReportController, CooperativeReportController),
    CooperativeMemberController: Object.assign(CooperativeMemberController, CooperativeMemberController),
    CooperativeDuesController: Object.assign(CooperativeDuesController, CooperativeDuesController),
    CooperativePaymentController: Object.assign(CooperativePaymentController, CooperativePaymentController),
    PosRegisterController: Object.assign(PosRegisterController, PosRegisterController),
}

export default Cooperative