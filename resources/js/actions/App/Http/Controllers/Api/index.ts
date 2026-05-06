import AuthController from './AuthController'
import TokenController from './TokenController'
import V1 from './V1'
import EssController from './EssController'
import TechnicianWorkOrderController from './TechnicianWorkOrderController'

const Api = {
    AuthController: Object.assign(AuthController, AuthController),
    TokenController: Object.assign(TokenController, TokenController),
    V1: Object.assign(V1, V1),
    EssController: Object.assign(EssController, EssController),
    TechnicianWorkOrderController: Object.assign(TechnicianWorkOrderController, TechnicianWorkOrderController),
}

export default Api