import MemberSelfServiceController from './MemberSelfServiceController'
import CooperativeMemberApiController from './CooperativeMemberApiController'
import CooperativeDuesApiController from './CooperativeDuesApiController'
import CooperativePaymentApiController from './CooperativePaymentApiController'
import SavingsApiController from './SavingsApiController'
import LoanApiController from './LoanApiController'
import PointApiController from './PointApiController'
import RewardApiController from './RewardApiController'
import PosApiController from './PosApiController'
import PosSyncApiController from './PosSyncApiController'
import ProcurementApiController from './ProcurementApiController'

const V1 = {
    MemberSelfServiceController: Object.assign(MemberSelfServiceController, MemberSelfServiceController),
    CooperativeMemberApiController: Object.assign(CooperativeMemberApiController, CooperativeMemberApiController),
    CooperativeDuesApiController: Object.assign(CooperativeDuesApiController, CooperativeDuesApiController),
    CooperativePaymentApiController: Object.assign(CooperativePaymentApiController, CooperativePaymentApiController),
    SavingsApiController: Object.assign(SavingsApiController, SavingsApiController),
    LoanApiController: Object.assign(LoanApiController, LoanApiController),
    PointApiController: Object.assign(PointApiController, PointApiController),
    RewardApiController: Object.assign(RewardApiController, RewardApiController),
    PosApiController: Object.assign(PosApiController, PosApiController),
    PosSyncApiController: Object.assign(PosSyncApiController, PosSyncApiController),
    ProcurementApiController: Object.assign(ProcurementApiController, ProcurementApiController),
}

export default V1