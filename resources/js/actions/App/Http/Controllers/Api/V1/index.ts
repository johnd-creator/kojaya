import CooperativeMemberApiController from './CooperativeMemberApiController'
import CooperativeDuesApiController from './CooperativeDuesApiController'
import CooperativePaymentApiController from './CooperativePaymentApiController'
import PosApiController from './PosApiController'

const V1 = {
    CooperativeMemberApiController: Object.assign(CooperativeMemberApiController, CooperativeMemberApiController),
    CooperativeDuesApiController: Object.assign(CooperativeDuesApiController, CooperativeDuesApiController),
    CooperativePaymentApiController: Object.assign(CooperativePaymentApiController, CooperativePaymentApiController),
    PosApiController: Object.assign(PosApiController, PosApiController),
}

export default V1