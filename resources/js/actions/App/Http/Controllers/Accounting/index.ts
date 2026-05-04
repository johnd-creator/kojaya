import ChartOfAccountController from './ChartOfAccountController'
import JournalEntryController from './JournalEntryController'
import FinancialStatementController from './FinancialStatementController'

const Accounting = {
    ChartOfAccountController: Object.assign(ChartOfAccountController, ChartOfAccountController),
    JournalEntryController: Object.assign(JournalEntryController, JournalEntryController),
    FinancialStatementController: Object.assign(FinancialStatementController, FinancialStatementController),
}

export default Accounting