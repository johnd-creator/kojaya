import CooperativeReportController from './CooperativeReportController'
import CooperativeMemberController from './CooperativeMemberController'
import CooperativeDuesController from './CooperativeDuesController'
import CooperativePaymentController from './CooperativePaymentController'
import CooperativeLedgerController from './CooperativeLedgerController'
import AnnualShuController from './AnnualShuController'
import PosRegisterController from './PosRegisterController'
import PosSalesReportController from './PosSalesReportController'
import PosAnnualShuController from './PosAnnualShuController'
import PosTransactionHistoryController from './PosTransactionHistoryController'
import PosCategoryController from './PosCategoryController'
import PosProductController from './PosProductController'

const Cooperative = {
    CooperativeReportController: Object.assign(CooperativeReportController, CooperativeReportController),
    CooperativeMemberController: Object.assign(CooperativeMemberController, CooperativeMemberController),
    CooperativeDuesController: Object.assign(CooperativeDuesController, CooperativeDuesController),
    CooperativePaymentController: Object.assign(CooperativePaymentController, CooperativePaymentController),
    CooperativeLedgerController: Object.assign(CooperativeLedgerController, CooperativeLedgerController),
    AnnualShuController: Object.assign(AnnualShuController, AnnualShuController),
    PosRegisterController: Object.assign(PosRegisterController, PosRegisterController),
    PosSalesReportController: Object.assign(PosSalesReportController, PosSalesReportController),
    PosAnnualShuController: Object.assign(PosAnnualShuController, PosAnnualShuController),
    PosTransactionHistoryController: Object.assign(PosTransactionHistoryController, PosTransactionHistoryController),
    PosCategoryController: Object.assign(PosCategoryController, PosCategoryController),
    PosProductController: Object.assign(PosProductController, PosProductController),
}

export default Cooperative