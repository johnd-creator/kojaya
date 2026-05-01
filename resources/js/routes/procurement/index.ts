import vendors from './vendors'
import prs from './prs'
import pos from './pos'
import grns from './grns'

const procurement = {
    vendors: Object.assign(vendors, vendors),
    prs: Object.assign(prs, prs),
    pos: Object.assign(pos, pos),
    grns: Object.assign(grns, grns),
}

export default procurement