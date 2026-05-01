import VendorController from './VendorController'
import PurchaseRequestController from './PurchaseRequestController'
import PurchaseOrderController from './PurchaseOrderController'
import GrnController from './GrnController'

const Procurement = {
    VendorController: Object.assign(VendorController, VendorController),
    PurchaseRequestController: Object.assign(PurchaseRequestController, PurchaseRequestController),
    PurchaseOrderController: Object.assign(PurchaseOrderController, PurchaseOrderController),
    GrnController: Object.assign(GrnController, GrnController),
}

export default Procurement